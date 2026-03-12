# Kelps Blog

Uma rede social/blog moderna desenvolvida em PHP e PostgreSQL.

## Visão Geral

Kelps Blog é uma plataforma de blog e rede social que permite aos usuários criar posts, seguir outros usuários, comentar e interagir através de upvotes. O sistema oferece uma interface elegante e responsiva com suporte completo a Markdown.

## Estrutura do Projeto

```
kelps-blog/
├── app/                        # Código principal da aplicação
│   ├── bootstrap.php           # Inicialização centralizada
│   ├── config/                 # Configurações (database, app)
│   ├── helpers/                # Funções auxiliares
│   │   ├── auth.php            # Autenticação
│   │   ├── db.php              # Conexão banco de dados
│   │   ├── notifications.php   # Sistema de notificações
│   │   └── EmailSender.php     # Envio de emails
│   ├── security/               # Classes de segurança
│   │   ├── Csrf.php            # Proteção CSRF
│   │   ├── InputSanitizer.php  # Sanitização de entrada
│   │   ├── RateLimiter.php     # Limitação de requisições
│   │   └── SessionManager.php  # Gerenciamento de sessão
│   └── views/partials/         # Templates reutilizáveis
│       ├── header.php
│       └── footer.php
│
├── pages/                      # Páginas organizadas por contexto
│   ├── auth/                   # Login, registro, logout
│   ├── posts/                  # Criar, editar, excluir posts
│   ├── profile/                # Perfil e notificações
│   ├── account/                # Gerenciamento de conta
│   └── api/                    # Endpoints JSON (AJAX)
│
├── admin/                      # Painel administrativo
│   ├── dashboard.php
│   ├── users.php
│   ├── posts.php
│   └── comments.php
│
├── public/                     # Assets públicos
│   ├── css/
│   ├── js/
│   └── images/
│
├── database/                   # Banco de dados
│   ├── schema.sql              # Schema completo
│   └── migrations/             # Migrations incrementais
│
├── storage/                    # Arquivos gerados (não versionados)
│   ├── uploads/
│   ├── logs/
│   └── cache/
│
├── docs/                       # Documentação
├── vendor/                     # Dependências (Composer)
│
├── includes/                   # [Compatibilidade] Proxies
├── config/                     # [Compatibilidade] Proxies
│
└── *.php (raiz)                # Redirecionadores para pages/
```

## Funcionalidades

### Usuários
- Registro e autenticação segura
- Perfis personalizáveis com foto e bio
- Sistema de seguir/deixar de seguir
- Notificações em tempo real

### Posts
- Editor Markdown com preview
- Upvotes e comentários
- Feed personalizado

### Segurança
- Proteção contra SQL Injection (prepared statements)
- Proteção CSRF
- Sanitização de entrada
- Rate limiting
- Sessões seguras

## Requisitos

- PHP 8.0+
- PostgreSQL 13+
- Composer

## Instalação

```bash
# Clonar repositório
git clone https://github.com/seu-usuario/kelps-blog.git

# Instalar dependências
composer install

# Configurar ambiente
cp .env.example .env
# Editar .env com suas configurações

# Criar banco de dados
psql -U postgres -f database/schema.sql

# Acessar no navegador
# http://localhost/kelps-blog
```

## Configuração

### Variáveis de Ambiente (.env)

```env
APP_ENV=development

DB_HOST=localhost
DB_PORT=5432
DB_NAME=kelps_blog
DB_USER=postgres
DB_PASS=sua_senha
```

### Railway

O projeto está configurado para deploy no Railway. As variáveis de ambiente são detectadas automaticamente.

## Utilização do Editor Markdown

O Kelps Blog suporta a sintaxe Markdown para criar posts ricos e bem formatados:

- Use `# Título` para cabeçalhos
- Use `**texto**` para negrito
- Use `*texto*` para itálico
- Use `[texto](URL)` para links
- Use `![alt](URL)` para imagens
- Use listas com `- item` ou `1. item`
- Blocos de código com ``` (triplo backtick)

A barra de ferramentas do editor facilita a inserção desses elementos sem precisar memorizar a sintaxe.

## Funcionalidade de Exclusão de Posts

Para excluir um post:
1. Acesse a página de edição do post
2. Clique no botão "Excluir Post" no final do formulário
3. Confirme a exclusão quando solicitado

## Contribuição

Contribuições são bem-vindas! Para contribuir:
1. Faça um fork do repositório
2. Crie uma branch para sua feature (`git checkout -b minha-nova-feature`)
3. Commit suas mudanças (`git commit -m 'Adiciona nova feature'`)
4. Push para a branch (`git push origin minha-nova-feature`)
5. Abra um Pull Request

## Licença

Este projeto é licenciado sob a licença MIT - veja o arquivo LICENSE para detalhes.

## Contato

Para questões ou suporte, entre em contato através de [davimoreiraf@gmail.com].
