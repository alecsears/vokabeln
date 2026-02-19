<?php
declare(strict_types=1);
require_once __DIR__ . '/partials/helpers.php';

$settings    = get_settings();
$vocabs_data = get_vocabularies();
$unlocked    = get_unlocked_vocabularies($vocabs_data, $settings);
$current_user = get_active_user();
$current_user_id = get_current_user_id();
$stats_data  = get_statistics();
$user_stats  = $stats_data['users'][$current_user_id] ?? ['total_correct' => 0, 'total_wrong' => 0, 'today' => ['correct' => 0, 'wrong' => 0, 'total' => 0]];

// Box counts
$box_counts = [1 => 0, 2 => 0, 3 => 0];
foreach ($unlocked as $v) {
    $b = (int)($v['box'] ?? 1);
    if (isset($box_counts[$b])) $box_counts[$b]++;
}

$total_unlocked = count($unlocked);
$total_vocabs   = count($vocabs_data['vocabularies'] ?? []);
$total_correct  = $user_stats['total_correct'] ?? 0;
$total_wrong    = $user_stats['total_wrong'] ?? 0;
$total_answered = $total_correct + $total_wrong;
$error_rate     = $total_answered > 0 ? round(($total_wrong / $total_answered) * 100) : 0;

$page_title = 'Home';
$use_chart  = true;
require_once __DIR__ . '/partials/header.php';
?>

<!-- User greeting -->
<div class="flex items-center gap-3 mb-6 p-4 card shadow-sm">
  <div class="w-14 h-14 rounded-full overflow-hidden flex items-center justify-center text-3xl ring-2 flex-shrink-0"
       style="background:var(--border);ring-color:var(--primary)">
    <?php if (!empty($current_user['avatar'])): ?>
      <img src="uploads/avatars/<?= htmlspecialchars(basename($current_user['avatar'])) ?>"
           alt="Avatar" class="w-full h-full object-cover">
    <?php else: ?>
      👤
    <?php endif; ?>
  </div>
  <div class="flex-1 min-w-0">
    <div class="text-xs font-medium text-muted">Willkommen zurück 👋</div>
    <div class="text-xl font-bold truncate" style="color:var(--text)"><?= htmlspecialchars($current_user['name'] ?? 'Nutzer') ?></div>
  </div>
  <a href="users.php" class="btn btn-secondary text-sm px-3 py-2 flex-shrink-0">Wechseln</a>
</div>

<!-- Donut chart + stats -->
<div class="card p-4 mb-5 shadow-sm">
  <div class="section-title">📦 Vokabeln nach Boxen</div>
  <div class="flex flex-col sm:flex-row items-center gap-6">
    <div class="w-44 h-44 flex-shrink-0">
      <canvas id="chart-boxes"></canvas>
    </div>
    <div class="flex-1 grid grid-cols-2 gap-3 w-full">
      <div class="card p-3 text-center stat-card-red">
        <div class="text-2xl font-bold" style="color:#b91c1c"><?= $box_counts[1] ?></div>
        <div class="text-xs font-medium mt-0.5" style="color:#991b1b">📦 Box 1 (neu)</div>
      </div>
      <div class="card p-3 text-center stat-card-blue">
        <div class="text-2xl font-bold" style="color:#1d4ed8"><?= $box_counts[2] ?></div>
        <div class="text-xs font-medium mt-0.5" style="color:#1e40af">📦 Box 2</div>
      </div>
      <div class="card p-3 text-center stat-card-green">
        <div class="text-2xl font-bold" style="color:#065f46"><?= $box_counts[3] ?></div>
        <div class="text-xs font-medium mt-0.5" style="color:#047857">📦 Box 3 (gut)</div>
      </div>
      <div class="card p-3 text-center stat-card-yellow">
        <div class="text-2xl font-bold" style="color:#92400e"><?= $error_rate ?>%</div>
        <div class="text-xs font-medium mt-0.5" style="color:#78350f">❌ Fehlerrate</div>
      </div>
      <div class="card p-3 text-center stat-card-purple">
        <div class="text-2xl font-bold" style="color:#4c1d95"><?= $total_vocabs ?></div>
        <div class="text-xs font-medium mt-0.5" style="color:#5b21b6">📚 Gesamt</div>
      </div>
      <div class="card p-3 text-center" style="background:linear-gradient(135deg,#e0f2fe,#bae6fd);border-color:#7dd3fc">
        <div class="text-2xl font-bold" style="color:#0c4a6e"><?= $total_unlocked ?></div>
        <div class="text-xs font-medium mt-0.5" style="color:#075985">🔓 Freigeschaltet</div>
      </div>
    </div>
  </div>
</div>

<!-- Training settings -->
<div class="card p-5 mb-5 shadow-sm">
  <div class="section-title">🎯 Training konfigurieren</div>
  <form action="training.php" method="get" id="training-form">

    <div class="mb-4">
      <div class="label">Boxen auswählen</div>
      <div class="flex gap-2 flex-wrap" id="box-toggles">
        <button type="button" class="box-toggle active" data-box="1">📦 Box 1</button>
        <button type="button" class="box-toggle active" data-box="2">📦 Box 2</button>
        <button type="button" class="box-toggle active" data-box="3">📦 Box 3</button>
      </div>
      <input type="hidden" name="boxes" id="boxes-input" value="1,2,3">
    </div>

    <div class="mb-5">
      <div class="label">Übersetzungsrichtung</div>
      <div class="flex gap-2">
        <button type="button" class="box-toggle active" id="dir-de-en" data-dir="de-en">🇩🇪 → 🇬🇧</button>
        <button type="button" class="box-toggle" id="dir-en-de" data-dir="en-de">🇬🇧 → 🇩🇪</button>
      </div>
      <input type="hidden" name="direction" id="direction-input" value="de-en">
    </div>

    <?php if ($total_unlocked === 0): ?>
      <div class="alert alert-error">⚠️ Keine Vokabeln freigeschaltet. Bitte Aktivierungswort in den Einstellungen setzen.</div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary w-full text-lg py-3.5 mt-1"
            <?= $total_unlocked === 0 ? 'disabled' : '' ?>>
      🚀 Vokabeltest starten
    </button>
  </form>
</div>

<!-- Today's progress -->
<?php
$daily_goal = (int)($current_user['settings']['daily_goal'] ?? 30);
$today_total = (int)($user_stats['today']['total'] ?? 0);
$progress_pct = $daily_goal > 0 ? min(100, round(($today_total / $daily_goal) * 100)) : 0;
?>
<div class="card p-5 mb-5 shadow-sm">
  <div class="section-title">📅 Tagesziel</div>
  <div class="flex justify-between text-sm font-medium mb-2" style="color:var(--secondary)">
    <span><?= $today_total ?> / <?= $daily_goal ?> Vokabeln</span>
    <span class="font-bold" style="color:var(--primary)"><?= $progress_pct ?>%</span>
  </div>
  <div class="progress-bar-track">
    <div class="progress-bar-fill" style="width:<?= $progress_pct ?>%"></div>
  </div>
  <?php if ($progress_pct >= 100): ?>
    <p class="text-sm font-semibold mt-3 text-center" style="color:var(--success)">🎉 Tagesziel erreicht!</p>
  <?php endif; ?>
</div>

<!-- Quick nav -->
<div class="grid grid-cols-2 gap-3">
  <a href="stats.php" class="btn btn-secondary flex-col py-5 gap-1 rounded-2xl">
    <span class="text-2xl">📊</span>
    <span class="text-xs font-semibold">Statistik</span>
  </a>
  <a href="settings.php" class="btn btn-secondary flex-col py-5 gap-1 rounded-2xl">
    <span class="text-2xl">⚙️</span>
    <span class="text-xs font-semibold">Einstellungen</span>
  </a>
</div>

<script>
// Box toggles
const boxToggles = document.querySelectorAll('[data-box]');
const boxesInput = document.getElementById('boxes-input');
function updateBoxes() {
    const active = [...boxToggles].filter(b => b.classList.contains('active')).map(b => b.dataset.box);
    boxesInput.value = active.join(',') || '1';
}
boxToggles.forEach(btn => {
    btn.addEventListener('click', () => {
        btn.classList.toggle('active');
        updateBoxes();
    });
});

// Direction toggles
const dirDeEn = document.getElementById('dir-de-en');
const dirEnDe = document.getElementById('dir-en-de');
const dirInput = document.getElementById('direction-input');
dirDeEn.addEventListener('click', () => { dirDeEn.classList.add('active'); dirEnDe.classList.remove('active'); dirInput.value = 'de-en'; });
dirEnDe.addEventListener('click', () => { dirEnDe.classList.add('active'); dirDeEn.classList.remove('active'); dirInput.value = 'en-de'; });

// Chart.js donut
window.STATS_BOXES = <?= json_encode($box_counts) ?>;
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
