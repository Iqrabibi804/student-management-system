<?php
/**
 * Header Partial v2
 */
$pageTitle    = $pageTitle    ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? 'Manage your data efficiently';
$currentPage  = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — StudentMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div id="toastContainer" class="toast-container"></div>

<div class="app-shell" id="appShell">

<?php include __DIR__ . '/../partials/sidebar.php'; ?>

<div class="main-content" id="mainContent">

    <!-- TOPBAR -->
    <header class="topbar">
        <button class="sidebar-toggle" id="sidebarToggle" title="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>

        <div class="topbar-center">
            <div class="search-bar">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" id="globalSearch" placeholder="Quick search students…" autocomplete="off">
                <kbd>Ctrl+K</kbd>
            </div>
        </div>

        <div class="topbar-right">
            <button class="icon-btn" id="darkModeToggle" title="Toggle dark mode">
                <i class="fas fa-moon" id="darkModeIcon"></i>
            </button>

            <button class="icon-btn" onclick="window.location='students.php'" title="Students">
                <i class="fas fa-users"></i>
            </button>

            <!-- Admin dropdown -->
            <div class="admin-menu" id="adminMenu">
                <button class="admin-trigger" id="adminTrigger" onclick="toggleAdminMenu()">
                    <div class="avatar-top">
                        <?= strtoupper(substr($_SESSION['admin_fullname'] ?? 'A', 0, 1)) ?>
                    </div>
                    <span><?= htmlspecialchars($_SESSION['admin_fullname'] ?? 'Admin') ?></span>
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <div class="admin-dropdown" id="adminDropdown">
                    <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                    <a href="activity.php"><i class="fas fa-history"></i> Activity Log</a>
                    <hr>
                    <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- PAGE CONTENT -->
    <div class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
            </div>
        </div>
