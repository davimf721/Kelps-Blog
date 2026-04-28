/**
 * Editor Markdown — toolbar + preview
 */
(function () {
    const textarea = document.getElementById('content');
    const previewArea = document.getElementById('preview-area');
    const previewToggle = document.getElementById('preview-toggle');

    if (!textarea) return;

    // ---- Toolbar ----
    document.querySelectorAll('[data-action]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const action = btn.dataset.action;
            const start = textarea.selectionStart;
            const end   = textarea.selectionEnd;
            const sel   = textarea.value.substring(start, end);

            const map = {
                bold:    { before: '**', after: '**', placeholder: 'texto em negrito' },
                italic:  { before: '_', after: '_', placeholder: 'texto em itálico' },
                heading: { before: '## ', after: '', placeholder: 'Título' },
                link:    { before: '[', after: '](url)', placeholder: 'texto do link' },
                image:   { before: '![', after: '](url-da-imagem)', placeholder: 'alt' },
                code:    { before: '`', after: '`', placeholder: 'código' },
            };

            const m = map[action];
            if (!m) return;

            const replacement = m.before + (sel || m.placeholder) + m.after;
            textarea.setRangeText(replacement, start, end, 'end');
            textarea.focus();
        });
    });

    // ---- Preview ----
    if (previewToggle && previewArea) {
        let showing = false;

        previewToggle.addEventListener('click', async () => {
            showing = !showing;

            if (showing) {
                previewToggle.textContent = 'Editar';
                previewArea.style.display = 'block';
                textarea.style.display    = 'none';

                // Preview via Parsedown no servidor
                try {
                    const res = await fetch('/api/posts/preview', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': document.querySelector('input[name="csrf_token"]')?.value || '',
                        },
                        body: JSON.stringify({ content: textarea.value }),
                    });
                    const d = await res.json();
                    previewArea.innerHTML = d.html || '';
                } catch {
                    previewArea.textContent = '[erro ao gerar preview]';
                }
            } else {
                previewToggle.textContent = 'Visualizar';
                previewArea.style.display = 'none';
                textarea.style.display    = 'block';
                textarea.focus();
            }
        });
    }
})();
