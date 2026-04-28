<?php
/** @var array $user */
$pageTitle = 'Editar perfil — Kelps Blog';
?>

<div class="page-header">
    <h1><i class="fas fa-user-edit"></i> Editar perfil</h1>
</div>

<?php if (!empty($error)): ?>
    <div class="message error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error, ENT_QUOTES) ?></div>
<?php endif; ?>

<!-- Dados do perfil -->
<form method="POST" action="/profile/edit" enctype="multipart/form-data" class="profile-form">
    <?= $csrf ?>

    <div class="form-section">
        <h2>Informações pessoais</h2>

        <div class="avatar-upload">
            <img src="<?= !empty($user['profile_picture'])
                ? '/uploads/avatars/' . htmlspecialchars($user['profile_picture'], ENT_QUOTES)
                : '/images/default-profile.png' ?>"
                 alt="Avatar" class="profile-avatar" id="avatar-preview">
            <label class="btn-secondary">
                <i class="fas fa-camera"></i> Trocar foto
                <input type="file" name="avatar" accept="image/*" style="display:none"
                       onchange="previewImage(this, 'avatar-preview')">
            </label>
        </div>

        <div class="form-group">
            <label>Banner</label>
            <input type="file" name="banner" accept="image/*">
        </div>

        <div class="form-group">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" rows="3" maxlength="500"
                      placeholder="Conte um pouco sobre você..."><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES) ?></textarea>
        </div>

        <div class="form-group">
            <label for="location">Localização</label>
            <input type="text" id="location" name="location" maxlength="100"
                   value="<?= htmlspecialchars($user['location'] ?? '', ENT_QUOTES) ?>"
                   placeholder="São Paulo, Brasil">
        </div>

        <div class="form-group">
            <label for="website">Website</label>
            <input type="url" id="website" name="website" maxlength="255"
                   value="<?= htmlspecialchars($user['website'] ?? '', ENT_QUOTES) ?>"
                   placeholder="https://seusite.com">
        </div>
    </div>

    <div class="form-actions">
        <a href="/profile/<?= $user['id'] ?>" class="btn-secondary">Cancelar</a>
        <button type="submit" class="btn-primary">
            <i class="fas fa-save"></i> Salvar
        </button>
    </div>
</form>

<!-- Alterar senha -->
<div class="form-section" style="margin-top: 2rem">
    <h2>Alterar senha</h2>
    <form method="POST" action="/profile/change-password">
        <?= $csrf ?>
        <div class="form-group">
            <label for="current_password">Senha atual</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">Nova senha</label>
            <input type="password" id="new_password" name="new_password" required minlength="8">
        </div>
        <button type="submit" class="btn-primary">
            <i class="fas fa-key"></i> Alterar senha
        </button>
    </form>
</div>

<!-- Zona de perigo -->
<div class="danger-zone">
    <h2><i class="fas fa-exclamation-triangle"></i> Zona de perigo</h2>
    <p>A exclusão da conta é permanente e irreversível.</p>
    <a href="/profile/delete" class="btn-danger">
        <i class="fas fa-trash"></i> Excluir minha conta
    </a>
</div>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById(previewId).src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
