<?php
declare(strict_types=1);
require_once __DIR__ . '/partials/helpers.php';

$users_data  = get_users();
$users       = $users_data['users'] ?? [];
$stats_data  = get_statistics();
$design      = get_design();
$settings    = get_settings();

$page_title = 'Admin';
require_once __DIR__ . '/partials/header.php';
?>

<h1 class="text-2xl font-bold mb-5" style="color:var(--text)">🛠️ Admin</h1>

<!-- Users overview -->
<div class="card p-4 mb-5">
  <div class="flex items-center justify-between mb-3">
    <div class="section-title mb-0">👥 Nutzer (<?= count($users) ?>)</div>
    <a href="/users.php" class="btn btn-primary text-sm px-3 py-2">Alle anzeigen</a>
  </div>
  <?php foreach ($users as $u): ?>
    <?php $us = $stats_data['users'][$u['id']] ?? []; ?>
    <div class="flex items-center gap-3 py-2" style="border-top:1px solid var(--border)">
      <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl" style="background:var(--border)">
        <?php if (!empty($u['avatar'])): ?>
          <img src="/uploads/avatars/<?= htmlspecialchars(basename($u['avatar'])) ?>"
               alt="" class="w-full h-full object-cover rounded-full">
        <?php else: ?>👤<?php endif; ?>
      </div>
      <div class="flex-1">
        <div class="font-semibold" style="color:var(--text)"><?= htmlspecialchars($u['name']) ?></div>
        <div class="text-xs text-muted">
          ✅ <?= (int)($us['total_correct'] ?? 0) ?> richtig &nbsp;
          ❌ <?= (int)($us['total_wrong']   ?? 0) ?> falsch &nbsp;
          🏅 <?= count($us['badges'] ?? []) ?> Abzeichen
        </div>
      </div>
      <a href="/profile.php?user=<?= htmlspecialchars($u['id']) ?>"
         class="btn btn-secondary text-sm px-3 py-2">Profil</a>
    </div>
  <?php endforeach; ?>
</div>

<!-- Design overview -->
<div class="card p-4 mb-5">
  <div class="section-title">🎨 Aktives Design</div>
  <p class="text-sm" style="color:var(--text)">
    Framework: <strong><?= htmlspecialchars($design['css_framework'] ?? 'tailwind') ?></strong> &nbsp;|&nbsp;
    Theme: <strong><?= htmlspecialchars($design['theme'] ?? 'light') ?></strong>
  </p>
  <a href="/settings.php" class="btn btn-secondary mt-3 text-sm">Design ändern →</a>
</div>

<!-- Vocabulary overview -->
<?php
$vocabs_data = get_vocabularies();
$all_vocabs  = $vocabs_data['vocabularies'] ?? [];
$unlocked    = get_unlocked_vocabularies($vocabs_data, $settings);
$box_counts  = [1 => 0, 2 => 0, 3 => 0];
foreach ($unlocked as $v) {
    $b = (int)($v['box'] ?? 1);
    if (isset($box_counts[$b])) $box_counts[$b]++;
}
?>
<div class="card p-4 mb-5">
  <div class="section-title">📚 Vokabeln</div>
  <div class="grid grid-cols-3 gap-3 text-center text-sm">
    <div>
      <div class="text-xl font-bold" style="color:var(--text)"><?= count($all_vocabs) ?></div>
      <div class="text-muted">Gesamt</div>
    </div>
    <div>
      <div class="text-xl font-bold" style="color:var(--text)"><?= count($unlocked) ?></div>
      <div class="text-muted">Freigeschaltet</div>
    </div>
    <div>
      <div class="text-xl font-bold" style="color:var(--text)">
        <?= htmlspecialchars($settings['activation_word'] ?? '–') ?>
      </div>
      <div class="text-muted text-xs">Aktivierungswort</div>
    </div>
  </div>
</div>

<a href="/settings.php" class="btn btn-primary w-full">⚙️ Einstellungen öffnen</a>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
