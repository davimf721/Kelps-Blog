/**
 * Kelps Blog — JS principal
 * Upvotes, comentários AJAX, follow toggle, drawer/mobile nav
 */

// -----------------------------------------------------------------
// Utilitários
// -----------------------------------------------------------------

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.content;
    const input = document.querySelector('input[name="csrf_token"]');
    return input ? input.value : '';
}

async function apiPost(url, data = {}) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCsrfToken(),
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify(data),
    });
    return res.json();
}

async function apiDelete(url) {
    const res = await fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-Token': getCsrfToken(),
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
    });
    return res.json();
}

// -----------------------------------------------------------------
// Upvotes — delegação via bubbling
// -----------------------------------------------------------------

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.upvote-btn');
    if (!btn || btn.disabled) return;

    const postId = btn.dataset.postId;
    if (!postId) return;

    btn.disabled = true;

    try {
        const data = await apiPost(`/api/posts/${postId}/upvote`);

        if (data.error) {
            showToast(data.error, 'error');
            return;
        }

        const countEl = btn.querySelector('.upvote-count');
        if (countEl) countEl.textContent = data.upvotes_count;

        btn.classList.toggle('upvoted', data.upvoted);
    } catch {
        showToast('Erro ao processar voto.', 'error');
    } finally {
        btn.disabled = false;
    }
});

// -----------------------------------------------------------------
// Comentários via AJAX
// -----------------------------------------------------------------

const commentForm = document.getElementById('comment-form');
if (commentForm) {
    commentForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const postId  = commentForm.dataset.postId;
        const content = commentForm.querySelector('textarea[name="content"]').value.trim();

        if (!content) return;

        const btn = commentForm.querySelector('button[type="submit"]');
        btn.disabled = true;

        try {
            const data = await apiPost(`/api/posts/${postId}/comments`, { content });

            if (data.error) {
                showToast(data.error, 'error');
                return;
            }

            // Recarrega a seção de comentários
            location.reload();
        } catch {
            showToast('Erro ao enviar comentário.', 'error');
        } finally {
            btn.disabled = false;
        }
    });
}

// -----------------------------------------------------------------
// Deletar comentários
// -----------------------------------------------------------------

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-delete-comment');
    if (!btn) return;

    if (!confirm('Deletar este comentário?')) return;

    const commentId = btn.dataset.commentId;

    try {
        const data = await apiDelete(`/api/comments/${commentId}`);

        if (data.success) {
            document.getElementById(`comment-${commentId}`)?.remove();
        } else {
            showToast(data.error || 'Erro ao deletar.', 'error');
        }
    } catch {
        showToast('Erro ao deletar comentário.', 'error');
    }
});

// -----------------------------------------------------------------
// Follow toggle
// -----------------------------------------------------------------

document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-follow');
    if (!btn) return;

    const userId = btn.dataset.userId;
    btn.disabled = true;

    try {
        const data = await apiPost(`/api/users/${userId}/follow`);

        if (data.error) {
            showToast(data.error, 'error');
            return;
        }

        const icon = btn.querySelector('i');
        const span = btn.querySelector('span');

        if (data.following) {
            btn.classList.add('following');
            if (icon) icon.className = 'fas fa-user-check';
            if (span) span.textContent = 'Seguindo';
        } else {
            btn.classList.remove('following');
            if (icon) icon.className = 'fas fa-user-plus';
            if (span) span.textContent = 'Seguir';
        }

        // Atualiza contador se existir
        const counter = document.querySelector('.followers-count');
        if (counter) counter.textContent = data.followers_count;
    } catch {
        showToast('Erro ao processar.', 'error');
    } finally {
        btn.disabled = false;
    }
});

// -----------------------------------------------------------------
// Flash auto-dismiss
// -----------------------------------------------------------------

document.querySelectorAll('.flash').forEach((el) => {
    setTimeout(() => {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    }, 5000);
});

// -----------------------------------------------------------------
// Toast helper
// -----------------------------------------------------------------

function showToast(msg, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `flash flash-${type}`;
    toast.textContent = msg;
    toast.style.cssText = 'position:fixed;bottom:1rem;right:1rem;z-index:9999;max-width:320px';
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 500); }, 4000);
}
