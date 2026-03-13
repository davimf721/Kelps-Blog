<?php
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../libs/Parsedown.php';

// Inicializar Parsedown para renderizar markdown
$parsedown = new Parsedown();
$parsedown->setSafeMode(true);

// Verificar se a coluna is_admin existe
$check_column = pg_query($dbconn, "SELECT column_name FROM information_schema.columns 
                                 WHERE table_name='users' AND column_name='is_admin'");
$has_is_admin = pg_num_rows($check_column) > 0;

// Construir a query base
$base_query = "SELECT p.id, p.title, p.content, p.created_at, p.user_id, p.upvotes_count,
               u.username AS author, COALESCE(u.is_admin, FALSE) as is_admin,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count";

// Se o usuário estiver logado, verificar se ele deu upvote
if (isset($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];
    $base_query .= ", (SELECT COUNT(*) > 0 FROM post_upvotes WHERE post_id = p.id AND user_id = $1) as user_has_upvoted";
} else {
    $base_query .= ", FALSE as user_has_upvoted";
}

$base_query .= " FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.id IS NOT NULL
                ORDER BY p.created_at DESC";

// Usar prepared statement se usuário logado
if (isset($_SESSION['user_id'])) {
    $result = pg_query_params($dbconn, $base_query, [$user_id]);
} else {
    $result = pg_query($dbconn, $base_query);
}
$posts = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $content = $row['content'];
        
        // Extrair primeira imagem do markdown (formato: ![alt](url))
        $first_image = null;
        if (preg_match('/!\[([^\]]*)\]\(([^)]+)\)/', $content, $matches)) {
            $first_image = $matches[2];
        }
        
        // Criar preview de texto removendo imagens e limitando caracteres
        $text_content = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '', $content);
        $text_content = strip_tags($text_content);
        $text_content = trim(preg_replace('/\s+/', ' ', $text_content));
        
        // Limitar preview a 150 caracteres
        if (strlen($text_content) > 150) {
            $text_content = substr($text_content, 0, 150) . '...';
        }
        
        $post = [
            'id' => $row['id'],
            'title' => htmlspecialchars($row['title']),
            'content' => htmlspecialchars($text_content),
            'content_html' => $parsedown->text(substr($row['content'], 0, 500)),
            'first_image' => $first_image,
            'created_at' => $row['created_at'],
            'author' => htmlspecialchars($row['author']),
            'user_id' => $row['user_id'],
            'upvotes_count' => $row['upvotes_count'] ?? 0,
            'comments_count' => $row['comments_count'] ?? 0,
            'is_admin' => $row['is_admin'] == 't',
            'user_has_upvoted' => $row['user_has_upvoted'] ?? false
        ];
        
        $posts[] = $post;
    }
}

header('Content-Type: application/json');
echo json_encode($posts);