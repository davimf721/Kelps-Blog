# Kelps Blog

## Visão Geral
Kelps Blog é uma plataforma moderna de blog desenvolvida em PHP e PostgreSQL, que permite aos usuários criar, editar e gerenciar conteúdo com suporte completo a Markdown. O sistema oferece uma interface elegante e responsiva, projetada para uma experiência de escrita e leitura agradável.

## Funcionalidades

### Gerenciamento de Conteúdo
- **Editor Markdown completo** com pré-visualização em tempo real
- **Barra de ferramentas de formatação** para fácil inserção de elementos Markdown
- **Upload e gerenciamento de imagens** integrado aos posts
- **Pré-visualização** do resultado final antes da publicação
- **Tags e categorias** para melhor organização do conteúdo
- **Edição e exclusão** de posts pelo autor

### Sistema de Usuários
- **Registro e autenticação** de usuários
- **Perfis personalizáveis** com informações do autor
- **Níveis de permissão** (administrador, autor, leitor)
- **Proteção contra ataques** de injeção SQL e XSS

### Interface e Design
- **Design responsivo** adaptável a dispositivos móveis e desktop
- **Tema escuro** para melhor experiência de leitura
- **Layout customizável** através de CSS
- **Suporte para múltiplos idiomas**

### Recursos Técnicos
- **Rotas amigáveis** para melhor SEO
- **Armazenamento eficiente** de conteúdo no PostgreSQL
- **Compatibilidade com PHP 7.4+**
- **Validação de formulários** no lado cliente e servidor
- **Notificações em tempo real** para feedback do usuário


## Requisitos do Sistema
- PHP 7.4 ou superior
- PostgreSQL 11 ou superior
- Extensões PHP: pgsql, mbstring, json

## Instalação e Configuração

### Windows

1. **Instalar XAMPP (ou similar)**
   ```
   # Download XAMPP com PHP 7.4+
   https://www.apachefriends.org/download.html
   
   # Execute o instalador e selecione pelo menos Apache, PHP e PostgreSQL
   ```

2. **Instalar PostgreSQL**
   ```
   # Download PostgreSQL
   https://www.postgresql.org/download/windows/
   
   # Execute o instalador e anote a senha do superusuário
   ```

3. **Configurar Banco de Dados**
   ```
   # Abra pgAdmin e crie um novo banco de dados
   Nome: kelps_blog
   
   # Execute o script SQL fornecido no arquivo database.sql
   ```

4. **Configurar o Projeto**
   ```
   # Clone o repositório
   git clone https://github.com/seu-usuario/Kelps-Blog.git
   
   # Mova para o diretório htdocs
   move Kelps-Blog C:\xampp\htdocs\
   
   # Edite o arquivo de conexão com o banco
   Abra includes/db_connect.php e atualize as configurações
   ```

5. **Iniciar o Servidor**
   ```
   # Inicie o Apache no painel de controle do XAMPP
   # Acesse o blog em http://localhost/Kelps-Blog/
   ```

### Linux (Ubuntu/Debian)

1. **Instalar Dependências**
   ```bash
   # Atualizar repositórios
   sudo apt update
   
   # Instalar PHP, Apache e extensões
   sudo apt install apache2 php php-pgsql php-mbstring php-json
   
   # Instalar PostgreSQL
   sudo apt install postgresql postgresql-contrib
   ```

2. **Configurar PostgreSQL**
   ```bash
   # Trocar para o usuário postgres
   sudo -i -u postgres
   
   # Criar banco de dados
   createdb kelps_blog
   
   # Criar usuário para o blog
   psql -c "CREATE USER kelps_user WITH PASSWORD 'sua_senha';"
   
   # Conceder privilégios
   psql -c "GRANT ALL PRIVILEGES ON DATABASE kelps_blog TO kelps_user;"
   
   # Sair do usuário postgres
   exit
   
   # Importar o esquema do banco de dados
   psql -U kelps_user -d kelps_blog -f /caminho/para/database.sql
   ```

3. **Configurar o Projeto**
   ```bash
   # Clone o repositório
   git clone https://github.com/seu-usuario/Kelps-Blog.git
   
   # Mova para o diretório web
   sudo mv Kelps-Blog /var/www/html/
   
   # Defina permissões corretas
   sudo chown -R www-data:www-data /var/www/html/Kelps-Blog
   sudo chmod -R 755 /var/www/html/Kelps-Blog
   
   # Edite o arquivo de conexão com o banco
   sudo nano /var/www/html/Kelps-Blog/includes/db_connect.php
   ```

4. **Iniciar o Serviço**
   ```bash
   # Reiniciar Apache
   sudo systemctl restart apache2
   
   # Acesse o blog
   http://localhost/Kelps-Blog/
   ```

## Primeiro Acesso

1. Acesse o blog através do navegador
2. Vá para a página de registro (`/register.php`)
3. Crie uma conta de administrador
4. Faça login com suas credenciais
5. Comece a criar conteúdo!

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
