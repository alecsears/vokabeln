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
<div class="flex items-center gap-3 mb-6">
  <div class="w-14 h-14 rounded-full overflow-hidden flex items-center justify-center text-3xl"
       style="background:var(--border)">
    <?php if (!empty($current_user['avatar'])): ?>
      <img src="/uploads/avatars/<?= htmlspecialchars(basename($current_user['avatar'])) ?>"
           alt="Avatar" class="w-full h-full object-cover">
    <?php else: ?>
      👤
    <?php endif; ?>
  </div>
  <div>
    <div class="text-sm text-muted">Hallo,</div>
    <div class="text-xl font-bold" style="color:var(--text)"><?= htmlspecialchars($current_user['name'] ?? 'Nutzer') ?></div>
  </div>
  <a href="/users.php" class="ml-auto btn btn-secondary text-sm px-3 py-2">Wechseln</a>
</div>

<!-- Donut chart + stats -->
<div class="card p-4 mb-5 shadow-sm">
  <div class="section-title">📦 Vokabeln nach Boxen</div>
  <div class="flex flex-col sm:flex-row items-center gap-6">
    <div class="w-48 h-48 flex-shrink-0">
      <canvas id="chart-boxes"></canvas>
    </div>
    <div class="flex-1 grid grid-cols-2 gap-3 w-full">
      <div class="card p-3 text-center">
        <div class="text-2xl font-bold" style="color:var(--error)"><?= $box_counts[1] ?></div>
        <div class="text-xs text-muted">Box 1 (neu)</div>
      </div>
      <div class="card p-3 text-center">
        <div class="text-2xl font-bold" style="color:var(--primary)"><?= $box_counts[2] ?></div>
        <div class="text-xs text-muted">Box 2</div>
      </div>
      <div class="card p-3 text-center">
        <div class="text-2xl font-bold" style="color:var(--success)"><?= $box_counts[3] ?></div>
        <div class="text-xs text-muted">Box 3 (gut)</div>
      </div>
      <div class="card p-3 text-center">
        <div class="text-2xl font-bold" style="color:var(--secondary)"><?= $error_rate ?>%</div>
        <div class="text-xs text-muted">Fehlerrate</div>
      </div>
      <div class="card p-3 text-center">
        <div class="text-2xl font-bold" style="color:var(--text)"><?= $total_vocabs ?></div>
        <div class="text-xs text-muted">Gesamt</div>
      </div>
      <div class="card p-3 text-center">
        <div class="text-2xl font-bold" style="color:var(--text)"><?= $total_unlocked ?></div>
        <div class="text-xs text-muted">Freigeschaltet</div>
      </div>
    </div>
  </div>
</div>

<!-- Training settings -->
<div class="card p-4 mb-5 shadow-sm">
  <div class="section-title">🎯 Training konfigurieren</div>
  <form action="/training.php" method="get" id="training-form">

    <div class="mb-4">
      <div class="label">Boxen auswählen</div>
      <div class="flex gap-2 flex-wrap" id="box-toggles">
        <button type="button" class="box-toggle active" data-box="1">Box 1</button>
        <button type="button" class="box-toggle active" data-box="2">Box 2</button>
        <button type="button" class="box-toggle active" data-box="3">Box 3</button>
      </div>
      <input type="hidden" name="boxes" id="boxes-input" value="1,2,3">
    </div>

    <div class="mb-4">
      <div class="label">Übersetzungsrichtung</div>
      <div class="flex gap-2">
        <button type="button" class="box-toggle active" id="dir-de-en" data-dir="de-en">🇩🇪 → 🇬🇧</button>
        <button type="button" class="box-toggle" id="dir-en-de" data-dir="en-de">🇬🇧 → 🇩🇪</button>
      </div>
      <input type="hidden" name="direction" id="direction-input" value="de-en">
    </div>

    <?php if ($total_unlocked === 0): ?>
      <div class="alert alert-error">Keine Vokabeln freigeschaltet. Bitte Aktivierungswort in den Einstellungen setzen.</div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary w-full text-lg py-3 mt-2"
            <?= $total_unlocked === 0 ? 'disabled' : '' ?>>
      📚 Vokabeltest starten
    </button>
  </form>
</div>

<!-- Today's progress -->
<?php
$daily_goal = (int)($current_user['settings']['daily_goal'] ?? 30);
$today_total = (int)($user_stats['today']['total'] ?? 0);
$progress_pct = $daily_goal > 0 ? min(100, round(($today_total / $daily_goal) * 100)) : 0;
?>
<div class="card p-4 mb-5 shadow-sm">
  <div class="section-title">📅 Tagesziel</div>
  <div class="flex justify-between text-sm mb-2" style="color:var(--secondary)">
    <span><?= $today_total ?> / <?= $daily_goal ?> Vokabeln</span>
    <span><?= $progress_pct ?>%</span>
  </div>
  <div class="progress-bar-track">
    <div class="progress-bar-fill" style="width:<?= $progress_pct ?>%"></div>
  </div>
</div>

<!-- Quick nav -->
<div class="grid grid-cols-2 gap-3">
  <a href="/stats.php" class="btn btn-secondary flex-col py-4">📊<span class="text-xs mt-1">Statistik</span></a>
  <a href="/settings.php" class="btn btn-secondary flex-col py-4">⚙️<span class="text-xs mt-1">Einstellungen</span></a>
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
