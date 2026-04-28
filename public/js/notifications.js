/**
 * Notificações — deletar item
 */
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-delete-notif');
    if (!btn) return;

    const id = btn.dataset.id;

    try {
        const res = await fetch(`/api/notifications/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-Token': document.querySelector('input[name="csrf_token"]')?.value || '' },
            credentials: 'same-origin',
        });
        const data = await res.json();

        if (data.success) {
            btn.closest('.notification-item')?.remove();
        }
    } catch {
        console.error('Erro ao deletar notificação');
    }
});
