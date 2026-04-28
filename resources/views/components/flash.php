<?php
/** @var array $flash ['success', 'error', 'info'] */
if (!empty($flash['success'])): ?>
    <div class="flash flash-success" role="alert">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($flash['success'], ENT_QUOTES) ?>
    </div>
<?php endif; ?>

<?php if (!empty($flash['error'])): ?>
    <div class="flash flash-error" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($flash['error'], ENT_QUOTES) ?>
    </div>
<?php endif; ?>

<?php if (!empty($flash['info'])): ?>
    <div class="flash flash-info" role="alert">
        <i class="fas fa-info-circle"></i>
        <?= htmlspecialchars($flash['info'], ENT_QUOTES) ?>
    </div>
<?php endif; ?>
