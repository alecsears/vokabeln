<?php
declare(strict_types=1);
require_once __DIR__ . '/partials/helpers.php';

$current_user_id = get_current_user_id();
$stats_data  = get_statistics();
$user_stats  = $stats_data['users'][$current_user_id] ?? [
    'total_correct' => 0, 'total_wrong' => 0,
    'daily_history' => [], 'badges' => [],
    'today' => ['date' => null, 'correct' => 0, 'wrong' => 0, 'total' => 0]
];

$current_user = get_active_user();
$daily_goal   = (int)($current_user['settings']['daily_goal'] ?? 30);

// Build last 7 days history (fill gaps)
$history_raw = $user_stats['daily_history'] ?? [];
$today_entry = $user_stats['today'] ?? ['date' => date('Y-m-d'), 'correct' => 0, 'wrong' => 0, 'total' => 0];
if ($today_entry['date'] === date('Y-m-d') && $today_entry['total'] > 0) {
    $history_raw[] = $today_entry;
}

$history_by_date = [];
foreach ($history_raw as $h) {
    if (!empty($h['date'])) $history_by_date[$h['date']] = $h;
}

$history7 = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $history7[] = $history_by_date[$d] ?? ['date' => $d, 'correct' => 0, 'wrong' => 0, 'total' => 0];
}

// Box distribution
$vocabs_data = get_vocabularies();
$settings    = get_settings();
$unlocked    = get_unlocked_vocabularies($vocabs_data, $settings);
$box_counts  = [1 => 0, 2 => 0, 3 => 0];
foreach ($unlocked as $v) {
    $b = (int)($v['box'] ?? 1);
    if (isset($box_counts[$b])) $box_counts[$b]++;
}

// Today progress
$today_total  = (int)($today_entry['total'] ?? 0);
$progress_pct = $daily_goal > 0 ? min(100, round(($today_total / $daily_goal) * 100)) : 0;

$badges = $user_stats['badges'] ?? [];

$total_correct = $user_stats['total_correct'] ?? 0;
$total_wrong   = $user_stats['total_wrong']   ?? 0;
$total_answered = $total_correct + $total_wrong;
$error_rate = $total_answered > 0 ? round(($total_wrong / $total_answered) * 100) : 0;

$page_title = 'Statistik';
$use_chart  = true;
require_once __DIR__ . '/partials/header.php';
?>

<h1 class="text-2xl font-bold mb-5" style="color:var(--text)">📊 Statistik</h1>

<!-- Summary cards -->
<div class="grid grid-cols-2 gap-3 mb-5">
  <div class="card p-4 text-center">
    <div class="text-3xl font-bold" style="color:var(--success)"><?= $total_correct ?></div>
    <div class="text-xs text-muted mt-1">Richtig gesamt</div>
  </div>
  <div class="card p-4 text-center">
    <div class="text-3xl font-bold" style="color:var(--error)"><?= $total_wrong ?></div>
    <div class="text-xs text-muted mt-1">Falsch gesamt</div>
  </div>
  <div class="card p-4 text-center">
    <div class="text-3xl font-bold" style="color:var(--text)"><?= $error_rate ?>%</div>
    <div class="text-xs text-muted mt-1">Fehlerrate</div>
  </div>
  <div class="card p-4 text-center">
    <div class="text-3xl font-bold" style="color:var(--primary)"><?= $today_total ?></div>
    <div class="text-xs text-muted mt-1">Heute gelernt</div>
  </div>
</div>

<!-- Daily goal progress -->
<div class="card p-4 mb-5">
  <div class="section-title">🎯 Tagesziel: <?= $today_total ?> / <?= $daily_goal ?></div>
  <div class="progress-bar-track">
    <div class="progress-bar-fill" id="progress-fill" style="width:0%"></div>
  </div>
  <?php if ($progress_pct >= 100): ?>
    <p class="text-sm font-semibold mt-2" style="color:var(--success)">🎉 Tagesziel erreicht!</p>
  <?php endif; ?>
</div>

<!-- 7-day history chart -->
<div class="card p-4 mb-5">
  <div class="section-title">📅 Letzte 7 Tage</div>
  <canvas id="chart-history" height="150"></canvas>
</div>

<!-- Box distribution -->
<div class="card p-4 mb-5">
  <div class="section-title">📦 Boxen-Verteilung</div>
  <div class="flex items-center gap-4">
    <div class="w-40 h-40">
      <canvas id="chart-boxes"></canvas>
    </div>
    <div class="flex flex-col gap-2 text-sm">
      <div class="flex items-center gap-2">
        <span class="w-3 h-3 rounded-full inline-block" style="background:#ef4444"></span>
        Box 1: <?= $box_counts[1] ?>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-3 h-3 rounded-full inline-block" style="background:#f59e0b"></span>
        Box 2: <?= $box_counts[2] ?>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-3 h-3 rounded-full inline-block" style="background:#10b981"></span>
        Box 3: <?= $box_counts[3] ?>
      </div>
    </div>
  </div>
</div>

<!-- Badges -->
<div class="card p-4 mb-5">
  <div class="section-title">🏅 Abzeichen</div>
  <?php if (empty($badges)): ?>
    <p class="text-sm text-muted">Noch keine Abzeichen. Lerne weiter!</p>
  <?php else: ?>
    <div class="grid grid-cols-3 gap-3">
      <?php foreach ($badges as $badge): ?>
        <div class="badge-card">
          <div class="badge-icon"><?= htmlspecialchars($badge['icon'] ?? '🏅') ?></div>
          <div class="text-xs font-semibold text-center" style="color:var(--text)"><?= htmlspecialchars($badge['label'] ?? '') ?></div>
          <div class="text-xs text-muted"><?= htmlspecialchars($badge['earned_at'] ?? '') ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
window.STATS_HISTORY  = <?= json_encode($history7) ?>;
window.STATS_BOXES    = <?= json_encode($box_counts) ?>;
window.STATS_PROGRESS = <?= $progress_pct ?>;
</script>

<?php
$extra_scripts = ['/assets/js/stats.js'];
require_once __DIR__ . '/partials/footer.php';
?>
