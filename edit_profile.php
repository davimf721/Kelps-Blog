<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

// Iniciar buffer de output
ob_start();

// Configurações de tamanho máximo
$max_file_size = 5 * 1024 * 1024; // 5MB em bytes
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
$user_query = "SELECT username, email FROM users WHERE id = $user_id";
$user_result = pg_query($dbconn, $user_query);
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
$profile_query = "SELECT * FROM user_profiles WHERE user_id = $user_id";
$profile_result = pg_query($dbconn, $profile_query);

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
    
    $updated_bio = pg_escape_string($dbconn, $_POST['bio']);
    
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    
    if ($new_username != $current_username || $new_email != $current_email) {
        $update_account = true;
        
        if ($new_username != $current_username) {
            $check_username = pg_query($dbconn, "SELECT id FROM users WHERE username = '$new_username' AND id != $user_id");
            if (pg_num_rows($check_username) > 0) {
                $errors[] = "Este nome de usuário já está em uso. Por favor, escolha outro.";
            }
        }
        
        if ($new_email != $current_email) {
            $check_email = pg_query($dbconn, "SELECT id FROM users WHERE email = '$new_email' AND id != $user_id");
            if (pg_num_rows($check_email) > 0) {
                $errors[] = "Este email já está em uso. Por favor, escolha outro.";
            }
        }
    }
    
    if (!empty($_POST['new_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $password_check = pg_query($dbconn, "SELECT password_hash FROM users WHERE id = $user_id");
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
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        if ($_FILES['profile_image']['size'] > $max_file_size) {
            $errors[] = "A imagem de perfil é muito grande. Tamanho máximo: 5MB.";
        } else {
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            $filename = $_FILES['profile_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if ($gd_available) {
                    $profile_image = resize_and_convert($_FILES['profile_image']['tmp_name'], 600, 600, 85);
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
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
        if ($_FILES['banner_image']['size'] > $max_file_size) {
            $errors[] = "A imagem de banner é muito grande. Tamanho máximo: 5MB.";
        } else {
            $allowed = array('jpg', 'jpeg', 'png', 'gif');
            $filename = $_FILES['banner_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if ($gd_available) {
                    $banner_image = resize_and_convert($_FILES['banner_image']['tmp_name'], 1200, 400, 85);
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
            $update_user = pg_query($dbconn, "
                UPDATE users 
                SET username = '$new_username', 
                    email = '$new_email' 
                WHERE id = $user_id
            ");
            
            if (!$update_user) {
                $transaction_success = false;
                $errors[] = "Erro ao atualizar informações da conta: " . pg_last_error($dbconn);
            } else {
                $_SESSION['username'] = $new_username;
            }
        }
        
        if ($update_password && $transaction_success) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password = pg_query($dbconn, "
                UPDATE users 
                SET password_hash = '$password_hash' 
                WHERE id = $user_id
            ");
            
            if (!$update_password) {
                $transaction_success = false;
                $errors[] = "Erro ao atualizar senha: " . pg_last_error($dbconn);
            }
        }
        
        if ($transaction_success) {
            $profile_image_esc = pg_escape_string($dbconn, $profile_image);
            $banner_image_esc = pg_escape_string($dbconn, $banner_image);
            
            $check_profile = pg_query($dbconn, "SELECT id FROM user_profiles WHERE user_id = $user_id");
            $profile_exists = ($check_profile && pg_num_rows($check_profile) > 0);
            
            if ($profile_exists) {
                $update_query = "
                    UPDATE user_profiles 
                    SET profile_image = '$profile_image_esc',
                        banner_image = '$banner_image_esc',
                        bio = '$updated_bio',
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = $user_id
                ";
            } else {
                $update_query = "
                    INSERT INTO user_profiles (user_id, profile_image, banner_image, bio, created_at, updated_at)
                    VALUES ($user_id, '$profile_image_esc', '$banner_image_esc', '$updated_bio', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ";
            }
            
            $result = pg_query($dbconn, $update_query);
            
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
    $image_info = getimagesize($file_path);
    $mime_type = $image_info['mime'];
    
    switch ($mime_type) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($file_path);
            break;
        case 'image/png':
            $source = imagecreatefrompng($file_path);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($file_path);
            break;
        default:
            return false;
    }
    
    $width = imagesx($source);
    $height = imagesy($source);
    
    if ($width > $height) {
        if ($width > $max_width) {
            $new_width = $max_width;
            $new_height = ($height * $max_width) / $width;
        } else {
            $new_width = $width;
            $new_height = $height;
        }
    } else {
        if ($height > $max_height) {
            $new_height = $max_height;
            $new_width = ($width * $max_height) / $height;
        } else {
            $new_width = $width;
            $new_height = $height;
        }
    }
    
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
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
            imagepng($new_image, null, $png_quality);
            break;
        case 'image/gif':
            imagegif($new_image);
            break;
    }
    
    $image_data = ob_get_clean();
    
    imagedestroy($source);
    imagedestroy($new_image);
    
    return 'data:' . $mime_type . ';base64,' . base64_encode($image_data);
}

// Definir variáveis para o header
$page_title = "Editar Perfil - Kelps Blog";
$current_page = 'edit_profile';

// Incluir o header
include 'includes/header.php';
?>

<div class="container edit-profile-container">
    <h2><i class="fas fa-user-edit"></i> Editar Perfil</h2>
    
    <div class="tabs">
        <div class="tab active" onclick="openTab('profile')">
            <i class="fas fa-user"></i> Perfil
        </div>
        <div class="tab" onclick="openTab('account')">
            <i class="fas fa-cog"></i> Conta
        </div>
        <div class="tab" onclick="openTab('security')">
            <i class="fas fa-shield-alt"></i> Segurança
        </div>
    </div>
    
    <form action="edit_profile.php" method="post" enctype="multipart/form-data" id="edit-profile-form">
        <!-- Tab de Perfil (Imagens e Bio) -->
        <div id="profile-tab" class="tab-content active">
            <div class="current-images">
                <div class="image-preview">
                    <h3>Foto de Perfil Atual</h3>
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Foto de Perfil" class="profile-pic-preview">
                </div>
                
                <div class="image-preview">
                    <h3>Banner Atual</h3>
                    <img src="<?php echo htmlspecialchars($banner_image); ?>" alt="Banner" class="banner-pic-preview">
                </div>
            </div>
            
            <div class="form-group">
                <label for="profile_image">Nova Foto de Perfil:</label>
                <input type="file" id="profile_image" name="profile_image" accept="image/*">
                <p class="file-size-note">Tamanho máximo: 5MB. Formatos aceitos: JPG, PNG, GIF</p>
                <div id="profile-preview-container" style="display: none; margin-top: 10px;">
                    <h4>Prévia:</h4>
                    <img id="profile-preview" class="preview profile-pic-preview" src="#" alt="Prévia da imagem">
                </div>
            </div>
            
            <div class="form-group">
                <label for="banner_image">Novo Banner:</label>
                <input type="file" id="banner_image" name="banner_image" accept="image/*">
                <p class="file-size-note">Tamanho máximo: 5MB. Formatos aceitos: JPG, PNG, GIF</p>
                <div id="banner-preview-container" style="display: none; margin-top: 10px;">
                    <h4>Prévia:</h4>
                    <img id="banner-preview" class="preview banner-pic-preview" src="#" alt="Prévia da imagem">
                </div>
            </div>
            
            <div class="form-group">
                <label for="bio">Biografia:</label>
                <textarea id="bio" name="bio" rows="4" placeholder="Conte um pouco sobre você..."><?php echo htmlspecialchars($bio); ?></textarea>
                <p class="file-size-note">Descreva seus interesses, experiência ou qualquer coisa interessante sobre você.</p>
            </div>
        </div>
        
        <!-- Tab de Conta (Username e Email) -->
        <div id="account-tab" class="tab-content">
            <div class="form-group">
                <label for="username">Nome de Usuário:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($current_username); ?>" required>
                <p class="file-size-note">O nome de usuário será visível para todos os visitantes do blog.</p>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_email); ?>" required>
                <p class="file-size-note">Seu email não será exibido publicamente.</p>
            </div>
        </div>
        
        <!-- Tab de Segurança (Alterar Senha) -->
        <div id="security-tab" class="tab-content">
            <div class="form-group">
                <label for="current_password">Senha Atual:</label>
                <input type="password" id="current_password" name="current_password">
                <p class="file-size-note">Somente necessária se desejar alterar sua senha.</p>
            </div>
            
            <div class="form-group">
                <label for="new_password">Nova Senha:</label>
                <input type="password" id="new_password" name="new_password">
                <div class="password-rules">
                    <p>A senha deve ter pelo menos 8 caracteres.</p>
                    <p>Recomendamos incluir letras maiúsculas, minúsculas, números e símbolos.</p>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Nova Senha:</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            
            <!-- Zona de Perigo -->
            <div class="danger-zone">
                <h3><i class="fas fa-exclamation-triangle"></i> Zona de Perigo</h3>
                <p>As ações abaixo são irreversíveis. Use com extrema cautela.</p>
                <a href="delete_account.php" class="danger-button">
                    <i class="fas fa-user-times"></i> Excluir Minha Conta
                </a>
                <p class="danger-note">Esta ação excluirá permanentemente sua conta e todos os dados associados.</p>
            </div>
        </div>
        
        <div class="form-group form-actions">
            <button type="submit" class="submit-button">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
            <a href="profile.php" class="secondary-button">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<style>
.edit-profile-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 30px;
    background-color: #3a3a3a;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.edit-profile-container h2 {
    text-align: center;
    margin-bottom: 30px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.current-images {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.image-preview {
    text-align: center;
    background: rgba(0, 0, 0, 0.2);
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #555;
}

.image-preview h3 {
    margin-bottom: 15px;
    color: #fff;
    font-size: 1.1rem;
}

.image-preview img {
    max-width: 100%;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.profile-pic-preview {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
}

.banner-pic-preview {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.preview {
    margin-top: 10px;
    max-width: 100%;
    max-height: 200px;
}

.form-group input[type="file"] {
    padding: 10px;
    border: 1px solid #555;
    border-radius: 4px;
    background-color: #4f4f4f;
    color: #ffffff;
    width: 100%;
}

.file-size-note {
    font-size: 0.8em;
    color: #ccc;
    margin-top: 5px;
}

/* Novas classes para as abas */
.tabs {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 2px solid #555;
}

.tab {
    padding: 10px 20px;
    cursor: pointer;
    background-color: #333;
    color: #ccc;
    border-radius: 5px 5px 0 0;
    margin-right: 5px;
    transition: all 0.3s;
}

.tab.active {
    background-color: #2196F3;
    color: white;
}

.tab-content {
    display: none;
    padding: 20px 0;
}

.tab-content.active {
    display: block;
}

.password-rules {
    font-size: 0.85em;
    color: #aaa;
    margin-top: 5px;
    padding: 5px 10px;
    background-color: #333;
    border-radius: 4px;
}
</style>

<script>
    // Função para trocar de aba
    function openTab(tabName) {
        // Esconder todas as abas
        var tabs = document.getElementsByClassName("tab-content");
        for (var i = 0; i < tabs.length; i++) {
            tabs[i].classList.remove("active");
        }
        
        // Remover classe active de todos os botões de aba
        var tabButtons = document.getElementsByClassName("tab");
        for (var i = 0; i < tabButtons.length; i++) {
            tabButtons[i].classList.remove("active");
        }
        
        // Mostrar aba selecionada e ativar botão correspondente
        document.getElementById(tabName + "-tab").classList.add("active");
        
        // Encontrar e ativar o botão da aba
        for (var i = 0; i < tabButtons.length; i++) {
            if (tabButtons[i].textContent.toLowerCase().includes(tabName.toLowerCase())) {
                tabButtons[i].classList.add("active");
            }
        }
    }
    
    // Preview para imagens selecionadas
    document.getElementById('profile_image').addEventListener('change', function(e) {
        if (!checkFileSize(this, 5)) return;
        
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('profile-preview').src = event.target.result;
                document.getElementById('profile-preview-container').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
    
    document.getElementById('banner_image').addEventListener('change', function(e) {
        if (!checkFileSize(this, 5)) return;
        
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('banner-preview').src = event.target.result;
                document.getElementById('banner-preview-container').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Verificar senhas
    document.getElementById('edit-profile-form').addEventListener('submit', function(e) {
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
    function checkFileSize(fileInput, maxSizeMB) {
        const maxSizeBytes = maxSizeMB * 1024 * 1024;
        const file = fileInput.files[0];
        
        if (file && file.size > maxSizeBytes) {
            alert(`A imagem é muito grande! Por favor, selecione uma imagem menor que ${maxSizeMB}MB.`);
            fileInput.value = ''; // Limpar o input
            return false;
        }
        return true;
    }
</script>

<?php
// Liberar o buffer apenas quando for seguro
ob_end_flush();
?>