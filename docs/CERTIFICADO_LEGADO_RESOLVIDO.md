# Solução para Certificados Digitais com Algoritmos Legados

## 🔍 Problema Identificado

O erro "Certificado inválido ou senha incorreta" estava ocorrendo devido ao uso de **algoritmos criptográficos antigos** (RC2-40-CBC ou 3DES) no certificado digital, que não são suportados por padrão no OpenSSL 3.x.

**Erro do OpenSSL**: `error:0308010C:digital envelope routines::unsupported`

## ✅ Solução Implementada

### Conversão Automática de Certificados Legados

O sistema agora **detecta e converte automaticamente** certificados com algoritmos antigos durante o upload:

1. **Tentativa inicial**: Sistema tenta ler o certificado normalmente
2. **Detecção de legado**: Se falhar com erro "unsupported", detecta que é certificado antigo
3. **Conversão automática**: Converte usando comandos OpenSSL com flag `-legacy`
4. **Validação**: Valida o certificado convertido
5. **Armazenamento**: Armazena o certificado convertido (não o original)

### Arquivos Modificados

- `app/Services/CertificateService.php`
  - Método `validateAndExtractInfo()`: Detecta e converte certificados legados
  - Método `storeCertificate()`: Armazena certificado convertido
  - Novo método `convertLegacyCertificate()`: Realiza a conversão

- `resources/views/livewire/admin/tenants/index.blade.php`
  - Método `uploadCertificate()`: Validação melhorada com mensagens de erro específicas

## 📋 Informações do Certificado Testado

**Certificado**: LIGANETT24168424.pfx
- **Empresa**: LIGANETT TELECOMUNICACOES LTDA
- **CNPJ**: 35.238.423/0001-29
- **Emissor**: AC Certisign RFB G5 (ICP-Brasil) ✓
- **Validade**: 06/11/2024 até 06/11/2025
- **Status**: ✅ VÁLIDO
- **⚠️ ATENÇÃO**: Expira em 10 dias! Renovar em breve.

## 🚀 Como Usar

### Opção 1: Upload Automático (Recomendado)

Simplesmente faça o upload do certificado original através da interface:
1. Acesse o menu de gerenciamento de empresas
2. Clique em "Adicionar Certificado" ou "🔐 Certificado"
3. Selecione o arquivo .pfx ou .p12
4. Digite a senha
5. O sistema converte automaticamente se necessário

### Opção 2: Conversão Manual

Se preferir converter manualmente antes do upload:

```bash
# Execute o script de conversão
php convert_certificate.php

# Ou use o comando OpenSSL diretamente:
# 1. Extrair certificado
openssl pkcs12 -in original.pfx -out cert.pem -clcerts -nokeys -passin pass:SENHA -legacy

# 2. Extrair chave privada
openssl pkcs12 -in original.pfx -out key.pem -nocerts -nodes -passin pass:SENHA -legacy

# 3. Recriar PFX com algoritmos modernos
openssl pkcs12 -export -out converted.pfx -in cert.pem -inkey key.pem -passout pass:SENHA
```

## 🔧 Requisitos Técnicos

- **PHP**: 8.0+ com extensão OpenSSL
- **OpenSSL**: 3.0+ instalado no sistema (comando `openssl` disponível)
- **Laravel**: 10+
- **Permissões**: Acesso para criar arquivos temporários em `sys_get_temp_dir()`

## 📝 Logs e Debug

O sistema registra detalhadamente cada etapa:

```bash
# Verificar logs de upload de certificado
tail -f storage/logs/laravel.log | grep -i certificado
```

Logs incluídos:
- Início da validação com tamanho do arquivo
- Tentativa de leitura do certificado
- Detecção de certificado legado
- Processo de conversão
- Validação ICP-Brasil
- Status de validade

## 🐛 Troubleshooting

### Erro: "openssl command not found"

**Solução**: Instale o OpenSSL:
- Windows: https://slproweb.com/products/Win32OpenSSL.html
- Linux: `sudo apt-get install openssl`

### Certificado ainda falha após conversão

**Possíveis causas**:
1. Senha incorreta
2. Certificado corrompido
3. Não é certificado ICP-Brasil
4. Certificado expirado

**Verificação**:
```bash
php test_certificate.php
```

### Permissão negada ao criar temporários

**Solução**:
```bash
# Linux
chmod 777 /tmp

# Windows
# Verificar permissões da pasta TEMP do usuário
```

## 📊 Melhorias Futuras

- [ ] Cache de certificados já convertidos
- [ ] Interface para visualizar detalhes do certificado antes do upload
- [ ] Notificação automática de certificados próximos ao vencimento
- [ ] Suporte a múltiplos certificados por empresa
- [ ] Backup automático de certificados antigos antes da substituição

## 🔒 Segurança

- ✅ Certificados armazenados criptografados
- ✅ Senhas criptografadas com `Crypt::encryptString()`
- ✅ Arquivos temporários removidos após conversão
- ✅ Validação ICP-Brasil obrigatória
- ✅ Verificação de validade do certificado

## 📚 Referências

- [OpenSSL Legacy Provider](https://www.openssl.org/docs/man3.0/man7/migration_guide.html)
- [ICP-Brasil](https://www.gov.br/iti/pt-br/assuntos/icp-brasil)
- [PHP OpenSSL Functions](https://www.php.net/manual/en/ref.openssl.php)

---

**Data**: 2025-10-27
**Desenvolvedor**: Claude Code
**Status**: ✅ Implementado e Testado
