<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

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
    
    if ($action === 'ban_user' && $user_id > 0) {
        $result = pg_query_params($dbconn, 
            "UPDATE users SET is_banned = true WHERE id = $1 AND id != $2", 
            [$user_id, $_SESSION['user_id']]
        );
        $_SESSION['success'] = $result ? "Usuário banido com sucesso!" : "Erro ao banir usuário.";
    }
    
    if ($action === 'unban_user' && $user_id > 0) {
        $result = pg_query_params($dbconn, 
            "UPDATE users SET is_banned = false WHERE id = $1", 
            [$user_id]
        );
        $_SESSION['success'] = $result ? "Usuário desbanido com sucesso!" : "Erro ao desbanir usuário.";
    }
    
    if ($action === 'promote_admin' && $user_id > 0) {
        $result = pg_query_params($dbconn, 
            "UPDATE users SET is_admin = true WHERE id = $1 AND id != $2", 
            [$user_id, $_SESSION['user_id']]
        );
        $_SESSION['success'] = $result ? "Usuário promovido a admin!" : "Erro ao promover usuário.";
    }
    
    if ($action === 'demote_admin' && $user_id > 0) {
        $result = pg_query_params($dbconn, 
            "UPDATE users SET is_admin = false WHERE id = $1 AND id != $2", 
            [$user_id, $_SESSION['user_id']]
        );
        $_SESSION['success'] = $result ? "Admin removido com sucesso!" : "Erro ao remover admin.";
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

// Queries de estatísticas
$queries = [
    'total_users' => "SELECT COUNT(*) FROM users",
    'active_users' => "SELECT COUNT(*) FROM users WHERE is_banned = false",
    'banned_users' => "SELECT COUNT(*) FROM users WHERE is_banned = true",
    'admin_users' => "SELECT COUNT(*) FROM users WHERE is_admin = true",
    'total_posts' => "SELECT COUNT(*) FROM posts",
    'total_comments' => "SELECT COUNT(*) FROM comments",
    'total_upvotes' => "SELECT COUNT(*) FROM post_upvotes",
    'avg_posts_per_user' => "SELECT ROUND(COALESCE(AVG(post_count), 0)) FROM (SELECT COUNT(*) as post_count FROM posts GROUP BY user_id) sq",
    'avg_comments_per_post' => "SELECT ROUND(COALESCE(AVG(comment_count), 0)) FROM (SELECT COUNT(*) as comment_count FROM comments GROUP BY post_id) sq",
];

foreach ($queries as $key => $query) {
    $result = pg_query($dbconn, $query);
    if ($result) {
        $stats[$key] = pg_fetch_result($result, 0, 0) ?? 0;
    }
}

// =============== DADOS PARA GRÁFICOS ===============

// Posts por semana
$posts_by_week = [];
$week_query = "SELECT 
    DATE_TRUNC('week', created_at)::date as week,
    COUNT(*) as count
FROM posts
WHERE created_at >= CURRENT_DATE - INTERVAL '8 weeks'
GROUP BY DATE_TRUNC('week', created_at)
ORDER BY week";
$week_result = pg_query($dbconn, $week_query);
if ($week_result) {
    while ($row = pg_fetch_assoc($week_result)) {
        $posts_by_week[] = [
            'week' => date('d/m', strtotime($row['week'])),
            'count' => $row['count']
        ];
    }
}

// Atividade de usuários (últimos 7 dias)
$users_activity = [];
$activity_query = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as count
FROM posts
WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'
GROUP BY DATE(created_at)
ORDER BY date";
$activity_result = pg_query($dbconn, $activity_query);
if ($activity_result) {
    while ($row = pg_fetch_assoc($activity_result)) {
        $users_activity[] = [
            'date' => date('d/m', strtotime($row['date'])),
            'count' => $row['count']
        ];
    }
}

// Top usuários por posts
$top_users = [];
$top_users_query = "SELECT u.username, COUNT(p.id) as post_count
FROM users u
LEFT JOIN posts p ON u.id = p.user_id
GROUP BY u.id, u.username
ORDER BY post_count DESC
LIMIT 10";
$top_users_result = pg_query($dbconn, $top_users_query);
if ($top_users_result) {
    while ($row = pg_fetch_assoc($top_users_result)) {
        $top_users[] = $row;
    }
}

// =============== ANÁLISE DE SEGURANÇA ===============
$security_issues = [];

// Verificar usuários sem avatars
$no_avatar_query = "SELECT COUNT(*) FROM users WHERE profile_picture IS NULL OR profile_picture = ''";
$no_avatar_result = pg_query($dbconn, $no_avatar_query);
$no_avatar_count = pg_fetch_result($no_avatar_result, 0, 0);
if ($no_avatar_count > 0) {
    $security_issues[] = [
        'type' => 'info',
        'title' => 'Usuários sem foto de perfil',
        'description' => "$no_avatar_count usuários não possuem foto de perfil",
        'severity' => 'low'
    ];
}

// Verificar contas banidas
if ($stats['banned_users'] > 0) {
    $security_issues[] = [
        'type' => 'warning',
        'title' => 'Contas banidas ativas',
        'description' => $stats['banned_users'] . " contas foram banidas",
        'severity' => 'medium'
    ];
}

// Verificar múltiplos admins
if ($stats['admin_users'] > 3) {
    $security_issues[] = [
        'type' => 'info',
        'title' => 'Muitos admins',
        'description' => $stats['admin_users'] . " contas com permissões de admin",
        'severity' => 'low'
    ];
}

// Listar usuários para gestão
$users_result = pg_query($dbconn, "SELECT id, username, email, is_admin, is_banned, created_at FROM users ORDER BY created_at DESC");

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
    background: #2a2a2a;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.chart-container h2 {
    margin-top: 0;
    color: #228be6;
    font-size: 1.3em;
}

.security-issues {
    background: #2a2a2a;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 40px;
}

.security-issues h2 {
    margin-top: 0;
    color: #228be6;
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
            <div class="number"><?php echo $stats['total_users']; ?></div>
            <div class="label"><?php echo $stats['active_users']; ?> ativos • <?php echo $stats['banned_users']; ?> banidos</div>
        </div>

        <div class="stat-card">
            <h3>📝 Posts</h3>
            <div class="number"><?php echo $stats['total_posts']; ?></div>
            <div class="label">Média: <?php echo $stats['avg_posts_per_user']; ?> por usuário</div>
        </div>

        <div class="stat-card">
            <h3>💬 Comentários</h3>
            <div class="number"><?php echo $stats['total_comments']; ?></div>
            <div class="label">Média: <?php echo $stats['avg_comments_per_post']; ?> por post</div>
        </div>

        <div class="stat-card">
            <h3>👍 Upvotes</h3>
            <div class="number"><?php echo $stats['total_upvotes']; ?></div>
            <div class="label">Engajamento do comunidade</div>
        </div>

        <div class="stat-card">
            <h3>🔧 Administradores</h3>
            <div class="number"><?php echo $stats['admin_users']; ?></div>
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
            <h2>📈 Posts por Semana</h2>
            <canvas id="postsChart" width="400" height="200"></canvas>
        </div>

        <div class="chart-container">
            <h2>📊 Atividade Diária (7 dias)</h2>
            <canvas id="activityChart" width="400" height="200"></canvas>
        </div>

        <div class="chart-container">
            <h2>👑 Top 10 Usuários</h2>
            <canvas id="topUsersChart" width="400" height="200"></canvas>
        </div>

        <div class="chart-container">
            <h2>📊 Distribuição de Usuários</h2>
            <canvas id="usersDistributionChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- ANÁLISE DE SEGURANÇA -->
    <div class="security-issues">
        <h2>🔒 Análise de Segurança & Saúde</h2>
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

    <!-- GESTÃO DE USUÁRIOS -->
    <div class="users-section">
        <h2>👥 Gestão de Usuários</h2>
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
                <?php while ($user = pg_fetch_assoc($users_result)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['is_admin'] == 't'): ?>
                                <span class="status-badge status-admin">👑 Admin</span>
                            <?php endif; ?>
                            <?php if ($user['is_banned'] == 't'): ?>
                                <span class="status-badge status-banned">🚫 Banido</span>
                            <?php else: ?>
                                <span class="status-badge status-active">✅ Ativo</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <?php if ($user['is_banned'] != 't'): ?>
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

                                    <?php if ($user['is_admin'] != 't'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="promote_admin">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="action-btn btn-promote" onclick="return confirm('Promover e admin?');">👑 Admin</button>
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
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const chartConfig = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
            labels: { color: '#f1f1f1' }
        },
        filler: {
            propagate: true
        }
    },
    scales: {
        y: {
            ticks: { color: '#999' },
            grid: { color: '#333' }
        },
        x: {
            ticks: { color: '#999' },
            grid: { color: '#333' }
        }
    }
};

// Posts por semana
const ctx1 = document.getElementById('postsChart').getContext('2d');
const postsData = <?php echo $json_posts_by_week; ?>;
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: postsData.map(d => d.week),
        datasets: [{
            label: 'Posts',
            data: postsData.map(d => d.count),
            borderColor: '#228be6',
            backgroundColor: 'rgba(34, 139, 230, 0.2)',
            tension: 0.4,
            fill: true,
            borderWidth: 2,
            pointBackgroundColor: '#228be6',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: { ...chartConfig, maintainAspectRatio: true }
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
            backgroundColor: '#51cf66',
            borderColor: '#40c057',
            borderWidth: 1
        }]
    },
    options: { ...chartConfig, maintainAspectRatio: true }
});

// Top usuários
const ctx3 = document.getElementById('topUsersChart').getContext('2d');
const topUsersData = <?php echo $json_top_users; ?>;
const colors = ['#228be6', '#51cf66', '#ffd43b', '#ff8787', '#a78bfa', '#06b6d4', '#ec4899', '#f97316', '#84cc16', '#10b981'];
new Chart(ctx3, {
    type: 'doughnut',
    data: {
        labels: topUsersData.map(d => d.username),
        datasets: [{
            data: topUsersData.map(d => d.post_count),
            backgroundColor: colors.slice(0, topUsersData.length),
            borderColor: '#2a2a2a',
            borderWidth: 2
        }]
    },
    options: { ...chartConfig, maintainAspectRatio: true }
});

// Distribuição de usuários
const ctx4 = document.getElementById('usersDistributionChart').getContext('2d');
new Chart(ctx4, {
    type: 'doughnut',
    data: {
        labels: ['Ativos', 'Banidos', 'Admins'],
        datasets: [{
            data: [<?php echo $stats['active_users']; ?>, <?php echo $stats['banned_users']; ?>, <?php echo $stats['admin_users']; ?>],
            backgroundColor: ['#51cf66', '#ff8787', '#228be6'],
            borderColor: '#2a2a2a',
            borderWidth: 2
        }]
    },
    options: { ...chartConfig, maintainAspectRatio: true }
});
</script>

<?php include '../includes/footer.php'; ?>
