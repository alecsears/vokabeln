<?php
declare(strict_types=1);
require_once __DIR__ . '/helpers.php';

$design  = get_design();
$theme   = $design['theme'] ?? 'light';
$framework = $design['css_framework'] ?? 'tailwind';

$current_user    = get_active_user();
$current_user_id = get_current_user_id();

$use_chart = $use_chart ?? false;
$page_title = $page_title ?? 'Vokabeltrainer';

// Detect active page for nav highlighting
$current_page = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> – Vokabeltrainer</title>
<base href="<?= htmlspecialchars(BASE_URL . '/') ?>">
<script>window.BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<?php if ($framework === 'tailwind'): ?>
<script src="https://cdn.tailwindcss.com"></script>
<?php endif; ?>
<?php if ($use_chart ?? false): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php endif; ?>
<link rel="stylesheet" href="assets/css/base.css">
<link rel="stylesheet" href="assets/css/theme-<?= htmlspecialchars($theme) ?>.css">
</head>
<body class="min-h-screen font-sans">

<!-- Sticky Glass Header -->
<header class="sticky top-0 z-50 glass-header">
  <div class="max-w-2xl mx-auto flex items-center justify-between px-4 py-3 relative">

    <!-- Hamburger -->
    <button id="menu-btn" aria-label="Menü öffnen"
            class="w-10 h-10 flex flex-col justify-center items-center gap-[5px] rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-1 transition"
            style="focus-ring-color:var(--primary)">
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
    </button>

    <!-- Logo (centre) -->
    <span class="absolute left-1/2 -translate-x-1/2 logo-text text-lg">
      📚 Vokabeltrainer
    </span>

    <!-- Avatar -->
    <div class="w-10 h-10 rounded-full overflow-hidden flex items-center justify-center text-xl ring-2 transition"
         style="background:var(--border);ring-color:var(--primary)">
      <?php if (!empty($current_user['avatar']) && file_exists(BASE_PATH . '/uploads/avatars/' . basename($current_user['avatar']))): ?>
        <img src="uploads/avatars/<?= htmlspecialchars(basename($current_user['avatar'])) ?>"
             alt="Avatar" class="w-full h-full object-cover">
      <?php else: ?>
        👤
      <?php endif; ?>
    </div>

    <!-- Flyout menu -->
    <nav id="flyout-menu"
         class="hidden absolute top-[58px] left-2 w-56 rounded-2xl shadow-2xl z-50 py-2"
         style="background:var(--card-bg);border:1px solid var(--border)">
      <a href="index.php"
         class="nav-item <?= $current_page === 'index.php' ? 'active' : '' ?>">
        🏠 <span>Home</span>
      </a>
      <a href="training.php"
         class="nav-item <?= $current_page === 'training.php' ? 'active' : '' ?>">
        📚 <span>Training</span>
      </a>
      <a href="stats.php"
         class="nav-item <?= $current_page === 'stats.php' ? 'active' : '' ?>">
        📊 <span>Statistik</span>
      </a>
      <a href="profile.php?user=<?= htmlspecialchars($current_user_id) ?>"
         class="nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
        👤 <span>Profil</span>
      </a>
      <a href="users.php"
         class="nav-item <?= $current_page === 'users.php' ? 'active' : '' ?>">
        👥 <span>Nutzer</span>
      </a>
      <div style="height:1px;background:var(--border);margin:0.375rem 1rem;"></div>
      <a href="settings.php"
         class="nav-item <?= $current_page === 'settings.php' ? 'active' : '' ?>">
        ⚙️ <span>Einstellungen</span>
      </a>
      <a href="admin.php"
         class="nav-item <?= $current_page === 'admin.php' ? 'active' : '' ?>">
        🛠️ <span>Admin</span>
      </a>
    </nav>
  </div>
</header>

<main class="max-w-2xl mx-auto px-4 py-6">
