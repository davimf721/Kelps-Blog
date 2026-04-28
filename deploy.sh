#!/bin/bash
# =========================================================
# deploy.sh — Kelps Blog → Railway
# Uso: bash deploy.sh
# =========================================================
set -e

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_DIR"

echo ""
echo "╔══════════════════════════════════════════╗"
echo "║     Kelps Blog — Deploy para Railway     ║"
echo "╚══════════════════════════════════════════╝"
echo ""

# ── 1. Verificar dependências ────────────────────────────
echo "▶ Verificando dependências..."

if ! command -v composer &>/dev/null; then
    echo "  ✗ Composer não encontrado. Instale via https://getcomposer.org"
    exit 1
fi
echo "  ✓ Composer: $(composer --version --no-ansi 2>&1 | head -1)"

if ! command -v railway &>/dev/null; then
    echo "  ✗ Railway CLI não encontrado. Instalando..."
    npm install -g @railway/cli
fi
echo "  ✓ Railway CLI: $(railway --version 2>&1 | head -1)"

# ── 2. Instalar/atualizar dependências PHP ───────────────
echo ""
echo "▶ Instalando dependências PHP..."
composer install --no-dev --optimize-autoloader --no-interaction
echo "  ✓ Vendor gerado"

# ── 3. Commitar tudo ─────────────────────────────────────
echo ""
echo "▶ Preparando commit Git..."
git add -A

if git diff --staged --quiet; then
    echo "  • Nada novo para commitar"
else
    git commit -m "chore: deploy $(date '+%Y-%m-%d %H:%M')"
    echo "  ✓ Commit criado"
fi

# ── 4. Railway login (se necessário) ─────────────────────
echo ""
echo "▶ Verificando autenticação Railway..."
if ! railway whoami &>/dev/null; then
    echo "  → Faça login no Railway:"
    railway login
fi
echo "  ✓ Autenticado como: $(railway whoami 2>&1)"

# ── 5. Link do projeto (se necessário) ───────────────────
echo ""
echo "▶ Vinculando projeto Railway..."
if [ ! -f ".railway/config.json" ]; then
    echo "  → Selecione seu projeto Railway:"
    railway link
fi
echo "  ✓ Projeto vinculado"

# ── 6. Deploy ────────────────────────────────────────────
echo ""
echo "▶ Fazendo deploy..."
railway up --detach
echo "  ✓ Deploy enviado!"

# ── 7. Migrations ────────────────────────────────────────
echo ""
echo "▶ Rodando migrations em produção..."
railway run php database/migrate.php
echo "  ✓ Migrations executadas"

# ── 8. Health check ──────────────────────────────────────
echo ""
echo "▶ Verificando health check..."
APP_URL=$(railway variables get APP_URL 2>/dev/null || echo "")

if [ -n "$APP_URL" ]; then
    sleep 5
    HEALTH=$(curl -sf "$APP_URL/health" 2>/dev/null || echo "falhou")
    echo "  Health: $HEALTH"
else
    echo "  (Defina APP_URL nas variáveis do Railway para testar)"
fi

echo ""
echo "╔══════════════════════════════════════════╗"
echo "║           ✓ Deploy concluído!            ║"
echo "║                                          ║"
echo "║  Próximos passos:                        ║"
echo "║  1. Defina APP_URL, APP_ENV=production   ║"
echo "║     nas variáveis do Railway             ║"
echo "║  2. Linke o PostgreSQL ao seu serviço    ║"
echo "╚══════════════════════════════════════════╝"
echo ""
