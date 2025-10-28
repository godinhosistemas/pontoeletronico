# 📱 Ponto Digital - PWA com Reconhecimento Facial

## 🚀 Visão Geral

O Ponto Digital é um Progressive Web App (PWA) completo para registro de ponto eletrônico com reconhecimento facial e código único do colaborador. O sistema funciona offline e sincroniza automaticamente quando a conexão é restaurada.

## ✨ Funcionalidades

### 🔐 Autenticação sem Login
- Sistema de código único por colaborador
- Sem necessidade de senha
- Validação via API REST

### 📸 Reconhecimento Facial
- Captura de foto em cada registro
- Armazenamento seguro das fotos
- Validação de presença facial
- Suporte a câmera frontal e traseira

### ⏰ Registro de Ponto Completo
- ✅ Registrar Entrada
- 🍽️ Iniciar Almoço
- ✅ Finalizar Almoço
- 🚪 Registrar Saída

### 📴 Modo Offline
- Funciona sem internet
- Sincronização automática em background
- IndexedDB para armazenamento local
- Service Worker para cache

### 📊 Visualização em Tempo Real
- Relógio digital sincronizado
- Exibição dos registros do dia
- Cálculo automático de horas trabalhadas
- Status atualizado em tempo real

## 🛠️ Configuração

### 1. Gerar Código Único para Colaboradores

Acesse o painel administrativo e gere códigos únicos para cada funcionário:

```bash
# Você pode gerar códigos automaticamente ou manualmente
# Exemplo de código: ABC123, DEF456, etc.
```

No cadastro de funcionários, adicione um campo `unique_code` único para cada colaborador.

### 2. Configurar Ícones do PWA

Os ícones do PWA precisam estar na pasta `public/images/`. Você pode:

**Opção 1: Gerar automaticamente**
1. Acesse: `http://seusite.com/images/generate-icons.html`
2. Os ícones serão baixados automaticamente

**Opção 2: Criar manualmente**
1. Crie ícones nos seguintes tamanhos:
   - 72x72, 96x96, 128x128, 144x144
   - 152x152, 192x192, 384x384, 512x512
2. Salve em `public/images/` com o nome `icon-{tamanho}x{tamanho}.png`

### 3. Configurar HTTPS (Obrigatório para PWA)

PWAs exigem HTTPS em produção. Configure:

```bash
# No servidor de produção
# Instale SSL/TLS (Let's Encrypt recomendado)
```

### 4. Permissões de Câmera

O navegador pedirá permissão para acessar a câmera na primeira vez.

## 📲 Como Usar

### Para Colaboradores

1. **Acesse o PWA**
   - Abra: `https://seusite.com/pwa/clock`
   - Ou adicione à tela inicial do celular

2. **Digite o Código Único**
   - Insira o código fornecido pelo RH
   - Ex: ABC123

3. **Posicione o Rosto**
   - Aguarde a detecção facial
   - Mantenha boa iluminação
   - Olhe para a câmera

4. **Registre o Ponto**
   - Clique no botão apropriado:
     - **Registrar Entrada**: Primeira ação do dia
     - **Iniciar Almoço**: Após registrar entrada
     - **Finalizar Almoço**: Após iniciar almoço
     - **Registrar Saída**: Última ação do dia

### Para Gestores

1. **Aprovar Pontos**
   - Acesse: `Admin > Aprovar Pontos`
   - Visualize as fotos capturadas
   - Aprove ou rejeite registros

2. **Gerar Relatórios**
   - Acesse: `Admin > Relatórios`
   - Filtre por período e funcionário
   - Exporte PDF ou Excel

## 🔧 Estrutura Técnica

### Arquivos Principais

```
/public
  /manifest.json          # Configuração do PWA
  /sw.js                  # Service Worker
  /images/                # Ícones do PWA

/resources/views/pwa
  /clock.blade.php        # Interface principal do PWA

/app/Http/Controllers/Api
  /PwaClockController.php # Controller da API

/routes
  /web.php                # Rotas PWA e API
```

### Endpoints da API

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/pwa/validate-code` | Valida código único |
| GET | `/api/pwa/today-entry/{id}` | Busca registro do dia |
| POST | `/api/pwa/register-clock` | Registra ponto com foto |
| POST | `/api/pwa/sync` | Sincroniza registros offline |

### Banco de Dados

**Tabela: employees**
- `unique_code`: Código único do colaborador (VARCHAR 20)
- `face_photo`: Caminho da última foto facial (VARCHAR 255)

**Tabela: time_entries**
- `clock_in`: Horário de entrada
- `clock_out`: Horário de saída
- `lunch_start`: Início do almoço
- `lunch_end`: Fim do almoço
- `ip_address`: IP do registro
- `status`: pending, approved, rejected

## 🔒 Segurança

### Validações Implementadas

1. **Código Único**
   - Verificação de existência
   - Validação de funcionário ativo
   - Validação de tenant ativo

2. **Fotos Faciais**
   - Máximo 5MB por foto
   - Apenas formatos de imagem
   - Armazenamento separado por tenant

3. **IP Tracking**
   - Registro do IP em cada marcação
   - Auditoria completa

4. **Validações de Fluxo**
   - Não pode sair sem entrar
   - Não pode finalizar almoço sem iniciar
   - Evita registros duplicados

## 📱 Instalação como App

### Android

1. Abra o site no Chrome
2. Toque em "⋮" (menu)
3. Selecione "Adicionar à tela inicial"
4. Confirme

### iOS

1. Abra o site no Safari
2. Toque no ícone de compartilhar
3. Selecione "Adicionar à Tela de Início"
4. Confirme

## 🐛 Solução de Problemas

### Câmera não funciona
- ✅ Verifique permissões do navegador
- ✅ Use HTTPS (obrigatório)
- ✅ Teste em outro navegador

### Código não aceito
- ✅ Verifique se está em maiúsculas
- ✅ Confirme com o RH o código correto
- ✅ Verifique se o funcionário está ativo

### Offline não sincroniza
- ✅ Verifique conexão com internet
- ✅ Aguarde alguns minutos
- ✅ Abra o app novamente

### PWA não instala
- ✅ Use HTTPS
- ✅ Verifique manifest.json
- ✅ Verifique console do navegador

## 📊 Tecnologias Utilizadas

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: Laravel 11
- **PWA**: Service Worker, Web App Manifest
- **Câmera**: getUserMedia API
- **Storage**: IndexedDB, LocalStorage
- **Cache**: Cache API
- **Notificações**: Push API (preparado)

## 🎯 Próximos Passos

- [ ] Implementar reconhecimento facial real (face-api.js)
- [ ] Adicionar geolocalização
- [ ] Push notifications para lembretes
- [ ] Biometria (fingerprint)
- [ ] QR Code alternativo
- [ ] Múltiplos idiomas
- [ ] Dashboard de estatísticas
- [ ] Integração com folha de pagamento

## 📞 Suporte

Para dúvidas ou problemas:
- Abra um issue no GitHub
- Entre em contato com o time de TI
- Consulte a documentação completa

## 📄 Licença

Sistema proprietário - Todos os direitos reservados

---

**Desenvolvido com ❤️ usando Laravel + PWA**
