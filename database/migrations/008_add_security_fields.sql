-- Migration: 008_add_security_fields.sql
-- Descrição: Adiciona campos e tabelas de segurança

-- ============================================
-- Tabela de rate limits
-- ============================================
CREATE TABLE IF NOT EXISTS rate_limits (
    id SERIAL PRIMARY KEY,
    key VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_rate_limits_key ON rate_limits(key);
CREATE INDEX IF NOT EXISTS idx_rate_limits_created ON rate_limits(created_at);

-- ============================================
-- Campos de segurança na tabela users
-- ============================================

-- Tentativas de login falhas
ALTER TABLE users ADD COLUMN IF NOT EXISTS 
    failed_login_attempts INTEGER DEFAULT 0;

-- Bloqueio temporário da conta
ALTER TABLE users ADD COLUMN IF NOT EXISTS 
    locked_until TIMESTAMP NULL;

-- Último login
ALTER TABLE users ADD COLUMN IF NOT EXISTS 
    last_login_at TIMESTAMP NULL;

-- IP do último login
ALTER TABLE users ADD COLUMN IF NOT EXISTS 
    last_login_ip VARCHAR(45) NULL;

-- Data da última alteração de senha
ALTER TABLE users ADD COLUMN IF NOT EXISTS 
    password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- ============================================
-- Tabela de sessões ativas (para invalidação)
-- ============================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(128) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_user_sessions_user ON user_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_session ON user_sessions(session_id);

-- ============================================
-- Tabela de logs de auditoria
-- ============================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INTEGER,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_action ON audit_logs(action);
CREATE INDEX IF NOT EXISTS idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created ON audit_logs(created_at);

-- ============================================
-- Tabela de tokens de reset de senha
-- ============================================
CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(128) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_password_resets_token ON password_resets(token);
CREATE INDEX IF NOT EXISTS idx_password_resets_user ON password_resets(user_id);

-- ============================================
-- Função para limpar rate limits antigos (job periódico)
-- ============================================
CREATE OR REPLACE FUNCTION cleanup_old_rate_limits()
RETURNS void AS $$
BEGIN
    DELETE FROM rate_limits WHERE created_at < NOW() - INTERVAL '1 hour';
END;
$$ LANGUAGE plpgsql;

-- ============================================
-- Função para limpar sessões expiradas
-- ============================================
CREATE OR REPLACE FUNCTION cleanup_expired_sessions()
RETURNS void AS $$
BEGIN
    DELETE FROM user_sessions WHERE last_activity < NOW() - INTERVAL '24 hours';
END;
$$ LANGUAGE plpgsql;

-- ============================================
-- Comentários nas colunas
-- ============================================
COMMENT ON TABLE rate_limits IS 'Controle de rate limiting para proteção contra brute force';
COMMENT ON TABLE user_sessions IS 'Sessões ativas dos usuários para controle e invalidação';
COMMENT ON TABLE audit_logs IS 'Log de auditoria de ações sensíveis';
COMMENT ON TABLE password_resets IS 'Tokens de reset de senha';

COMMENT ON COLUMN users.failed_login_attempts IS 'Número de tentativas de login falhas consecutivas';
COMMENT ON COLUMN users.locked_until IS 'Timestamp até quando a conta está bloqueada';
COMMENT ON COLUMN users.last_login_at IS 'Data/hora do último login bem sucedido';
COMMENT ON COLUMN users.last_login_ip IS 'IP do último login bem sucedido';
