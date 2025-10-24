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
            // Lê o conteúdo do certificado
            $certificateContent = file_get_contents($certificatePath);

            if (!$certificateContent) {
                return false;
            }

            // Tenta ler o certificado PFX/P12
            $certificates = [];
            $success = openssl_pkcs12_read($certificateContent, $certificates, $password);

            if (!$success) {
                return false;
            }

            // Extrai informações do certificado
            $certData = openssl_x509_parse($certificates['cert']);

            if (!$certData) {
                return false;
            }

            // Verifica se é certificado ICP-Brasil
            $isIcpBrasil = $this->isIcpBrasilCertificate($certData);

            if (!$isIcpBrasil) {
                return [
                    'valid' => false,
                    'error' => 'Certificado não é da cadeia ICP-Brasil'
                ];
            }

            // Determina o tipo de certificado (A1 ou A3)
            $certificateType = $this->determineCertificateType($certificatePath);

            // Extrai dados importantes
            $validFrom = Carbon::createFromTimestamp($certData['validFrom_time_t']);
            $validUntil = Carbon::createFromTimestamp($certData['validTo_time_t']);

            // Verifica se o certificado está válido
            $now = Carbon::now();
            $isValid = $now->between($validFrom, $validUntil);

            return [
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
            ];

        } catch (\Exception $e) {
            \Log::error('Erro ao validar certificado: ' . $e->getMessage());
            return false;
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
            $fileName = 'certificates/' . $tenant->id . '_' . time() . '.pfx';
            $certificateContent = file_get_contents($certificatePath);
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
}
