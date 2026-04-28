document.addEventListener('DOMContentLoaded', function () {

    // Upvote buttons on post cards
    document.querySelectorAll('.upvote-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var postId = btn.dataset.postId;
            if (!postId) return;

            fetch('/api/posts/' + postId + '/upvote', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) return;
                    var countEl = btn.querySelector('.upvote-count');
                    if (countEl) countEl.textContent = data.upvotes ?? data.count ?? countEl.textContent;
                    btn.classList.toggle('upvoted', !!data.upvoted);
                })
                .catch(function () {});
        });
    });

});
