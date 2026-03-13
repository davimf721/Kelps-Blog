#!/bin/sh
set -eu

# Enforce a single Apache MPM at runtime to avoid "More than one MPM loaded".
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

echo "[startup] Apache MPM symlinks:"
ls -1 /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null || true
echo "[startup] Apache loaded MPM modules:"
apache2ctl -M 2>/dev/null | grep -E 'mpm_(event|worker|prefork)_module' || true

exec apache2-foreground
