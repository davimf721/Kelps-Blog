<?php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth.php';

// Iniciar buffer de output
ob_start();

function parse_size_to_bytes($size) {
    $size = trim((string)$size);
    if ($size === '') {
        return 0;
    }

    $unit = strtolower(substr($size, -1));
    $value = (float)$size;

    switch ($unit) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
            break;
    }

    return (int)$value;
}

function format_bytes_human($bytes) {
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 1, ',', '') . 'MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 0, ',', '') . 'KB';
    }

    return $bytes . 'B';
}

// Configurações de limite de upload
$app_file_limit = 10 * 1024 * 1024; // Limite funcional da aplicação por arquivo (10MB)
$php_upload_limit = parse_size_to_bytes(ini_get('upload_max_filesize'));
$php_post_limit = parse_size_to_bytes(ini_get('post_max_size'));
$is_railway = !empty(getenv('RAILWAY_ENVIRONMENT')) || !empty(getenv('RAILWAY_PUBLIC_DOMAIN'));

// MAX_REQUEST_BODY_SIZE pode ser definido como variável de ambiente no Railway
// Com nginx.conf customizado (client_max_body_size 15M) o limite sobe para 15MB
$proxy_request_limit = parse_size_to_bytes(getenv('MAX_REQUEST_BODY_SIZE') ?: '');
if ($proxy_request_limit <= 0) {
    // Assume o valor configurado no nginx.conf (15M) quando em Railway
    $proxy_request_limit = $is_railway ? (15 * 1024 * 1024) : PHP_INT_MAX;
}

$server_request_limit = min(
    $php_post_limit > 0 ? $php_post_limit : PHP_INT_MAX,
    $proxy_request_limit
);

$max_total_upload_size = $server_request_limit > 0 ? $server_request_limit : $app_file_limit;
$max_file_size = min($app_file_limit, $max_total_upload_size);

$max_file_size_text = format_bytes_human($max_file_size);
$max_total_upload_size_text = format_bytes_human($max_total_upload_size);

// Quando o body excede o limite, PHP recebe POST/FILES vazios
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    empty($_POST) &&
    empty($_FILES) &&
    !empty($_SERVER['CONTENT_LENGTH']) &&
    (int)$_SERVER['CONTENT_LENGTH'] > 0
) {
    $_SESSION['error'] = "O arquivo enviado excede o limite do servidor ({$max_total_upload_size_text}). Reduza o tamanho da imagem e tente novamente.";
    header("Location: edit_profile.php");
    exit();
}

$max_width = 1200;
$max_height = 1200;

// Verificar se o usuário está logado
if (!is_logged_in()) {
    $_SESSION['error'] = "Você precisa estar logado para acessar esta página.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Verificar GD disponível
$gd_available = function_exists('imagecreatetruecolor');

// Buscar informações atuais do usuário
$user_result = pg_query_params($dbconn, "SELECT username, email FROM users WHERE id = $1", [$user_id]);
$user_data = pg_fetch_assoc($user_result);
$current_username = $user_data['username'];
$current_email = $user_data['email'];

// Verificar se a tabela existe
$check_table = pg_query($dbconn, "SELECT to_regclass('public.user_profiles')");
$table_exists = (pg_fetch_result($check_table, 0, 0) !== NULL);

// Se a tabela não existe, criar
if (!$table_exists) {
    $create_profiles_table = "
    CREATE TABLE IF NOT EXISTS user_profiles (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id),
        profile_image TEXT DEFAULT 'images/default-profile.png',
        banner_image TEXT DEFAULT 'images/default-banner.png',
        bio TEXT DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT user_profiles_user_id_key UNIQUE (user_id)
    )";
    pg_query($dbconn, $create_profiles_table);
} else {
    // Verificar e ajustar colunas
    $alter_columns = "
        ALTER TABLE user_profiles 
        ALTER COLUMN profile_image TYPE TEXT,
        ALTER COLUMN banner_image TYPE TEXT
    ";
    pg_query($dbconn, $alter_columns);
    
    $check_constraint = pg_query($dbconn, "
        SELECT count(*) FROM pg_constraint 
        WHERE conname = 'user_profiles_user_id_key' 
        AND conrelid = 'user_profiles'::regclass
    ");
    
    $constraint_exists = (pg_fetch_result($check_constraint, 0, 0) > 0);
    
    if (!$constraint_exists) {
        $add_constraint = "
            ALTER TABLE user_profiles 
            ADD CONSTRAINT user_profiles_user_id_key UNIQUE (user_id)
        ";
        pg_query($dbconn, $add_constraint);
    }
}

// Buscar perfil atual
$profile_result = pg_query_params($dbconn, "SELECT * FROM user_profiles WHERE user_id = $1", [$user_id]);

// Valores padrão
$profile_image = "images/default-profile.png";
$banner_image = "images/default-banner.png";
$bio = "";

// Se existir, buscar dados
if ($profile_result && pg_num_rows($profile_result) > 0) {
    $profile_data = pg_fetch_assoc($profile_result);
    $profile_image = $profile_data['profile_image'];
    $banner_image = $profile_data['banner_image'];
    $bio = $profile_data['bio'];
}

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $update_password = false;
    $update_account = false;
    
    $updated_bio = trim($_POST['bio'] ?? '');
    
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    
    if ($new_username != $current_username || $new_email != $current_email) {
        $update_account = true;
        
        if ($new_username != $current_username) {
            $check_username = pg_query_params($dbconn, "SELECT id FROM users WHERE username = $1 AND id != $2", [$new_username, $user_id]);
            if (pg_num_rows($check_username) > 0) {
                $errors[] = "Este nome de usuário já está em uso. Por favor, escolha outro.";
            }
        }
        
        if ($new_email != $current_email) {
            $check_email = pg_query_params($dbconn, "SELECT id FROM users WHERE email = $1 AND id != $2", [$new_email, $user_id]);
            if (pg_num_rows($check_email) > 0) {
                $errors[] = "Este email já está em uso. Por favor, escolha outro.";
            }
        }
    }
    
    if (!empty($_POST['new_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $password_check = pg_query_params($dbconn, "SELECT password_hash FROM users WHERE id = $1", [$user_id]);
        $user = pg_fetch_assoc($password_check);
        
        if (!password_verify($current_password, $user['password_hash'])) {
            $errors[] = "Senha atual incorreta.";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "A nova senha e a confirmação não coincidem.";
        }
        
        if (strlen($new_password) < 8) {
            $errors[] = "A senha deve ter pelo menos 8 caracteres.";
        }
        
        $update_password = true;
    }
    
    // Upload da imagem de perfil
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] != UPLOAD_ERR_NO_FILE) {
        if ($_FILES['profile_image']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['profile_image']['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = "A imagem de perfil excede o limite permitido pelo servidor ({$max_file_size_text}).";
        } elseif ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Falha no upload da imagem de perfil. Tente novamente.";
        } elseif ($_FILES['profile_image']['size'] > $max_file_size) {
            $errors[] = "A imagem de perfil é muito grande. Tamanho máximo: {$max_file_size_text}.";
        } else {
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            $filename = $_FILES['profile_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if ($gd_available) {
                    $resized = resize_and_convert($_FILES['profile_image']['tmp_name'], 600, 600, 85);
                    if ($resized !== false) {
                        $profile_image = $resized;
                    } else {
                        // Fallback se resize falhar
                        $image_data = file_get_contents($_FILES['profile_image']['tmp_name']);
                        $profile_image = 'data:image/' . $ext . ';base64,' . base64_encode($image_data);
                    }
                } else {
                    $image_data = file_get_contents($_FILES['profile_image']['tmp_name']);
                    $profile_image = 'data:image/' . $ext . ';base64,' . base64_encode($image_data);
                }
            } else {
                $errors[] = "Tipo de arquivo não permitido para a imagem de perfil.";
            }
        }
    }
    
    // Upload da imagem de banner
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] != UPLOAD_ERR_NO_FILE) {
        if ($_FILES['banner_image']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['banner_image']['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = "O banner excede o limite permitido pelo servidor ({$max_file_size_text}).";
        } elseif ($_FILES['banner_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Falha no upload do banner. Tente novamente.";
        } elseif ($_FILES['banner_image']['size'] > $max_file_size) {
            $errors[] = "A imagem de banner é muito grande. Tamanho máximo: {$max_file_size_text}.";
        } else {
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            $filename = $_FILES['banner_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if ($gd_available) {
                    $resized = resize_and_convert($_FILES['banner_image']['tmp_name'], 1200, 400, 85);
                    if ($resized !== false) {
                        $banner_image = $resized;
                    } else {
                        // Fallback se resize falhar
                        $image_data = file_get_contents($_FILES['banner_image']['tmp_name']);
                        $banner_image = 'data:image/' . $ext . ';base64,' . base64_encode($image_data);
                    }
                } else {
                    $image_data = file_get_contents($_FILES['banner_image']['tmp_name']);
                    $banner_image = 'data:image/' . $ext . ';base64,' . base64_encode($image_data);
                }
            } else {
                $errors[] = "Tipo de arquivo não permitido para o banner.";
            }
        }
    }
    
    // Se não houver erros, atualizar perfil
    if (empty($errors)) {
        pg_query($dbconn, "BEGIN");
        $transaction_success = true;
        
        if ($update_account) {
            $update_user = pg_query_params($dbconn, 
                "UPDATE users SET username = $1, email = $2 WHERE id = $3",
                [$new_username, $new_email, $user_id]
            );
            
            if (!$update_user) {
                $transaction_success = false;
                $errors[] = "Erro ao atualizar informações da conta: " . pg_last_error($dbconn);
            } else {
                $_SESSION['username'] = $new_username;
            }
        }
        
        if ($update_password && $transaction_success) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password = pg_query_params($dbconn, 
                "UPDATE users SET password_hash = $1 WHERE id = $2",
                [$password_hash, $user_id]
            );
            
            if (!$update_password) {
                $transaction_success = false;
                $errors[] = "Erro ao atualizar senha: " . pg_last_error($dbconn);
            }
        }
        
        if ($transaction_success) {
            $check_profile = pg_query_params($dbconn, "SELECT id FROM user_profiles WHERE user_id = $1", [$user_id]);
            $profile_exists = ($check_profile && pg_num_rows($check_profile) > 0);
            
            if ($profile_exists) {
                $result = pg_query_params($dbconn, 
                    "UPDATE user_profiles SET profile_image = $1, banner_image = $2, bio = $3, updated_at = CURRENT_TIMESTAMP WHERE user_id = $4",
                    [$profile_image, $banner_image, $updated_bio, $user_id]
                );
            } else {
                $result = pg_query_params($dbconn,
                    "INSERT INTO user_profiles (user_id, profile_image, banner_image, bio, created_at, updated_at) VALUES ($1, $2, $3, $4, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    [$user_id, $profile_image, $banner_image, $updated_bio]
                );
            }
            
            if (!$result) {
                $transaction_success = false;
                $errors[] = "Erro ao atualizar perfil: " . pg_last_error($dbconn);
            }
        }
        
        if ($transaction_success) {
            pg_query($dbconn, "COMMIT");
            $_SESSION['success'] = "Perfil atualizado com sucesso!";
            header("Location: profile.php");
            exit();
        } else {
            pg_query($dbconn, "ROLLBACK");
            $_SESSION['error'] = implode("<br>", $errors);
            header("Location: edit_profile.php");
            exit();
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: edit_profile.php");
        exit();
    }
}

function resize_and_convert($file_path, $max_width = 300, $max_height = 300, $quality = 85) {
    // Verificar se o arquivo existe
    if (!file_exists($file_path)) {
        return false;
    }
    
    $image_info = @getimagesize($file_path);
    if ($image_info === false) {
        return false;
    }
    
    $mime_type = $image_info['mime'];
    
    switch ($mime_type) {
        case 'image/jpeg':
            $source = @imagecreatefromjpeg($file_path);
            break;
        case 'image/png':
            $source = @imagecreatefrompng($file_path);
            break;
        case 'image/gif':
            $source = @imagecreatefromgif($file_path);
            break;
        default:
            return false;
    }
    
    // Verificar se a imagem foi carregada
    if ($source === false) {
        return false;
    }
    
    $width = imagesx($source);
    $height = imagesy($source);
    
    if ($width > $height) {
        if ($width > $max_width) {
            $new_width = $max_width;
            $new_height = (int)(($height * $max_width) / $width);
        } else {
            $new_width = $width;
            $new_height = $height;
        }
    } else {
        if ($height > $max_height) {
            $new_height = $max_height;
            $new_width = (int)(($width * $max_height) / $height);
        } else {
            $new_width = $width;
            $new_height = $height;
        }
    }
    
    // Garantir dimensões mínimas
    $new_width = max(1, (int)$new_width);
    $new_height = max(1, (int)$new_height);
    
    $new_image = @imagecreatetruecolor($new_width, $new_height);
    if ($new_image === false) {
        imagedestroy($source);
        return false;
    }
    
    if ($mime_type == 'image/png') {
        imagecolortransparent($new_image, imagecolorallocate($new_image, 0, 0, 0));
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }
    
    imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    ob_start();
    
    switch ($mime_type) {
        case 'image/jpeg':
            imagejpeg($new_image, null, $quality);
            break;
        case 'image/png':
            $png_quality = ($quality - 100) / 11.11;
            $png_quality = round(abs($png_quality));
            imagepng($new_image, null, (int)$png_quality);
            break;
        case 'image/gif':
            imagegif($new_image);
            break;
    }
    
    $image_data = ob_get_clean();
    
    imagedestroy($source);
    imagedestroy($new_image);
    
    // Verificar se gerou dados
    if (empty($image_data)) {
        return false;
    }
    
    return 'data:' . $mime_type . ';base64,' . base64_encode($image_data);
}

// Definir variáveis para o header
$page_title = "Editar Perfil - Kelps Blog";
$current_page = 'edit_profile';

// Incluir o header
include __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="upload-flash upload-flash-error">
        <i class="fas fa-exclamation-triangle"></i>
        <span><?php echo $_SESSION['error']; ?></span>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="upload-flash upload-flash-success">
        <i class="fas fa-check-circle"></i>
        <span><?php echo $_SESSION['success']; ?></span>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="edit-profile-container">
    <div class="edit-profile-card">
        <div class="edit-profile-header">
            <h1><i class="fas fa-user-edit"></i> Editar Perfil</h1>
        </div>
        
        <div class="edit-profile-body">
            <div class="content-tabs">
                <button type="button" class="tab-button active" onclick="openTab('profile')">
                    <i class="fas fa-user"></i> Perfil
                </button>
                <button type="button" class="tab-button" onclick="openTab('account')">
                    <i class="fas fa-cog"></i> Conta
                </button>
                <button type="button" class="tab-button" onclick="openTab('security')">
                    <i class="fas fa-shield-alt"></i> Segurança
                </button>
            </div>
            
            <form action="edit_profile.php" method="post" enctype="multipart/form-data" id="edit-profile-form">
                <!-- Tab de Perfil (Imagens e Bio) -->
                <div id="profile-tab" class="tab-content active">
                    <div class="edit-section">
                        <h3 class="edit-section-title"><i class="fas fa-images"></i> Imagens do Perfil</h3>
                        
                        <div class="image-preview-container">
                            <div class="image-preview-box">
                                <label>Foto de Perfil Atual</label>
                                <div class="profile-preview" style="background-image: url('<?php echo htmlspecialchars($profile_image); ?>');"></div>
                                <div class="file-input-wrapper">
                                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                                    <div class="file-input-label">
                                        <i class="fas fa-camera"></i> Alterar Foto
                                    </div>
                                </div>
                            </div>
                            
                            <div class="image-preview-box">
                                <label>Banner Atual</label>
                                <div class="banner-preview" style="background-image: url('<?php echo htmlspecialchars($banner_image); ?>');"></div>
                                <div class="file-input-wrapper">
                                    <input type="file" id="banner_image" name="banner_image" accept="image/*">
                                    <div class="file-input-label">
                                        <i class="fas fa-image"></i> Alterar Banner
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="input-hint" style="text-align: center; margin-top: 10px;">
                            <i class="fas fa-info-circle"></i> Limite por arquivo: <?php echo htmlspecialchars($max_file_size_text); ?>. Limite total por envio: <?php echo htmlspecialchars($max_total_upload_size_text); ?>. Formatos: JPG, PNG, GIF
                        </p>
                    </div>
                    
                    <div class="edit-section">
                        <h3 class="edit-section-title"><i class="fas fa-pen"></i> Sobre Você</h3>
                        <div class="form-group">
                            <label for="bio">Biografia</label>
                            <textarea id="bio" name="bio" rows="5" placeholder="Conte um pouco sobre você, seus interesses e experiências..."><?php echo htmlspecialchars($bio); ?></textarea>
                            <p class="input-hint">Esta informação será exibida no seu perfil público.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Tab de Conta (Username e Email) -->
                <div id="account-tab" class="tab-content">
                    <div class="edit-section">
                        <h3 class="edit-section-title"><i class="fas fa-id-card"></i> Informações da Conta</h3>
                        
                        <div class="form-group">
                            <label for="username">Nome de Usuário</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($current_username); ?>" required>
                            <p class="input-hint">Seu nome de usuário será visível para todos os visitantes.</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_email); ?>" required>
                            <p class="input-hint">Seu email não será exibido publicamente.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Tab de Segurança (Alterar Senha) -->
                <div id="security-tab" class="tab-content">
                    <div class="edit-section">
                        <h3 class="edit-section-title"><i class="fas fa-lock"></i> Alterar Senha</h3>
                        
                        <div class="form-group">
                            <label for="current_password">Senha Atual</label>
                            <input type="password" id="current_password" name="current_password" placeholder="Digite sua senha atual">
                            <p class="input-hint">Necessária apenas para alterar sua senha.</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Nova Senha</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Digite a nova senha">
                            <p class="input-hint">Mínimo 8 caracteres. Use letras, números e símbolos.</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmar Nova Senha</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirme a nova senha">
                        </div>
                    </div>
                    
                    <div class="danger-zone">
                        <h3><i class="fas fa-exclamation-triangle"></i> Zona de Perigo</h3>
                        <p>Ações irreversíveis. Proceda com cautela.</p>
                        <a href="delete_account.php" class="btn-danger">
                            <i class="fas fa-user-times"></i> Excluir Minha Conta
                        </a>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <a href="profile.php" class="btn-cancel">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Complementos específicos do edit profile */
    .tab-content {
        display: none;
        padding: 20px 0;
    }

    .upload-flash {
        max-width: 980px;
        margin: 18px auto 0;
        padding: 12px 16px;
        border-radius: 10px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .upload-flash-error {
        background: rgba(220, 53, 69, 0.12);
        border: 1px solid rgba(220, 53, 69, 0.35);
        color: #8e1420;
    }

    .upload-flash-success {
        background: rgba(40, 167, 69, 0.12);
        border: 1px solid rgba(40, 167, 69, 0.35);
        color: #0f6b31;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .input-hint {
        font-size: 0.85rem;
        color: #666;
        margin-top: 6px;
    }
    
    /* Indicador de imagem alterada */
    .image-changed {
        border-color: #4ecdc4 !important;
        box-shadow: 0 0 20px rgba(78, 205, 196, 0.4) !important;
        position: relative;
    }
    
    .image-changed::after {
        content: '✓ Nova imagem';
        position: absolute;
        bottom: -25px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .profile-preview.image-changed::after {
        bottom: -30px;
    }
    
    .banner-preview.image-changed::after {
        bottom: -30px;
    }
    
    /* Campo alterado */
    .field-changed {
        border-color: #4ecdc4 !important;
        background: rgba(78, 205, 196, 0.1) !important;
    }
    
    /* Toast de feedback */
    .feedback-toast {
        position: fixed;
        bottom: 30px;
        right: 30px;
        padding: 15px 25px;
        border-radius: 12px;
        color: white;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 9999;
        transform: translateX(120%);
        transition: transform 0.3s ease;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    }
    
    .feedback-toast.show {
        transform: translateX(0);
    }
    
    .feedback-info {
        background: linear-gradient(135deg, #0e86ca, #4ecdc4);
    }
    
    .feedback-success {
        background: linear-gradient(135deg, #28a745, #20c997);
    }
    
    .feedback-error {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }
    
    /* Ajustes nas previews de imagem */
    .profile-preview,
    .banner-preview {
        position: relative;
        transition: all 0.3s ease;
    }
    
    .image-preview-box {
        margin-bottom: 30px;
    }
</style>

<script>
    const MAX_FILE_SIZE_BYTES = <?php echo (int)$max_file_size; ?>;
    const MAX_TOTAL_UPLOAD_BYTES = <?php echo (int)$max_total_upload_size; ?>;

    // Função para trocar de aba
    function openTab(tabName) {
        // Esconder todas as abas de conteúdo
        var tabs = document.getElementsByClassName("tab-content");
        for (var i = 0; i < tabs.length; i++) {
            tabs[i].classList.remove("active");
        }
        
        // Remover classe active de todos os botões de aba
        var tabButtons = document.getElementsByClassName("tab-button");
        for (var i = 0; i < tabButtons.length; i++) {
            tabButtons[i].classList.remove("active");
        }
        
        // Mostrar aba selecionada
        document.getElementById(tabName + "-tab").classList.add("active");
        
        // Ativar o botão correspondente
        event.currentTarget.classList.add("active");
    }
    
    // Preview para imagem de perfil
    document.getElementById('profile_image').addEventListener('change', function(e) {
        if (!checkFileSize(this, MAX_FILE_SIZE_BYTES)) return;
        if (!checkTotalUploadSize()) {
            this.value = '';
            return;
        }
        
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                // Atualizar preview da foto de perfil
                const preview = document.querySelector('.profile-preview');
                preview.style.backgroundImage = `url(${event.target.result})`;
                
                // Adicionar indicador visual de mudança
                preview.classList.add('image-changed');
                
                // Mostrar mensagem de feedback
                showFeedback('Foto de perfil selecionada! Salve para aplicar.', 'info');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Preview para banner
    document.getElementById('banner_image').addEventListener('change', function(e) {
        if (!checkFileSize(this, MAX_FILE_SIZE_BYTES)) return;
        if (!checkTotalUploadSize()) {
            this.value = '';
            return;
        }
        
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                // Atualizar preview do banner
                const preview = document.querySelector('.banner-preview');
                preview.style.backgroundImage = `url(${event.target.result})`;
                
                // Adicionar indicador visual de mudança
                preview.classList.add('image-changed');
                
                // Mostrar mensagem de feedback
                showFeedback('Banner selecionado! Salve para aplicar.', 'info');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Monitorar mudanças nos campos de texto
    document.querySelectorAll('#edit-profile-form input, #edit-profile-form textarea').forEach(function(input) {
        const originalValue = input.value;
        input.addEventListener('input', function() {
            if (this.value !== originalValue) {
                this.classList.add('field-changed');
            } else {
                this.classList.remove('field-changed');
            }
        });
    });
    
    // Função para mostrar feedback visual
    function showFeedback(message, type) {
        // Remover feedback anterior se existir
        const existingFeedback = document.querySelector('.feedback-toast');
        if (existingFeedback) {
            existingFeedback.remove();
        }
        
        const toast = document.createElement('div');
        toast.className = `feedback-toast feedback-${type}`;
        toast.innerHTML = `<i class="fas fa-${type === 'info' ? 'info-circle' : type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
        document.body.appendChild(toast);
        
        // Animar entrada
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Remover após 3 segundos
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Verificar senhas
    document.getElementById('edit-profile-form').addEventListener('submit', function(e) {
        if (!checkTotalUploadSize()) {
            e.preventDefault();
            return;
        }

        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const currentPassword = document.getElementById('current_password').value;
        
        // Se o usuário começou a preencher campos de senha
        if (newPassword || confirmPassword || currentPassword) {
            // Verificar se a senha atual foi preenchida
            if (!currentPassword) {
                e.preventDefault();
                alert('Por favor, insira sua senha atual para confirmar as alterações de senha.');
                openTab('security');
                return;
            }
            
            // Verificar se as senhas coincidem
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('A nova senha e a confirmação não coincidem.');
                openTab('security');
                return;
            }
            
            // Verificar tamanho mínimo da senha
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 8 caracteres.');
                openTab('security');
                return;
            }
        }
    });
    
    // Função para verificar o tamanho do arquivo
    function checkFileSize(fileInput, maxSizeBytes) {
        const file = fileInput.files[0];
        
        if (file && file.size > maxSizeBytes) {
            const maxSizeMB = (maxSizeBytes / (1024 * 1024)).toFixed(1);
            alert(`A imagem é muito grande! Por favor, selecione uma imagem menor que ${maxSizeMB}MB.`);
            fileInput.value = ''; // Limpar o input
            return false;
        }
        return true;
    }

    function checkTotalUploadSize() {
        const profile = document.getElementById('profile_image').files[0];
        const banner = document.getElementById('banner_image').files[0];
        const total = (profile ? profile.size : 0) + (banner ? banner.size : 0);

        if (total > MAX_TOTAL_UPLOAD_BYTES) {
            const maxMB = (MAX_TOTAL_UPLOAD_BYTES / (1024 * 1024)).toFixed(1);
            alert(`O envio total excede o limite do servidor (${maxMB}MB). Escolha imagens menores.`);
            return false;
        }

        return true;
    }
</script>

<?php
// Liberar o buffer apenas quando for seguro
ob_end_flush();
?>