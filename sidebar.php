<?php
/**
 * Sidebar Partial v2
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$navItems = [
    ['id'=>'dashboard', 'href'=>'dashboard.php', 'icon'=>'fa-gauge-high',   'label'=>'Dashboard'],
    ['id'=>'students',  'href'=>'students.php',  'icon'=>'fa-users',        'label'=>'Students', 'badge'=>true],
    ['id'=>'activity',  'href'=>'activity.php',  'icon'=>'fa-clock-rotate-left', 'label'=>'Activity Log'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
            <div class="logo-text">
                <div class="brand">Student<span>MS</span></div>
                <div class="version">v2.0 &nbsp;·&nbsp; Pro</div>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main Menu</div>
        <ul>
            <?php foreach ($navItems as $item): ?>
            <li>
                <a href="<?= $item['href'] ?>"
                   class="nav-link <?= $currentPage === $item['id'] ? 'active' : '' ?>">
                    <i class="fas <?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                    <?php if (!empty($item['badge'])): ?>
                        <span class="nav-badge" id="navStudentCount">…</span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="nav-section-label" style="margin-top:18px">Account</div>
        <ul>
            <li>
                <a href="logout.php" class="nav-link nav-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar-sm">
                <?= strtoupper(substr($_SESSION['admin_fullname'] ?? 'A', 0, 1)) ?>
            </div>
            <div style="flex:1;min-width:0">
                <div class="user-name"><?= htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin') ?></div>
                <div class="user-role">Administrator</div>
            </div>
            <div class="status-dot" title="Online"></div>
        </div>
    </div>
</aside>
