<?php
/**
 * Dashboard — Smart Student Management System v2
 */
require_once 'includes/auth_check.php';

$pdo = getDB();

// ── Stats ──────────────────────────────────────────────
$totalStudents   = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$activeStudents  = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='Active'")->fetchColumn();
$inactiveStudents= (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='Inactive'")->fetchColumn();
$todayStudents   = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$weekStudents    = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();
$totalCourses    = (int)$pdo->query("SELECT COUNT(DISTINCT course) FROM students")->fetchColumn();

// ── Students per course (bar chart) ───────────────────
$courseData = $pdo->query(
    "SELECT course, COUNT(*) AS total FROM students GROUP BY course ORDER BY total DESC"
)->fetchAll();

// Build clean arrays for JS (FIX: not inside heredoc)
$courseLabels = array_column($courseData, 'course');
$courseValues = array_map('intval', array_column($courseData, 'total'));

// ── Monthly registrations for current year ─────────────
$monthlyData = $pdo->query(
    "SELECT MONTH(created_at) AS m, COUNT(*) AS total
     FROM students WHERE YEAR(created_at) = YEAR(NOW())
     GROUP BY MONTH(created_at) ORDER BY m"
)->fetchAll();
$monthlyMap = array_fill(1, 12, 0);
foreach ($monthlyData as $row) $monthlyMap[(int)$row['m']] = (int)$row['total'];
$monthlyValues = array_values($monthlyMap);

// ── Status donut (for chart) ───────────────────────────
$statusValues = [$activeStudents, $inactiveStudents];

// ── Recent students ────────────────────────────────────
$recentStudents = $pdo->query(
    "SELECT * FROM students ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

// ── Recent activity ────────────────────────────────────
$recentActivity = $pdo->query(
    "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10"
)->fetchAll();

// ── Top courses ────────────────────────────────────────
$topCourses = $pdo->query(
    "SELECT course, COUNT(*) as total,
     SUM(status='Active') as active
     FROM students GROUP BY course ORDER BY total DESC LIMIT 5"
)->fetchAll();

// ── Helpers ─────────────────────────────────────────────
function timeAgo(string $datetime): string {
    $diff = (new DateTime())->diff(new DateTime($datetime));
    if ($diff->days > 30) return (new DateTime($datetime))->format('M j');
    if ($diff->days > 0)  return $diff->days . 'd ago';
    if ($diff->h > 0)     return $diff->h . 'h ago';
    if ($diff->i > 0)     return $diff->i . 'm ago';
    return 'Just now';
}
function avatarColor(string $str): string {
    $colors = ['#6366F1','#06B6D4','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#F97316'];
    return $colors[abs(crc32($str)) % count($colors)];
}
function activityIcon(string $action): string {
    return match(strtoupper($action)) {
        'ADD'    => 'fa-plus',
        'UPDATE' => 'fa-pen',
        'DELETE' => 'fa-trash',
        'LOGIN'  => 'fa-sign-in-alt',
        'LOGOUT' => 'fa-sign-out-alt',
        default  => 'fa-circle',
    };
}
function activityColor(string $action): string {
    return match(strtoupper($action)) {
        'ADD'    => 'green',
        'UPDATE' => 'blue',
        'DELETE' => 'red',
        'LOGIN','LOGOUT' => 'orange',
        default  => 'gray',
    };
}

// ── Pre-encode JS data (FIX for array-to-string bug) ───
$jsCourseLables = json_encode($courseLabels);
$jsCourseValues = json_encode($courseValues);
$jsMonthlyValues= json_encode($monthlyValues);
$jsStatusValues = json_encode($statusValues);

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin') . '! Here\'s what\'s happening.';
include 'partials/header.php';
?>

<!-- ── STAT CARDS ─────────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card stat-indigo" style="animation-delay:0s">
        <div class="stat-bg-icon"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <p class="stat-label">Total Students</p>
            <h2 class="stat-value counter" data-target="<?= $totalStudents ?>"><?= $totalStudents ?></h2>
            <div class="stat-trend up">
                <i class="fas fa-arrow-trend-up"></i>
                <span>All registrations</span>
            </div>
        </div>
        <div class="stat-icon-wrap indigo">
            <i class="fas fa-users"></i>
        </div>
    </div>

    <div class="stat-card stat-cyan" style="animation-delay:.08s">
        <div class="stat-bg-icon"><i class="fas fa-user-check"></i></div>
        <div class="stat-content">
            <p class="stat-label">Active Students</p>
            <h2 class="stat-value counter" data-target="<?= $activeStudents ?>"><?= $activeStudents ?></h2>
            <div class="stat-trend up">
                <i class="fas fa-circle-check"></i>
                <span><?= $totalStudents > 0 ? round($activeStudents/$totalStudents*100) : 0 ?>% of total</span>
            </div>
        </div>
        <div class="stat-icon-wrap cyan">
            <i class="fas fa-user-check"></i>
        </div>
    </div>

    <div class="stat-card stat-green" style="animation-delay:.16s">
        <div class="stat-bg-icon"><i class="fas fa-user-plus"></i></div>
        <div class="stat-content">
            <p class="stat-label">Added Today</p>
            <h2 class="stat-value counter" data-target="<?= $todayStudents ?>"><?= $todayStudents ?></h2>
            <div class="stat-trend <?= $todayStudents > 0 ? 'up' : 'neutral' ?>">
                <i class="fas fa-calendar-day"></i>
                <span><?= $weekStudents ?> this week</span>
            </div>
        </div>
        <div class="stat-icon-wrap green">
            <i class="fas fa-user-plus"></i>
        </div>
    </div>

    <div class="stat-card stat-amber" style="animation-delay:.24s">
        <div class="stat-bg-icon"><i class="fas fa-book-open"></i></div>
        <div class="stat-content">
            <p class="stat-label">Total Courses</p>
            <h2 class="stat-value counter" data-target="<?= $totalCourses ?>"><?= $totalCourses ?></h2>
            <div class="stat-trend neutral">
                <i class="fas fa-layer-group"></i>
                <span>Distinct courses</span>
            </div>
        </div>
        <div class="stat-icon-wrap amber">
            <i class="fas fa-book-open"></i>
        </div>
    </div>
</div>

<!-- ── CHARTS ROW ──────────────────────────────────────── -->
<div class="charts-grid">
    <!-- Bar Chart -->
    <div class="card chart-card" style="animation-delay:.1s">
        <div class="card-header">
            <div class="card-title-group">
                <h3 class="card-title"><i class="fas fa-chart-bar"></i> Students per Course</h3>
                <p class="card-subtitle">Distribution across all courses</p>
            </div>
        </div>
        <div class="chart-wrap"><canvas id="courseChart"></canvas></div>
    </div>

    <!-- Line Chart -->
    <div class="card chart-card" style="animation-delay:.18s">
        <div class="card-header">
            <div class="card-title-group">
                <h3 class="card-title"><i class="fas fa-chart-line"></i> Monthly Registrations</h3>
                <p class="card-subtitle"><?= date('Y') ?> overview</p>
            </div>
        </div>
        <div class="chart-wrap"><canvas id="monthlyChart"></canvas></div>
    </div>
</div>

<!-- ── BOTTOM ROW ─────────────────────────────────────── -->
<div class="bottom-grid">

    <!-- Recent Students -->
    <div class="card" style="animation-delay:.2s">
        <div class="card-header">
            <div class="card-title-group">
                <h3 class="card-title"><i class="fas fa-clock-rotate-left"></i> Recent Students</h3>
                <p class="card-subtitle">Latest registrations</p>
            </div>
            <a href="students.php" class="card-link">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="recent-list">
            <?php foreach ($recentStudents as $s): ?>
            <div class="recent-item">
                <div class="s-avatar" style="background:<?= avatarColor($s['name']) ?>">
                    <?= strtoupper(substr($s['name'],0,2)) ?>
                </div>
                <div class="s-info">
                    <strong><?= htmlspecialchars($s['name']) ?></strong>
                    <span><?= htmlspecialchars($s['course']) ?></span>
                </div>
                <div class="s-right">
                    <span class="badge badge-<?= strtolower($s['status']) ?>"><?= $s['status'] ?></span>
                    <span class="s-time"><?= timeAgo($s['created_at']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right Column -->
    <div class="right-col">

        <!-- Donut + Course Summary -->
        <div class="card" style="animation-delay:.25s">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie"></i> Status Overview</h3>
            </div>
            <div class="donut-row">
                <div class="donut-wrap">
                    <canvas id="statusChart" width="130" height="130"></canvas>
                    <div class="donut-center">
                        <span class="donut-num"><?= $totalStudents ?></span>
                        <span class="donut-lbl">Total</span>
                    </div>
                </div>
                <div class="donut-legend">
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#10B981"></span>
                        <span>Active</span>
                        <strong><?= $activeStudents ?></strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#94A3B8"></span>
                        <span>Inactive</span>
                        <strong><?= $inactiveStudents ?></strong>
                    </div>
                    <?php foreach ($topCourses as $c): ?>
                    <div class="legend-item" style="margin-top:6px">
                        <span class="legend-dot" style="background:<?= avatarColor($c['course']) ?>"></span>
                        <span style="font-size:11px"><?= htmlspecialchars($c['course']) ?></span>
                        <strong><?= $c['total'] ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Activity Feed -->
        <div class="card" style="animation-delay:.3s">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt"></i> Recent Activity</h3>
                <a href="activity.php" class="card-link">All Logs <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="activity-feed">
                <?php if (empty($recentActivity)): ?>
                    <p class="empty-msg">No activity recorded yet.</p>
                <?php endif; ?>
                <?php foreach ($recentActivity as $log): ?>
                <div class="activity-item">
                    <div class="act-icon <?= activityColor($log['action']) ?>">
                        <i class="fas <?= activityIcon($log['action']) ?>"></i>
                    </div>
                    <div class="act-body">
                        <p><?= htmlspecialchars($log['description']) ?></p>
                        <span><?= timeAgo($log['created_at']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Inject chart data safely (no heredoc PHP bug) ─── -->
<script>
const CHART_DATA = {
    courseLabels:  <?= $jsCourseLables ?>,
    courseValues:  <?= $jsCourseValues ?>,
    monthlyValues: <?= $jsMonthlyValues ?>,
    statusValues:  <?= $jsStatusValues ?>
};
</script>
<script src="js/dashboard.js"></script>

<?php include 'partials/footer.php'; ?>
