<?php
declare(strict_types=1);
require_once __DIR__ . '/partials/helpers.php';

$success = '';
$error   = '';

// Create new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['post_action'] ?? '') === 'create_user') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $error = 'Name darf nicht leer sein.';
    } else {
        $users_data = get_users();
        // Increment last ID
        $last = $users_data['meta']['last_user_id'] ?? 'u0000';
        $num  = (int)substr($last, 1) + 1;
        $new_id = 'u' . str_pad((string)$num, 4, '0', STR_PAD_LEFT);
        $new_user = [
            'id'     => $new_id,
            'name'   => $name,
            'avatar' => null,
            'settings' => ['daily_goal' => 30, 'theme' => 'light', 'css_framework' => 'tailwind'],
        ];
        $users_data['users'][] = $new_user;
        $users_data['meta']['last_user_id'] = $new_id;
        write_json_atomic(BASE_PATH . '/data/users.json', $users_data);

        // Init stats
        $stats = get_statistics();
        $stats['users'][$new_id] = [
            'total_correct' => 0, 'total_wrong' => 0,
            'daily_history' => [], 'badges' => [],
            'current_session' => null,
            'today' => ['date' => null, 'correct' => 0, 'wrong' => 0, 'total' => 0],
        ];
        write_json_atomic(BASE_PATH . '/data/statistics.json', $stats);
        $success = 'Nutzer „' . htmlspecialchars($name) . '" erstellt.';
    }
}

$users_data = get_users();
$users      = $users_data['users'] ?? [];
$stats_data = get_statistics();

$page_title = 'Nutzer';
require_once __DIR__ . '/partials/header.php';
?>

<div class="flex items-center justify-between mb-5">
  <h1 class="text-2xl font-extrabold" style="color:var(--text)">👥 Nutzer</h1>
</div>

<?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- User grid -->
<div class="grid grid-cols-2 gap-4 mb-6">
  <?php foreach ($users as $u): ?>
    <?php $us = $stats_data['users'][$u['id']] ?? []; ?>
    <a href="profile.php?user=<?= htmlspecialchars($u['id']) ?>"
       class="card p-4 flex flex-col items-center gap-2 text-center no-underline"
       style="transition:transform 0.2s ease,box-shadow 0.2s ease"
       onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)'"
       onmouseleave="this.style.transform='';this.style.boxShadow=''">
      <div class="w-16 h-16 rounded-full overflow-hidden flex items-center justify-center text-3xl ring-2"
           style="background:var(--border);ring-color:var(--border)">
        <?php if (!empty($u['avatar']) && file_exists(BASE_PATH . '/uploads/avatars/' . basename($u['avatar']))): ?>
          <img src="uploads/avatars/<?= htmlspecialchars(basename($u['avatar'])) ?>"
               alt="Avatar" class="w-full h-full object-cover">
        <?php else: ?>
          👤
        <?php endif; ?>
      </div>
      <div class="font-bold text-sm" style="color:var(--text)"><?= htmlspecialchars($u['name']) ?></div>
      <div class="flex gap-2 text-xs">
        <span class="px-2 py-0.5 rounded-full font-semibold" style="background:#d1fae5;color:#065f46">✅ <?= (int)($us['total_correct'] ?? 0) ?></span>
        <span class="px-2 py-0.5 rounded-full font-semibold" style="background:#fee2e2;color:#991b1b">❌ <?= (int)($us['total_wrong'] ?? 0) ?></span>
      </div>
      <div class="text-xs font-medium" style="color:var(--primary)">Profil öffnen →</div>
    </a>
  <?php endforeach; ?>
</div>

<!-- Create new user form -->
<div class="card p-5">
  <div class="section-title">➕ Neuen Nutzer erstellen</div>
  <form method="post">
    <input type="hidden" name="post_action" value="create_user">
    <label class="label" for="name">Name</label>
    <input type="text" id="name" name="name" class="input mb-3"
           placeholder="z.B. Leon" maxlength="50" required>
    <button type="submit" class="btn btn-primary w-full py-3 rounded-2xl">✨ Erstellen</button>
  </form>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
