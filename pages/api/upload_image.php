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
if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nenhuma imagem foi enviada']);
    exit;
}

$file = $_FILES['image'];

// Verificar erros de upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'Arquivo excede max_upload_size',
        UPLOAD_ERR_FORM_SIZE => 'Arquivo excede formulário',
        UPLOAD_ERR_PARTIAL => 'Upload incompleto',
        UPLOAD_ERR_NO_FILE => 'Nenhum arquivo',
        UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário ausente',
        UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever arquivo',
        UPLOAD_ERR_EXTENSION => 'Extensão bloqueada',
    ];
    
    $msg = $error_messages[$file['error']] ?? 'Erro desconhecido ao fazer upload';
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

$max_size = 5 * 1024 * 1024; // 5MB
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Validar tamanho
if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Imagem muito grande (máximo 5MB)']);
    exit;
}

// Validar extensão do arquivo
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_extensions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de arquivo não permitido. Use: JPG, PNG, GIF ou WebP']);
    exit;
}

// Validar se é uma imagem real (getimagesize)
$image_check = @getimagesize($file['tmp_name']);
if ($image_check === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Arquivo não é uma imagem válida']);
    exit;
}

// Criar diretório se não existir
$upload_dir = __DIR__ . '/../../storage/uploads/';
if (!is_dir($upload_dir)) {
    if (!@mkdir($upload_dir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao criar diretório de upload']);
        exit;
    }
}

// Verificar permissões de escrita
if (!is_writable($upload_dir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Diretório de upload sem permissão de escrita']);
    exit;
}

// Gerar nome de arquivo seguro
$user_id = $_SESSION['user_id'];
$timestamp = time();
$random = bin2hex(random_bytes(4));
$filename = "img_{$user_id}_{$timestamp}_{$random}." . strtolower($ext);
$filepath = $upload_dir . $filename;

// Mover arquivo enviado
if (!@move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar a imagem no servidor']);
    exit;
}

// Definir permissões do arquivo
@chmod($filepath, 0644);

// Retornar URL relativa da imagem
$image_url = "/storage/uploads/" . $filename;

http_response_code(200);
echo json_encode([
    'success' => true,
    'url' => $image_url,
    'filename' => $filename
]);
