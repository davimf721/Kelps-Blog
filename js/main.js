// This file contains the main JavaScript functionality for the blog site, handling features such as displaying posts and managing user interactions.

document.addEventListener('DOMContentLoaded', function() {
    const postsContainer = document.getElementById('posts-container');

    async function fetchPosts() {
        try {
            const response = await fetch('posts.json'); // Certifique-se que o caminho está correto
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const posts = await response.json();
            displayPosts(posts);
        } catch (error) {
            console.error("Could not fetch posts:", error);
            if (postsContainer) {
                 postsContainer.innerHTML = '<p>Não foi possível carregar os posts. Tente novamente mais tarde.</p>';
            }
        }
    }

    function displayPosts(posts) {
        if (!postsContainer) return;

        postsContainer.innerHTML = '<h2>Blog Posts</h2>'; // Limpa e adiciona o título novamente

        if (posts.length === 0) {
            postsContainer.innerHTML += '<p>Nenhum post encontrado.</p>';
            return;
        }

        posts.forEach(post => {
            const postElement = document.createElement('article');
            postElement.className = 'post-summary';
            postElement.innerHTML = `
                <h3><a href="${post.contentFile}">${post.title}</a></h3>
                <p class="post-meta">Por: ${post.author}</p>
                <p>${post.summary}</p>
                <div class="post-stats">
                    <span>Upvotes: ${post.upvotes}</span> | 
                    <span>Comentários: ${post.commentsCount}</span>
                </div>
                <a href="${post.contentFile}" class="read-more">Leia mais...</a>
            `;
            postsContainer.appendChild(postElement);
        });
    }

    fetchPosts();
});