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
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> – Vokabeltrainer</title>
<?php if ($framework === 'tailwind'): ?>
<script src="https://cdn.tailwindcss.com"></script>
<?php endif; ?>
<?php if ($use_chart ?? false): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php endif; ?>
<link rel="stylesheet" href="/assets/css/base.css">
<link rel="stylesheet" href="/assets/css/theme-<?= htmlspecialchars($theme) ?>.css">
</head>
<body class="min-h-screen font-sans">

<!-- Sticky Header -->
<header class="sticky top-0 z-50 shadow-sm" style="background:var(--card-bg);border-bottom:1px solid var(--border);">
  <div class="max-w-2xl mx-auto flex items-center justify-between px-4 py-3 relative">

    <!-- Hamburger -->
    <button id="menu-btn" aria-label="Menü öffnen"
            class="w-10 h-10 flex flex-col justify-center items-center gap-1.5 rounded focus:outline-none"
            style="color:var(--text)">
      <span class="block w-6 h-0.5" style="background:currentColor"></span>
      <span class="block w-6 h-0.5" style="background:currentColor"></span>
      <span class="block w-6 h-0.5" style="background:currentColor"></span>
    </button>

    <!-- Title (centre) -->
    <span class="absolute left-1/2 -translate-x-1/2 font-bold text-lg tracking-wide"
          style="color:var(--text)">Vokabeltrainer</span>

    <!-- Avatar placeholder -->
    <div class="w-10 h-10 rounded-full overflow-hidden flex items-center justify-center text-xl"
         style="background:var(--border)">
      <?php if (!empty($current_user['avatar']) && file_exists(BASE_PATH . '/uploads/avatars/' . basename($current_user['avatar']))): ?>
        <img src="/uploads/avatars/<?= htmlspecialchars(basename($current_user['avatar'])) ?>"
             alt="Avatar" class="w-full h-full object-cover">
      <?php else: ?>
        👤
      <?php endif; ?>
    </div>

    <!-- Flyout menu -->
    <nav id="flyout-menu"
         class="hidden absolute top-14 left-2 w-52 rounded-xl shadow-xl z-50 py-2"
         style="background:var(--card-bg);border:1px solid var(--border)">
      <a href="/index.php"
         class="flex items-center gap-2 px-4 py-3 text-sm font-medium hover:opacity-70 transition"
         style="color:var(--text)">🏠 Home</a>
      <a href="/settings.php"
         class="flex items-center gap-2 px-4 py-3 text-sm font-medium hover:opacity-70 transition"
         style="color:var(--text)">⚙️ Einstellungen</a>
      <a href="/stats.php"
         class="flex items-center gap-2 px-4 py-3 text-sm font-medium hover:opacity-70 transition"
         style="color:var(--text)">📊 Statistik</a>
      <a href="/profile.php?user=<?= htmlspecialchars($current_user_id) ?>"
         class="flex items-center gap-2 px-4 py-3 text-sm font-medium hover:opacity-70 transition"
         style="color:var(--text)">👤 Profil</a>
      <a href="/users.php"
         class="flex items-center gap-2 px-4 py-3 text-sm font-medium hover:opacity-70 transition"
         style="color:var(--text)">👥 Nutzer</a>
      <a href="/admin.php"
         class="flex items-center gap-2 px-4 py-3 text-sm font-medium hover:opacity-70 transition"
         style="color:var(--text)">🛠️ Admin</a>
    </nav>
  </div>
</header>

<main class="max-w-2xl mx-auto px-4 py-6">
