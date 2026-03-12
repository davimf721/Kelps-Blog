<?php
/**
 * API para mudar idioma
 */

// Incluir sistema de tradução
require_once dirname(__DIR__) . '/config/translations.php';

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['language']) && in_array($data['language'], ['en', 'pt'])) {
            LanguageManager::setLanguage($data['language']);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'language' => $data['language']]);
            exit;
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => 'Invalid language']);
        exit;
    }
}

header('HTTP/1.1 405 Method Not Allowed');
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
