-- Criação do banco de dados (descomente se estiver criando um novo banco)
-- CREATE DATABASE kelps_blog_db WITH ENCODING='UTF8';

-- Conectando ao banco de dados
-- \c kelps_blog_db

-- Limpar tabelas existentes caso seja necessária uma reinstalação (descomente com cuidado)
/*
DROP TABLE IF EXISTS post_upvotes CASCADE;
DROP TABLE IF EXISTS comments CASCADE;
DROP TABLE IF EXISTS posts CASCADE;
DROP TABLE IF EXISTS users CASCADE;
*/

-- Criação da tabela de usuários
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criação da tabela de posts
CREATE TABLE IF NOT EXISTS posts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    upvotes_count INTEGER DEFAULT 0,
    updated_at TIMESTAMP DEFAULT NULL
);

-- Criação da tabela de comentários
CREATE TABLE IF NOT EXISTS comments (
    id SERIAL PRIMARY KEY,
    post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criação da tabela de upvotes em posts
CREATE TABLE IF NOT EXISTS post_upvotes (
    id SERIAL PRIMARY KEY,
    post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(post_id, user_id)  -- Previne duplicação de upvotes
);

-- Índices para melhorar o desempenho
CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id);
CREATE INDEX IF NOT EXISTS idx_comments_post_id ON comments(post_id);
CREATE INDEX IF NOT EXISTS idx_comments_user_id ON comments(user_id);
CREATE INDEX IF NOT EXISTS idx_post_upvotes_post_id ON post_upvotes(post_id);
CREATE INDEX IF NOT EXISTS idx_post_upvotes_user_id ON post_upvotes(user_id);

-- Trigger para atualizar a contagem de upvotes quando um upvote é adicionado/removido
CREATE OR REPLACE FUNCTION update_upvotes_count()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        UPDATE posts SET upvotes_count = upvotes_count + 1 WHERE id = NEW.post_id;
    ELSIF TG_OP = 'DELETE' THEN
        UPDATE posts SET upvotes_count = upvotes_count - 1 WHERE id = OLD.post_id;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Criar o trigger
DROP TRIGGER IF EXISTS trigger_update_upvotes_count ON post_upvotes;
CREATE TRIGGER trigger_update_upvotes_count
AFTER INSERT OR DELETE ON post_upvotes
FOR EACH ROW EXECUTE FUNCTION update_upvotes_count();

-- Dados iniciais de exemplo (opcional)
-- Usuário de exemplo (senha = 'senha123')
INSERT INTO users (username, email, password_hash) 
VALUES ('Ghoul', 'ghoul@example.com', '$2y$10$hNUFUc5Ni0vS9Vt/g3KZH.c4ZZpyUE9OmZ70WVnuDdCnKzLNsDJtO')
ON CONFLICT (username) DO NOTHING;

-- Post de exemplo
INSERT INTO posts (user_id, title, content, upvotes_count)
SELECT id, 'Bem-vindo ao Kelps Blog!', 
'# Bem-vindo ao Kelps Blog!

Este é um blog moderno com suporte completo a Markdown.

## Algumas funcionalidades:

- Editor com suporte a Markdown
- Sistema de upvotes
- Comentários
- Perfis de usuários

Esperamos que você goste deste blog construído com PHP e PostgreSQL!', 5
FROM users WHERE username = 'Ghoul'
ON CONFLICT DO NOTHING;

-- Comentário de exemplo
INSERT INTO comments (post_id, user_id, content)
SELECT p.id, u.id, 'Excelente primeiro post! O suporte a Markdown é incrível.'
FROM posts p, users u
WHERE p.title = 'Bem-vindo ao Kelps Blog!' AND u.username = 'Ghoul'
ON CONFLICT DO NOTHING;

-- Configurar o upvote inicial para combinação post_id e user_id
-- (Isso só funciona se você já tiver IDs específicos para referência)
/*
INSERT INTO post_upvotes (post_id, user_id)
VALUES (1, 1)
ON CONFLICT (post_id, user_id) DO NOTHING;
*/