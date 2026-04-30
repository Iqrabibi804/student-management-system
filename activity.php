<?php
/**
 * Activity Log Page
 */
require_once 'includes/auth_check.php';

$pdo = getDB();

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$total = $pdo->query("SELECT COUNT(*) FROM activity_log")->fetchColumn();
$pages = max(1, ceil($total / $limit));

$stmt = $pdo->prepare(
    "SELECT al.*, au.full_name
     FROM activity_log al
     LEFT JOIN admin_users au ON al.admin_id = au.id
     ORDER BY al.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

$pageTitle    = 'Activity Log';
$pageSubtitle = 'Track all system actions';
include 'partials/header.php';

function actionClass(string $action): string {
    return match(strtoupper($action)) {
        'ADD'    => 'success',
        'UPDATE' => 'info',
        'DELETE' => 'danger',
        'LOGIN','LOGOUT' => 'warning',
        default  => 'neutral',
    };
}
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history"></i> System Activity Log</h3>
        <span class="record-count"><?= number_format($total) ?> entries</span>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Admin</th>
                    <th>IP Address</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $i => $log): ?>
                <tr>
                    <td><?= $offset + $i + 1 ?></td>
                    <td>
                        <span class="badge badge-<?= actionClass($log['action']) ?>">
                            <?= htmlspecialchars($log['action']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($log['description']) ?></td>
                    <td><?= htmlspecialchars($log['full_name'] ?? 'System') ?></td>
                    <td><code><?= htmlspecialchars($log['ip_address']) ?></code></td>
                    <td><?= htmlspecialchars(date('M j, Y H:i', strtotime($log['created_at']))) ?></td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="table-empty">No activity recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
    <div class="pagination" style="padding:16px 20px">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>" class="page-btn"><i class="fas fa-chevron-left"></i> Prev</a>
        <?php else: ?>
            <button class="page-btn" disabled><i class="fas fa-chevron-left"></i> Prev</button>
        <?php endif; ?>

        <div class="page-info">Page <?= $page ?> of <?= $pages ?></div>

        <?php if ($page < $pages): ?>
            <a href="?page=<?= $page+1 ?>" class="page-btn">Next <i class="fas fa-chevron-right"></i></a>
        <?php else: ?>
            <button class="page-btn" disabled>Next <i class="fas fa-chevron-right"></i></button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'partials/footer.php'; ?>
