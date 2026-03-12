#!/bin/bash
# ============================================
# Script para rodar o projeto localmente
# ============================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Kelps Blog - Servidor de Desenvolvimento${NC}"
echo ""

# Verificar PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP não encontrado. Instale com: brew install php${NC}"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "✅ PHP $PHP_VERSION"

# Verificar extensão pgsql
if ! php -m | grep -q "pgsql"; then
    echo -e "${YELLOW}⚠️  Extensão pgsql não encontrada.${NC}"
    echo -e "   Instale com: brew install php && brew install postgresql"
    echo -e "   Ou: pecl install pgsql"
fi

# Verificar .env
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠️  Arquivo .env não encontrado.${NC}"
    echo -e "   Crie com: cp .env.example .env"
    echo -e "   E configure suas variáveis do Railway."
    exit 1
fi

echo -e "✅ Arquivo .env encontrado"

# Verificar composer
if [ -f "vendor/autoload.php" ]; then
    echo -e "✅ Dependências instaladas"
else
    echo -e "${YELLOW}⚠️  Instalando dependências...${NC}"
    composer install
fi

# Carregar variáveis de ambiente
export $(grep -v '^#' .env | grep -v '^\s*$' | xargs)

# Porta
PORT=${PORT:-8000}

echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}🌐 Servidor rodando em: http://localhost:$PORT${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "   Variáveis de banco:"
echo -e "   • DB_HOST: ${DB_HOST:-$PGHOST}"
echo -e "   • DB_PORT: ${DB_PORT:-$PGPORT}"
echo -e "   • DB_NAME: ${DB_NAME:-$PGDATABASE}"
echo ""
echo -e "   Pressione ${RED}Ctrl+C${NC} para parar"
echo ""

# Iniciar servidor PHP
php -S localhost:$PORT -t .
