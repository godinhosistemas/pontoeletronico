<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class CertificateService
{
    /**
     * Valida e extrai informações do certificado digital (.pfx/.p12)
     *
     * @param string $certificatePath Caminho do arquivo do certificado
     * @param string $password Senha do certificado
     * @return array|false Retorna array com informações ou false se inválido
     */
    public function validateAndExtractInfo(string $certificatePath, string $password)
    {
        try {
            \Log::info('Iniciando validação de certificado', [
                'path' => $certificatePath,
                'file_exists' => file_exists($certificatePath),
                'file_size' => file_exists($certificatePath) ? filesize($certificatePath) : 0
            ]);

            // Lê o conteúdo do certificado
            $certificateContent = file_get_contents($certificatePath);

            if (!$certificateContent) {
                \Log::error('Não foi possível ler o conteúdo do certificado');
                return [
                    'valid' => false,
                    'error' => 'Não foi possível ler o arquivo do certificado'
                ];
            }

            \Log::info('Certificado lido com sucesso', [
                'content_length' => strlen($certificateContent)
            ]);

            // Tenta ler o certificado PFX/P12
            $certificates = [];
            $success = openssl_pkcs12_read($certificateContent, $certificates, $password);

            if (!$success) {
                $opensslError = openssl_error_string();
                \Log::warning('Falha ao ler certificado PKCS12 - tentando conversão', [
                    'openssl_error' => $opensslError,
                    'password_length' => strlen($password)
                ]);

                // Se o erro for de algoritmo não suportado, tenta converter
                if (stripos($opensslError, 'unsupported') !== false) {
                    \Log::info('Detectado certificado com algoritmo legado - iniciando conversão');

                    $convertedContent = $this->convertLegacyCertificate($certificateContent, $password);

                    if ($convertedContent) {
                        // Tenta ler o certificado convertido
                        $success = openssl_pkcs12_read($convertedContent, $certificates, $password);

                        if ($success) {
                            \Log::info('Certificado legado convertido com sucesso');
                            // Atualiza o conteúdo para usar o convertido
                            $certificateContent = $convertedContent;
                        }
                    }
                }

                // Se ainda não teve sucesso, retorna erro
                if (!$success) {
                    \Log::error('Falha ao ler certificado após tentativa de conversão', [
                        'openssl_error' => $opensslError
                    ]);
                    return [
                        'valid' => false,
                        'error' => 'Senha incorreta ou certificado inválido',
                        'openssl_error' => $opensslError
                    ];
                }
            }

            \Log::info('Certificado PKCS12 lido com sucesso');

            // Extrai informações do certificado
            $certData = openssl_x509_parse($certificates['cert']);

            if (!$certData) {
                \Log::error('Não foi possível extrair dados do certificado');
                return [
                    'valid' => false,
                    'error' => 'Certificado corrompido ou inválido'
                ];
            }

            \Log::info('Dados do certificado extraídos', [
                'subject' => $certData['subject']['CN'] ?? 'N/A',
                'issuer' => $this->formatDN($certData['issuer'])
            ]);

            // Verifica se é certificado ICP-Brasil
            $isIcpBrasil = $this->isIcpBrasilCertificate($certData);

            if (!$isIcpBrasil) {
                \Log::warning('Certificado não é ICP-Brasil', [
                    'issuer' => $this->formatDN($certData['issuer'])
                ]);
                return [
                    'valid' => false,
                    'error' => 'Certificado não é da cadeia ICP-Brasil'
                ];
            }

            \Log::info('Certificado validado como ICP-Brasil');

            // Determina o tipo de certificado (A1 ou A3)
            $certificateType = $this->determineCertificateType($certificatePath);

            // Extrai dados importantes
            $validFrom = Carbon::createFromTimestamp($certData['validFrom_time_t']);
            $validUntil = Carbon::createFromTimestamp($certData['validTo_time_t']);

            // Verifica se o certificado está válido
            $now = Carbon::now();
            $isValid = $now->between($validFrom, $validUntil);

            if (!$isValid) {
                \Log::warning('Certificado fora do período de validade', [
                    'valid_from' => $validFrom->toDateString(),
                    'valid_until' => $validUntil->toDateString(),
                    'current_date' => $now->toDateString()
                ]);
            }

            $result = [
                'valid' => $isValid,
                'type' => $certificateType,
                'issuer' => $this->formatDN($certData['issuer']),
                'subject' => $this->formatDN($certData['subject']),
                'serial_number' => $certData['serialNumber'] ?? $certData['serialNumberHex'],
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'days_remaining' => $now->diffInDays($validUntil, false),
                'cnpj' => $this->extractCNPJ($certData),
                'company_name' => $certData['subject']['CN'] ?? null,
                'metadata' => [
                    'purposes' => $certData['purposes'] ?? [],
                    'extensions' => $this->extractRelevantExtensions($certData),
                    'fingerprint' => openssl_x509_fingerprint($certificates['cert'], 'sha256'),
                ],
                'certificates' => $certificates, // Guarda para uso posterior na assinatura
                'certificate_content' => $certificateContent, // Conteúdo do certificado (possivelmente convertido)
            ];

            \Log::info('Certificado validado com sucesso', [
                'valid' => $isValid,
                'expires_in_days' => $result['days_remaining'],
                'company_name' => $result['company_name']
            ]);

            return $result;

        } catch (\Exception $e) {
            \Log::error('Erro ao validar certificado: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'valid' => false,
                'error' => 'Erro ao processar certificado: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Armazena o certificado digital para a empresa
     *
     * @param Tenant $tenant
     * @param string $certificatePath
     * @param string $password
     * @return bool
     */
    public function storeCertificate(Tenant $tenant, string $certificatePath, string $password): bool
    {
        try {
            // Valida o certificado primeiro
            $certInfo = $this->validateAndExtractInfo($certificatePath, $password);

            if (!$certInfo || !$certInfo['valid']) {
                return false;
            }

            // Remove certificado antigo se existir
            if ($tenant->certificate_path) {
                Storage::disk('local')->delete($tenant->certificate_path);
            }

            // Armazena o novo certificado de forma segura
            // Usa o conteúdo validado (que pode ter sido convertido de legado para moderno)
            $fileName = 'certificates/' . $tenant->id . '_' . time() . '.pfx';
            $certificateContent = $certInfo['certificate_content'] ?? file_get_contents($certificatePath);
            Storage::disk('local')->put($fileName, $certificateContent);

            // Criptografa a senha
            $encryptedPassword = Crypt::encryptString($password);

            // Atualiza os dados do tenant
            $tenant->update([
                'certificate_path' => $fileName,
                'certificate_password_encrypted' => $encryptedPassword,
                'certificate_type' => $certInfo['type'],
                'certificate_issuer' => $certInfo['issuer'],
                'certificate_subject' => $certInfo['subject'],
                'certificate_serial_number' => $certInfo['serial_number'],
                'certificate_valid_from' => $certInfo['valid_from'],
                'certificate_valid_until' => $certInfo['valid_until'],
                'certificate_metadata' => $certInfo['metadata'],
                'certificate_active' => true,
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Erro ao armazenar certificado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém o certificado descriptografado para uso
     *
     * @param Tenant $tenant
     * @return array|false
     */
    public function getCertificateForSigning(Tenant $tenant)
    {
        try {
            if (!$tenant->certificate_path || !$tenant->certificate_active) {
                return false;
            }

            // Verifica se o certificado ainda está válido
            if (!$this->isCertificateValid($tenant)) {
                return false;
            }

            // Lê o certificado do storage
            $certificateContent = Storage::disk('local')->get($tenant->certificate_path);

            if (!$certificateContent) {
                return false;
            }

            // Descriptografa a senha
            $password = Crypt::decryptString($tenant->certificate_password_encrypted);

            // Lê o certificado
            $certificates = [];
            $success = openssl_pkcs12_read($certificateContent, $certificates, $password);

            if (!$success) {
                return false;
            }

            return $certificates;

        } catch (\Exception $e) {
            \Log::error('Erro ao obter certificado para assinatura: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica se o certificado da empresa ainda está válido
     *
     * @param Tenant $tenant
     * @return bool
     */
    public function isCertificateValid(Tenant $tenant): bool
    {
        if (!$tenant->certificate_valid_until) {
            return false;
        }

        return Carbon::now()->lt($tenant->certificate_valid_until);
    }

    /**
     * Verifica quantos dias faltam para o certificado expirar
     *
     * @param Tenant $tenant
     * @return int|null
     */
    public function getDaysUntilExpiration(Tenant $tenant): ?int
    {
        if (!$tenant->certificate_valid_until) {
            return null;
        }

        return Carbon::now()->diffInDays($tenant->certificate_valid_until, false);
    }

    /**
     * Verifica se o certificado precisa de renovação (menos de 30 dias)
     *
     * @param Tenant $tenant
     * @return bool
     */
    public function needsRenewal(Tenant $tenant): bool
    {
        $daysRemaining = $this->getDaysUntilExpiration($tenant);

        if ($daysRemaining === null) {
            return true;
        }

        return $daysRemaining <= 30;
    }

    /**
     * Remove o certificado da empresa
     *
     * @param Tenant $tenant
     * @return bool
     */
    public function removeCertificate(Tenant $tenant): bool
    {
        try {
            if ($tenant->certificate_path) {
                Storage::disk('local')->delete($tenant->certificate_path);
            }

            $tenant->update([
                'certificate_path' => null,
                'certificate_password_encrypted' => null,
                'certificate_type' => null,
                'certificate_issuer' => null,
                'certificate_subject' => null,
                'certificate_serial_number' => null,
                'certificate_valid_from' => null,
                'certificate_valid_until' => null,
                'certificate_metadata' => null,
                'certificate_active' => false,
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Erro ao remover certificado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica se é certificado da cadeia ICP-Brasil
     *
     * @param array $certData
     * @return bool
     */
    private function isIcpBrasilCertificate(array $certData): bool
    {
        $issuer = $this->formatDN($certData['issuer']);

        // Verifica se contém autoridades certificadoras do ICP-Brasil
        $icpBrasilAuthorities = [
            'ICP-Brasil',
            'AC-Raiz',
            'Autoridade Certificadora',
            'Certisign',
            'Serasa',
            'Valid',
            'Safeweb',
        ];

        foreach ($icpBrasilAuthorities as $authority) {
            if (stripos($issuer, $authority) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determina se o certificado é A1 ou A3
     *
     * @param string $certificatePath
     * @return string
     */
    private function determineCertificateType(string $certificatePath): string
    {
        // A1: software (arquivo .pfx/.p12)
        // A3: hardware (token, smartcard) - mas também pode ser arquivo temporário

        // Por enquanto, consideramos arquivo como A1
        // A3 seria identificado por conexão com token/smartcard
        return 'A1';
    }

    /**
     * Formata o Distinguished Name (DN) para string legível
     *
     * @param array $dn
     * @return string
     */
    private function formatDN(array $dn): string
    {
        $parts = [];
        foreach ($dn as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $parts[] = "$key=$value";
        }
        return implode(', ', $parts);
    }

    /**
     * Extrai CNPJ do certificado
     *
     * @param array $certData
     * @return string|null
     */
    private function extractCNPJ(array $certData): ?string
    {
        // O CNPJ geralmente está no Subject Alternative Name ou no Subject
        $subject = $certData['subject']['CN'] ?? '';

        // Padrão: NOME:CNPJ ou similar
        if (preg_match('/(\d{14})/', $subject, $matches)) {
            return $matches[1];
        }

        // Verifica em extensões
        if (isset($certData['extensions']['subjectAltName'])) {
            $altName = $certData['extensions']['subjectAltName'];
            if (preg_match('/(\d{14})/', $altName, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Extrai extensões relevantes do certificado
     *
     * @param array $certData
     * @return array
     */
    private function extractRelevantExtensions(array $certData): array
    {
        $extensions = [];

        if (isset($certData['extensions'])) {
            $relevantExtensions = [
                'keyUsage',
                'extendedKeyUsage',
                'subjectAltName',
                'authorityInfoAccess',
                'crlDistributionPoints',
            ];

            foreach ($relevantExtensions as $ext) {
                if (isset($certData['extensions'][$ext])) {
                    $extensions[$ext] = $certData['extensions'][$ext];
                }
            }
        }

        return $extensions;
    }

    /**
     * Converte certificado com algoritmos legados para formato moderno
     * Necessário para certificados antigos com RC2-40-CBC ou 3DES no OpenSSL 3.x
     *
     * @param string $certificateContent Conteúdo do certificado original
     * @param string $password Senha do certificado
     * @return string|false Conteúdo do certificado convertido ou false em caso de erro
     */
    private function convertLegacyCertificate(string $certificateContent, string $password)
    {
        try {
            // Salva certificado original em arquivo temporário
            $tempInput = tempnam(sys_get_temp_dir(), 'cert_input_');
            $tempCert = tempnam(sys_get_temp_dir(), 'cert_');
            $tempKey = tempnam(sys_get_temp_dir(), 'key_');
            $tempOutput = tempnam(sys_get_temp_dir(), 'cert_output_');

            file_put_contents($tempInput, $certificateContent);

            // Extrai certificado usando comando openssl com flag -legacy
            $cmd1 = sprintf(
                'openssl pkcs12 -in "%s" -out "%s" -clcerts -nokeys -passin pass:%s -legacy 2>&1',
                $tempInput,
                $tempCert,
                escapeshellarg($password)
            );

            exec($cmd1, $output1, $return1);

            if ($return1 !== 0) {
                \Log::error('Erro ao extrair certificado na conversão', [
                    'output' => implode("\n", $output1)
                ]);
                @unlink($tempInput);
                @unlink($tempCert);
                @unlink($tempKey);
                @unlink($tempOutput);
                return false;
            }

            // Extrai chave privada usando comando openssl com flag -legacy
            $cmd2 = sprintf(
                'openssl pkcs12 -in "%s" -out "%s" -nocerts -nodes -passin pass:%s -legacy 2>&1',
                $tempInput,
                $tempKey,
                escapeshellarg($password)
            );

            exec($cmd2, $output2, $return2);

            if ($return2 !== 0) {
                \Log::error('Erro ao extrair chave privada na conversão', [
                    'output' => implode("\n", $output2)
                ]);
                @unlink($tempInput);
                @unlink($tempCert);
                @unlink($tempKey);
                @unlink($tempOutput);
                return false;
            }

            // Recria PFX com algoritmos modernos
            $cmd3 = sprintf(
                'openssl pkcs12 -export -out "%s" -in "%s" -inkey "%s" -passout pass:%s 2>&1',
                $tempOutput,
                $tempCert,
                $tempKey,
                escapeshellarg($password)
            );

            exec($cmd3, $output3, $return3);

            if ($return3 !== 0) {
                \Log::error('Erro ao criar novo PFX na conversão', [
                    'output' => implode("\n", $output3)
                ]);
                @unlink($tempInput);
                @unlink($tempCert);
                @unlink($tempKey);
                @unlink($tempOutput);
                return false;
            }

            // Lê o certificado convertido
            $convertedContent = file_get_contents($tempOutput);

            // Limpa arquivos temporários
            @unlink($tempInput);
            @unlink($tempCert);
            @unlink($tempKey);
            @unlink($tempOutput);

            return $convertedContent;

        } catch (\Exception $e) {
            \Log::error('Exceção ao converter certificado legado: ' . $e->getMessage());
            return false;
        }
    }
}
