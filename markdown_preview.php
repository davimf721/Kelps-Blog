<?php
// Este arquivo renderiza markdown para preview em tempo real
require_once 'libs/Parsedown.php';

header('Content-Type: application/json');

// Verificar se o markdown foi enviado
if (!isset($_POST['markdown'])) {
    echo json_encode(['error' => 'Nenhum conteúdo markdown fornecido']);
    exit;
}

$markdown = $_POST['markdown'];

// Inicializar o Parsedown
$parsedown = new Parsedown();
$parsedown->setSafeMode(true); // Ativar modo seguro para prevenir XSS

// Renderizar o markdown para HTML
$html = $parsedown->text($markdown);

// Retornar o HTML renderizado
echo json_encode(['html' => $html]);
?>