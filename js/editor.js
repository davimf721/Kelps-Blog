document.addEventListener('DOMContentLoaded', function () {
    var textarea   = document.getElementById('content');
    var previewArea = document.getElementById('preview-area');
    var previewBtn  = document.getElementById('preview-toggle');
    var toolbar     = document.querySelector('.editor-toolbar');

    if (!textarea) return;

    // Toolbar actions
    var actions = {
        bold:    { wrap: '**', placeholder: 'texto em negrito' },
        italic:  { wrap: '_',  placeholder: 'texto em itálico' },
        heading: { prefix: '## ', placeholder: 'Título' },
        link:    { template: '[texto](url)' },
        image:   { template: '![alt](url-da-imagem)' },
        code:    { wrap: '`',  placeholder: 'código' },
    };

    if (toolbar) {
        toolbar.querySelectorAll('button[data-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var action = btn.dataset.action;
                var def    = actions[action];
                if (!def) return;

                var start  = textarea.selectionStart;
                var end    = textarea.selectionEnd;
                var sel    = textarea.value.slice(start, end);
                var before = textarea.value.slice(0, start);
                var after  = textarea.value.slice(end);
                var insert = '';

                if (def.template) {
                    insert = def.template;
                } else if (def.prefix) {
                    insert = def.prefix + (sel || def.placeholder);
                } else if (def.wrap) {
                    insert = def.wrap + (sel || def.placeholder) + def.wrap;
                }

                textarea.value = before + insert + after;
                textarea.focus();
                textarea.selectionStart = before.length + insert.length;
                textarea.selectionEnd   = before.length + insert.length;
            });
        });
    }

    // Preview toggle
    if (previewBtn && previewArea) {
        var isPreviewing = false;

        previewBtn.addEventListener('click', function () {
            isPreviewing = !isPreviewing;

            if (isPreviewing) {
                previewBtn.textContent = 'Editar';
                textarea.style.display = 'none';
                previewArea.style.display = 'block';

                fetch('/api/posts/preview', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ content: textarea.value }),
                })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { previewArea.innerHTML = d.html || ''; })
                    .catch(function () { previewArea.textContent = 'Erro ao carregar preview.'; });
            } else {
                previewBtn.textContent = 'Visualizar';
                textarea.style.display = '';
                previewArea.style.display = 'none';
            }
        });
    }
});
