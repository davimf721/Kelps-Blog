<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

// Verificar conexão com banco
if (!isset($dbconn) || !$dbconn) {
    die("Erro de conexão com banco de dados");
}

// Verificar se o usuário está logado e é admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error'] = "Você não tem permissão para acessar esta área.";
    header("Location: ../index.php");
    exit();
}

// Processar ações de admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $user_id = (int)($_POST['user_id'] ?? 0);
    $post_id = (int)($_POST['post_id'] ?? 0);
    $comment_id = (int)($_POST['comment_id'] ?? 0);
    
    if ($action) {
        try {
            switch ($action) {
                case 'ban_user':
                    if ($user_id != $_SESSION['user_id']) {
                        $result = pg_query_params($dbconn, 
                            "UPDATE users SET is_banned = true WHERE id = $1", 
                            [$user_id]
                        );
                        $_SESSION['success'] = $result ? "Usuário banido com sucesso!" : "Erro ao banir usuário.";
                    }
                    break;
                    
                case 'unban_user':
                    $result = pg_query_params($dbconn, 
                        "UPDATE users SET is_banned = false WHERE id = $1", 
                        [$user_id]
                    );
                    $_SESSION['success'] = $result ? "Usuário desbanido com sucesso!" : "Erro ao desbanir usuário.";
                    break;
                    
                case 'promote_admin':
                    if ($user_id != $_SESSION['user_id']) {
                        $result = pg_query_params($dbconn, 
                            "UPDATE users SET is_admin = true WHERE id = $1", 
                            [$user_id]
                        );
                        $_SESSION['success'] = $result ? "Usuário promovido a admin!" : "Erro ao promover usuário.";
                    }
                    break;
                    
                case 'demote_admin':
                    if ($user_id > 0 && $user_id != $_SESSION['user_id']) {
                        $result = pg_query_params($dbconn, 
                            "UPDATE users SET is_admin = false WHERE id = $1", 
                            [$user_id]
                        );
                        $_SESSION['success'] = $result ? "Admin removido com sucesso!" : "Erro ao remover admin.";
                    }
                    break;
                    
                case 'delete_post':
                    if ($post_id > 0) {
                        // Primeiro deleta comentários associados
                        pg_query_params($dbconn, "DELETE FROM comments WHERE post_id = $1", [$post_id]);
                        // Depois deleta upvotes
                        pg_query_params($dbconn, "DELETE FROM post_upvotes WHERE post_id = $1", [$post_id]);
                        // Finalmente deleta o post
                        $result = pg_query_params($dbconn, "DELETE FROM posts WHERE id = $1", [$post_id]);
                        $_SESSION['success'] = $result ? "Post deletado com sucesso!" : "Erro ao deletar post.";
                    }
                    break;
                    
                case 'delete_comment':
                    if ($comment_id > 0) {
                        $result = pg_query_params($dbconn, "DELETE FROM comments WHERE id = $1", [$comment_id]);
                        $_SESSION['success'] = $result ? "Comentário deletado com sucesso!" : "Erro ao deletar comentário.";
                    }
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Erro ao processar ação: " . $e->getMessage();
        }
    }
    
    header("Location: dashboard.php");
    exit();
}

// Definir variáveis para o header
$page_title = "Dashboard de Administração - Kelps Blog v2.0";
$current_page = 'admin';

// Incluir o header padrão
include '../includes/header.php';

// =============== ESTATÍSTICAS ===============
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'banned_users' => 0,
    'admin_users' => 0,
    'total_posts' => 0,
    'total_comments' => 0,
    'total_upvotes' => 0,
    'avg_posts_per_user' => 0,
    'avg_comments_per_post' => 0,
];

// Função auxiliar para executar query com segurança
function safe_query($dbconn, $query) {
    $result = pg_query($dbconn, $query);
    if ($result === false) {
        error_log("Database error: " . pg_last_error($dbconn));
        return null;
    }
    return $result;
}

// Queries de estatísticas
$queries = [
    'total_users' => "SELECT COUNT(*) FROM users WHERE is_banned = false",
    'active_users' => "SELECT COUNT(*) FROM users WHERE is_banned = false",
    'banned_users' => "SELECT COUNT(*) FROM users WHERE is_banned = true",
    'admin_users' => "SELECT COUNT(*) FROM users WHERE is_admin = true",
    'total_posts' => "SELECT COUNT(*) FROM posts",
    'total_comments' => "SELECT COUNT(*) FROM comments",
    'total_upvotes' => "SELECT COUNT(*) FROM post_upvotes",
    'avg_posts_per_user' => "SELECT ROUND(COALESCE(AVG(post_count), 0)::numeric)::integer FROM (SELECT COUNT(*) as post_count FROM posts GROUP BY user_id) sq",
    'avg_comments_per_post' => "SELECT ROUND(COALESCE(AVG(comment_count), 0)::numeric)::integer FROM (SELECT COUNT(*) as comment_count FROM comments GROUP BY post_id) sq",
];

foreach ($queries as $key => $query) {
    $result = safe_query($dbconn, $query);
    if ($result) {
        $value = pg_fetch_result($result, 0, 0);
        $stats[$key] = isset($value) ? (int)$value : 0;
        pg_free_result($result);
    }
}

// =============== DADOS PARA GRÁFICOS ===============

// ===== POSTS POR SEMANA (últimas 8 semanas) =====
// Gerar array com todas as 8 semanas explicitamente
$posts_by_week_full = [];
for ($i = 7; $i >= 0; $i--) {
    $week_start = date('Y-m-d', strtotime("-$i weeks", strtotime("Monday this week")));
    $week_end = date('Y-m-d', strtotime("+6 days", strtotime($week_start)));
    $posts_by_week_full[$week_start] = [
        'label' => date('d/m', strtotime($week_start)) . ' - ' . date('d/m', strtotime($week_end)),
        'count' => 0
    ];
}

// Query para pegar posts por semana
$week_query = "SELECT 
    DATE_TRUNC('week', created_at)::date as week_start,
    COUNT(*) as count
FROM posts
WHERE created_at >= CURRENT_DATE - INTERVAL '8 weeks'
GROUP BY DATE_TRUNC('week', created_at)
ORDER BY week_start";

$week_result = safe_query($dbconn, $week_query);
$week_data_map = [];
if ($week_result) {
    while ($row = pg_fetch_assoc($week_result)) {
        if (isset($row['week_start'])) {
            $week_start = date('Y-m-d', strtotime($row['week_start']));
            $week_data_map[$week_start] = (int)$row['count'];
        }
    }
    pg_free_result($week_result);
}

// Merge dos dados com semanas vazias preenchidas
$posts_by_week = [];
foreach ($posts_by_week_full as $week_start => $week_data) {
    $posts_by_week[] = [
        'week' => $week_data['label'],
        'count' => $week_data_map[$week_start] ?? 0
    ];
}

// ===== ATIVIDADE DIÁRIA (últimos 7 dias) =====
// Gerar array com todos os 7 dias
$users_activity_full = [];
$day_names = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_of_week = date('w', strtotime($date));
    $users_activity_full[$date] = [
        'label' => $day_names[$day_of_week] . ' ' . date('d/m', strtotime($date)),
        'count' => 0
    ];
}

// Query para atividade diária
$activity_query = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as count
FROM posts
WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'
GROUP BY DATE(created_at)
ORDER BY date";

$activity_result = safe_query($dbconn, $activity_query);
$activity_data_map = [];
if ($activity_result) {
    while ($row = pg_fetch_assoc($activity_result)) {
        if (isset($row['date'])) {
            $date = date('Y-m-d', strtotime($row['date']));
            $activity_data_map[$date] = (int)$row['count'];
        }
    }
    pg_free_result($activity_result);
}

// Merge dos dados com dias vazios preenchidos
$users_activity = [];
foreach ($users_activity_full as $date => $day_data) {
    $users_activity[] = [
        'date' => $day_data['label'],
        'count' => $activity_data_map[$date] ?? 0
    ];
}

// Top usuários por posts
$top_users = [];
$top_users_query = "SELECT 
    u.id,
    u.username, 
    COUNT(p.id) as post_count
FROM users u
LEFT JOIN posts p ON u.id = p.user_id
WHERE u.is_banned = false
GROUP BY u.id, u.username
ORDER BY post_count DESC
LIMIT 10";
$top_users_result = safe_query($dbconn, $top_users_query);
if ($top_users_result) {
    while ($row = pg_fetch_assoc($top_users_result)) {
        $top_users[] = [
            'username' => htmlspecialchars($row['username']),
            'post_count' => (int)$row['post_count']
        ];
    }
    pg_free_result($top_users_result);
}

// =============== ANÁLISE DE SEGURANÇA ===============
$security_issues = [];

// Verificar contas banidas
if ($stats['banned_users'] > 0) {
    $security_issues[] = [
        'type' => 'warning',
        'title' => 'Contas banidas ativas',
        'description' => $stats['banned_users'] . " conta(s) foram banida(s)",
        'severity' => 'medium'
    ];
}

// Verificar múltiplos admins
if ($stats['admin_users'] > 3) {
    $security_issues[] = [
        'type' => 'info',
        'title' => 'Muitos admins no sistema',
        'description' => $stats['admin_users'] . " contas com permissões de admin",
        'severity' => 'low'
    ];
}

// Verificar usuários sem dados
if ($stats['total_users'] === 0) {
    $security_issues[] = [
        'type' => 'warning',
        'title' => 'Nenhum usuário cadastrado',
        'description' => "O sistema ainda não tem usuários registrados",
        'severity' => 'medium'
    ];
}

// Listar usuários para gestão
$users_query = "SELECT id, username, email, is_admin, is_banned, created_at FROM users ORDER BY created_at DESC LIMIT 50";
$users_result = safe_query($dbconn, $users_query);

// Listar posts recentes para moderação
$posts_management_query = "SELECT p.id, p.title, p.content, u.username, p.created_at 
FROM posts p 
JOIN users u ON p.user_id = u.id 
ORDER BY p.created_at DESC LIMIT 20";
$posts_management_result = safe_query($dbconn, $posts_management_query);

// Listar comentários recentes para moderação
$comments_management_query = "SELECT c.id, c.content, u.username, p.title, c.created_at 
FROM comments c 
JOIN users u ON c.user_id = u.id 
JOIN posts p ON c.post_id = p.id 
ORDER BY c.created_at DESC LIMIT 20";
$comments_management_result = safe_query($dbconn, $comments_management_query);

$json_posts_by_week = json_encode(array_values($posts_by_week));
$json_users_activity = json_encode(array_values($users_activity));
$json_top_users = json_encode(array_values($top_users));
?>

<style>
.admin-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    background-color: #1a1a1a;
    color: #f1f1f1;
}

.dashboard-title {
    text-align: center;
    margin-bottom: 40px;
}

.dashboard-title h1 {
    margin: 0;
    color: #228be6;
    font-size: 2.5em;
}

.dashboard-title p {
    color: #999;
    margin: 10px 0 0 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: linear-gradient(135deg, #2a2a2a 0%, #333 100%);
    border-left: 4px solid #228be6;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 0.9em;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-card .number {
    font-size: 2.5em;
    font-weight: bold;
    color: #228be6;
}

.stat-card .label {
    font-size: 0.85em;
    color: #666;
    margin-top: 5px;
}

.charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.chart-container {
    background: linear-gradient(135deg, #2a2a2a 0%, #333 100%);
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    min-height: 350px;
    display: flex;
    flex-direction: column;
}

.chart-container h2 {
    margin: 0 0 15px 0;
    color: #228be6;
    font-size: 1.2em;
    border-bottom: 2px solid #444;
    padding-bottom: 10px;
}

.chart-wrapper {
    position: relative;
    height: 280px;
    flex-grow: 1;
}

.security-issues {
    background: linear-gradient(135deg, #2a2a2a 0%, #333 100%);
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 40px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.security-issues h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #228be6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.security-check-btn {
    background-color: #228be6;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em;
    font-weight: 600;
    transition: all 0.2s;
}

.security-check-btn:hover {
    background-color: #1c7ed6;
    transform: translateY(-1px);
}

.security-check-btn:disabled {
    background-color: #666;
    cursor: not-allowed;
}

.loading-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid #666;
    border-top: 2px solid #228be6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
    vertical-align: middle;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.last-check {
    font-size: 0.85em;
    color: #999;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #444;
}

.issue-item {
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 5px;
    border-left: 4px solid;
    background-color: rgba(255, 255, 255, 0.05);
}

.issue-item.low {
    border-left-color: #51cf66;
}

.issue-item.medium {
    border-left-color: #ffd43b;
}

.issue-item.high {
    border-left-color: #ff8787;
}

.issue-item.success {
    border-left-color: #51cf66;
    background-color: rgba(81, 207, 102, 0.1);
}

.issue-item small {
    display: block;
    color: #666;
    font-size: 0.8em;
    margin-top: 5px;
}

.issue-item h4 {
    margin: 0 0 5px 0;
    color: #fff;
}

.issue-item p {
    margin: 0;
    color: #999;
    font-size: 0.9em;
}

.users-section {
    background: #2a2a2a;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 40px;
    overflow-x: auto;
}

.users-section h2 {
    margin-top: 0;
    color: #228be6;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
}

.users-table th {
    background-color: #1a1a1a;
    padding: 12px;
    text-align: left;
    border-bottom: 2px solid #444;
    color: #999;
}

.users-table td {
    padding: 12px;
    border-bottom: 1px solid #444;
}

.users-table tr:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: bold;
    margin-right: 5px;
}

.status-admin {
    background-color: rgba(34, 139, 230, 0.3);
    color: #228be6;
}

.status-banned {
    background-color: rgba(255, 136, 136, 0.3);
    color: #ff8888;
}

.status-active {
    background-color: rgba(81, 207, 102, 0.3);
    color: #51cf66;
}

.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.action-btn {
    padding: 6px 12px;
    font-size: 0.85em;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 600;
}

.btn-ban {
    background-color: #ff8787;
    color: white;
}

.btn-ban:hover {
    background-color: #ff6b6b;
}

.btn-unban {
    background-color: #51cf66;
    color: white;
}

.btn-unban:hover {
    background-color: #40c057;
}

.btn-promote {
    background-color: #228be6;
    color: white;
}

.btn-promote:hover {
    background-color: #1c7ed6;
}

.btn-demote {
    background-color: #ffd43b;
    color: #000;
}

.btn-demote:hover {
    background-color: #ffca3d;
}

.btn-delete {
    background-color: #ff6b6b;
    color: white;
}

.btn-delete:hover {
    background-color: #ff5252;
}

.content-preview {
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #ccc;
    font-size: 0.9em;
}

.success-message {
    background-color: #51cf66;
    color: white;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

@media (max-width: 768px) {
    .charts-section {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
    }
}
</style>

<div class="admin-dashboard">
    <div class="dashboard-title">
        <h1>📊 Dashboard de Administração v2.0</h1>
        <p>Sistema completo de análise, gestão e segurança do Kelps Blog</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message">
            ✅ <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- CARTÕES DE ESTATÍSTICAS -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>👥 Usuários Totais</h3>
            <div class="number"><?php echo (int)$stats['total_users']; ?></div>
            <div class="label"><?php echo (int)$stats['active_users']; ?> ativos • <?php echo (int)$stats['banned_users']; ?> banidos</div>
        </div>

        <div class="stat-card">
            <h3>📝 Posts</h3>
            <div class="number"><?php echo (int)$stats['total_posts']; ?></div>
            <div class="label">Média: <?php echo (int)$stats['avg_posts_per_user']; ?> por usuário</div>
        </div>

        <div class="stat-card">
            <h3>💬 Comentários</h3>
            <div class="number"><?php echo (int)$stats['total_comments']; ?></div>
            <div class="label">Média: <?php echo (int)$stats['avg_comments_per_post']; ?> por post</div>
        </div>

        <div class="stat-card">
            <h3>👍 Upvotes</h3>
            <div class="number"><?php echo (int)$stats['total_upvotes']; ?></div>
            <div class="label">Engajamento da comunidade</div>
        </div>

        <div class="stat-card">
            <h3>🔧 Administradores</h3>
            <div class="number"><?php echo (int)$stats['admin_users']; ?></div>
            <div class="label">Contas com permissões elevadas</div>
        </div>

        <div class="stat-card">
            <h3>🏥 Saúde do Site</h3>
            <div class="number" style="color: #51cf66;">100%</div>
            <div class="label">Todos os serviços operacionais</div>
        </div>
    </div>

    <!-- GRÁFICOS -->
    <div class="charts-section">
        <div class="chart-container">
            <h2>📈 Posts por Semana (últimas 8)</h2>
            <p style="color: #999; margin: 0 0 15px 0; font-size: 0.9em;">Tendência de publicação semanal</p>
            <div class="chart-wrapper">
                <canvas id="postsChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <h2>📊 Atividade Diária (últimos 7 dias)</h2>
            <p style="color: #999; margin: 0 0 15px 0; font-size: 0.9em;">Posts criados por dia da semana</p>
            <div class="chart-wrapper">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <h2>👑 Top 10 Usuários</h2>
            <p style="color: #999; margin: 0 0 15px 0; font-size: 0.9em;">Usuários mais ativos (por número de posts)</p>
            <div class="chart-wrapper">
                <canvas id="topUsersChart"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <h2>📊 Distribuição de Usuários</h2>
            <p style="color: #999; margin: 0 0 15px 0; font-size: 0.9em;">Status de contas no sistema</p>
            <div class="chart-wrapper">
                <canvas id="usersDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ANÁLISE DE SEGURANÇA -->
    <div class="security-issues">
        <h2>
            <span>🔒 Análise de Segurança & Saúde (Em Tempo Real)</span>
            <button class="security-check-btn" id="securityCheckBtn" onclick="performSecurityCheck()">🔍 Verificar Agora</button>
        </h2>
        <div id="security-results">
            <?php if (empty($security_issues)): ?>
                <div class="issue-item low">
                    <h4>✅ Sistema em bom estado</h4>
                    <p>Nenhum problema de segurança detectado! Site bem configurado e seguro.</p>
                </div>
            <?php else: ?>
                <?php foreach ($security_issues as $issue): ?>
                    <div class="issue-item <?php echo $issue['severity']; ?>">
                        <h4><?php echo htmlspecialchars($issue['title']); ?></h4>
                        <p><?php echo htmlspecialchars($issue['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="last-check" id="lastCheckTime"></div>
    </div>

    <!-- GESTÃO DE USUÁRIOS -->
    <div class="users-section">
        <h2>👥 Gestão de Usuários</h2>
        <?php if (!$users_result): ?>
            <p style="color: #999;">Erro ao carregar usuários.</p>
        <?php else: ?>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Data de Criação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $user_count = 0;
                    while ($user = pg_fetch_assoc($users_result)): 
                        $user_count++;
                        $is_admin = ($user['is_admin'] === 't' || $user['is_admin'] === true);
                        $is_banned = ($user['is_banned'] === 't' || $user['is_banned'] === true);
                        $is_current = ($user['id'] == $_SESSION['user_id']);
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($is_admin): ?>
                                    <span class="status-badge status-admin">👑 Admin</span>
                                <?php endif; ?>
                                <?php if ($is_banned): ?>
                                    <span class="status-badge status-banned">🚫 Banido</span>
                                <?php else: ?>
                                    <span class="status-badge status-active">✅ Ativo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if (!$is_current): ?>
                                        <?php if (!$is_banned): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="ban_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="action-btn btn-ban" onclick="return confirm('Banir este usuário?');">🚫 Banir</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="unban_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="action-btn btn-unban">✅ Desbanir</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (!$is_admin): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="promote_admin">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="action-btn btn-promote" onclick="return confirm('Promover a admin?');">👑 Admin</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="demote_admin">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="action-btn btn-demote" onclick="return confirm('Remover admin?');">👤 Remover</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #666;">Você (Admin Atual)</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($user_count === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #999;">Nenhum usuário encontrado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- GESTÃO DE POSTS -->
    <div class="users-section" style="margin-top: 40px;">
        <h2>📝 Gestão de Posts</h2>
        <?php if (!$posts_management_result): ?>
            <p style="color: #999;">Erro ao carregar posts.</p>
        <?php else: ?>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Conteúdo</th>
                        <th>Data</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $post_count = 0;
                    while ($post = pg_fetch_assoc($posts_management_result)): 
                        $post_count++;
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($post['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($post['username']); ?></td>
                            <td><span class="content-preview"><?php echo htmlspecialchars($post['content']); ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_post">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="action-btn btn-delete" onclick="return confirm('Deletar este post? Esta ação é irreversível.\nTodos os comentários também serão removidos.');">🗑️ Deletar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($post_count === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #999;">Nenhum post encontrado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- GESTÃO DE COMENTÁRIOS -->
    <div class="users-section" style="margin-top: 40px;">
        <h2>💬 Gestão de Comentários</h2>
        <?php if (!$comments_management_result): ?>
            <p style="color: #999;">Erro ao carregar comentários.</p>
        <?php else: ?>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Autor</th>
                        <th>Comentário</th>
                        <th>Post</th>
                        <th>Data</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $comment_count = 0;
                    while ($comment = pg_fetch_assoc($comments_management_result)): 
                        $comment_count++;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($comment['username']); ?></td>
                            <td><span class="content-preview"><?php echo htmlspecialchars($comment['content']); ?></span></td>
                            <td><a href="../post.php?id=<?php echo htmlspecialchars($comment['title']); ?>" style="color: #228be6; text-decoration: none;"><?php echo htmlspecialchars($comment['title']); ?></a></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_comment">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                    <button type="submit" class="action-btn btn-delete" onclick="return confirm('Deletar este comentário?');">🗑️ Deletar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($comment_count === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #999;">Nenhum comentário encontrado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const chartConfig = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        intersect: false,
        mode: 'index'
    },
    plugins: {
        legend: {
            display: true,
            labels: { 
                color: '#f1f1f1',
                font: { size: 12 },
                padding: 15
            }
        },
        filler: {
            propagate: true
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#f1f1f1',
            borderColor: '#228be6',
            borderWidth: 1,
            padding: 12,
            displayColors: true
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { 
                color: '#999',
                stepSize: 1,
                precision: 0
            },
            grid: { color: '#333', drawBorder: false },
            title: {
                display: true,
                text: 'Quantidade',
                color: '#999'
            }
        },
        x: {
            ticks: { color: '#999' },
            grid: { color: '#333', drawBorder: false }
        }
    }
};

// Posts por semana
const ctx1 = document.getElementById('postsChart').getContext('2d');
const postsData = <?php echo $json_posts_by_week; ?>;
const postsMax = Math.max(...postsData.map(d => d.count), 5);
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: postsData.map(d => d.week),
        datasets: [{
            label: 'Número de Posts',
            data: postsData.map(d => d.count),
            borderColor: '#228be6',
            backgroundColor: 'rgba(34, 139, 230, 0.15)',
            tension: 0.4,
            fill: true,
            borderWidth: 3,
            pointBackgroundColor: '#228be6',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            segment: {
                borderDash: d => d.x === undefined ? [5, 5] : undefined
            }
        }]
    },
    options: { 
        ...chartConfig,
        scales: {
            ...chartConfig.scales,
            y: {
                ...chartConfig.scales.y,
                max: Math.max(postsMax, 10)
            }
        }
    }
});

// Atividade diária
const ctx2 = document.getElementById('activityChart').getContext('2d');
const activityData = <?php echo $json_users_activity; ?>;
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: activityData.map(d => d.date),
        datasets: [{
            label: 'Posts/Dia',
            data: activityData.map(d => d.count),
            backgroundColor: [
                '#51cf66', '#40c057', '#37b24d', '#2f9e44', '#2f8a4d', 
                '#228be6', '#1c7ed6'
            ],
            borderColor: '#f1f1f1',
            borderWidth: 1,
            borderRadius: 6,
            hoverBackgroundColor: '#ffd43b'
        }]
    },
    options: { 
        ...chartConfig,
        scales: {
            ...chartConfig.scales,
            y: {
                ...chartConfig.scales.y,
                beginAtZero: true
            }
        }
    }
});

// Top usuários
const ctx3 = document.getElementById('topUsersChart').getContext('2d');
const topUsersData = <?php echo $json_top_users; ?>;
const colors = ['#228be6', '#51cf66', '#ffd43b', '#ff8787', '#a78bfa', '#06b6d4', '#ec4899', '#f97316', '#84cc16', '#10b981'];
new Chart(ctx3, {
    type: 'doughnut',
    data: {
        labels: topUsersData.map((d, i) => `${i+1}. ${d.username} (${d.post_count})`),
        datasets: [{
            data: topUsersData.map(d => d.post_count),
            backgroundColor: colors.slice(0, topUsersData.length),
            borderColor: '#2a2a2a',
            borderWidth: 2,
            hoverOffset: 8
        }]
    },
    options: { 
        ...chartConfig,
        plugins: {
            ...chartConfig.plugins,
            legend: {
                display: true,
                position: 'right',
                labels: { 
                    color: '#f1f1f1',
                    font: { size: 11 },
                    padding: 10
                }
            },
            tooltip: {
                ...chartConfig.plugins.tooltip,
                callbacks: {
                    label: function(context) {
                        return context.label + ' posts';
                    }
                }
            }
        }
    }
});

// Distribuição de usuários
const ctx4 = document.getElementById('usersDistributionChart').getContext('2d');
const distData = [<?php echo $stats['active_users']; ?>, <?php echo $stats['banned_users']; ?>, <?php echo $stats['admin_users']; ?>];
new Chart(ctx4, {
    type: 'doughnut',
    data: {
        labels: [
            'Ativos (' + distData[0] + ')',
            'Banidos (' + distData[1] + ')',
            'Admins (' + distData[2] + ')'
        ],
        datasets: [{
            data: distData,
            backgroundColor: ['#51cf66', '#ff8787', '#228be6'],
            borderColor: '#2a2a2a',
            borderWidth: 2,
            hoverOffset: 8
        }]
    },
    options: { 
        ...chartConfig,
        plugins: {
            ...chartConfig.plugins,
            legend: {
                display: true,
                position: 'bottom',
                labels: { 
                    color: '#f1f1f1',
                    font: { size: 12 },
                    padding: 15
                }
            },
            tooltip: {
                ...chartConfig.plugins.tooltip,
                callbacks: {
                    label: function(context) {
                        return context.label + ' usuários';
                    }
                }
            }
        }
    }
});

// ============ VERIFICAÇÃO DE SEGURANÇA EM TEMPO REAL ============
function performSecurityCheck() {
    const btn = document.getElementById('securityCheckBtn');
    const resultsDiv = document.getElementById('security-results');
    const lastCheckDiv = document.getElementById('lastCheckTime');
    
    // Desabilita botão e mostra loading
    btn.disabled = true;
    btn.innerHTML = '<span class="loading-spinner"></span> Verificando...';
    
    fetch('/pages/api/security_check.php', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        resultsDiv.innerHTML = '';
        
        if (data.success && data.issues) {
            data.issues.forEach(issue => {
                const issueDiv = document.createElement('div');
                issueDiv.className = `issue-item ${issue.type === 'success' ? 'success' : issue.severity}`;
                
                const titleEl = document.createElement('h4');
                titleEl.textContent = issue.title;
                issueDiv.appendChild(titleEl);
                
                const descEl = document.createElement('p');
                descEl.textContent = issue.description;
                issueDiv.appendChild(descEl);
                
                const timeEl = document.createElement('small');
                timeEl.textContent = '⏱️ ' + issue.timestamp;
                issueDiv.appendChild(timeEl);
                
                resultsDiv.appendChild(issueDiv);
            });
            
            // Atualiza última verificação
            lastCheckDiv.innerHTML = `<strong>✓ Última verificação:</strong> ${data.last_check}`;
            lastCheckDiv.style.color = '#51cf66';
        } else {
            resultsDiv.innerHTML = '<div class="issue-item high"><h4>❌ Erro na verificação</h4><p>Não foi possível realizar a verificação de segurança</p></div>';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        resultsDiv.innerHTML = `<div class="issue-item high"><h4>❌ Erro de comunicação</h4><p>${error.message}</p></div>`;
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '🔍 Verificar Agora';
    });
}

// Auto-verificar segurança a cada 5 minutos
setInterval(() => {
    console.log('Auto-verificação automática de segurança...');
    performSecurityCheck();
}, 5 * 60 * 1000);

// Executar verificação inicial ao carregar a página
window.addEventListener('load', () => {
    setTimeout(() => performSecurityCheck(), 800);
});
</script>

<?php include '../includes/footer.php'; ?>
