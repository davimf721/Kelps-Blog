document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-delete-notif').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id   = btn.dataset.id;
            var item = btn.closest('.notification-item');
            if (!id || !item) return;

            fetch('/api/notifications/' + id, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) {
                    if (r.ok) {
                        item.style.transition = 'opacity 0.3s ease, height 0.3s ease';
                        item.style.opacity = '0';
                        setTimeout(function () { item.remove(); }, 300);
                    }
                })
                .catch(function () {});
        });
    });
});
