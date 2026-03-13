<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Verificar se foi enviado um arquivo
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nenhuma imagem foi enviada']);
    exit;
}

$file = $_FILES['image'];
$max_size = 5 * 1024 * 1024; // 5MB
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// Validar tamanho
if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Imagem muito grande (máximo 5MB)']);
    exit;
}

// Validar tipo MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de arquivo não permitido. Use: JPG, PNG, GIF ou WebP']);
    exit;
}

// Criar diretório se não existir
$upload_dir = __DIR__ . '/../../storage/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Gerar nome de arquivo seguro
$user_id = $_SESSION['user_id'];
$timestamp = time();
$random = bin2hex(random_bytes(4));
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = "img_{$user_id}_{$timestamp}_{$random}." . strtolower($ext);
$filepath = $upload_dir . $filename;

// Mover arquivo enviado
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar a imagem']);
    exit;
}

// Retornar URL relativa da imagem
$image_url = "/storage/uploads/" . $filename;

http_response_code(200);
echo json_encode([
    'success' => true,
    'url' => $image_url,
    'filename' => $filename
]);
