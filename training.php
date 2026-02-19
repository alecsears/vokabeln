<?php
declare(strict_types=1);
require_once __DIR__ . '/partials/helpers.php';

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$action  = $_GET['action'] ?? '';

// ── AJAX handlers ──────────────────────────────────────────────
if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
    $body = (string)file_get_contents('php://input');
    $data = json_decode($body, true) ?? [];

    if ($action === 'answer') {
        $vocab_id  = isset($data['vocab_id']) ? preg_replace('/[^a-z0-9]/i', '', (string)$data['vocab_id']) : '';
        $correct   = (bool)($data['correct'] ?? false);
        $user_id   = isset($data['user_id']) ? preg_replace('/[^a-z0-9]/i', '', (string)$data['user_id']) : get_current_user_id();
        $session   = is_array($data['session'] ?? null) ? $data['session'] : [];

        if ($vocab_id !== '') {
            save_vocabulary_answer($vocab_id, $correct);
            update_user_daily_stats($user_id, $correct);
            if (!empty($session)) {
                save_session_stats($user_id, $session);
            }
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'pause') {
        $user_id = isset($data['user_id']) ? preg_replace('/[^a-z0-9]/i', '', (string)$data['user_id']) : get_current_user_id();
        $session = is_array($data['session'] ?? null) ? $data['session'] : [];
        save_session_stats($user_id, array_merge($session, ['paused_at' => date('c')]));
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'end_set') {
        $user_id = isset($data['user_id']) ? preg_replace('/[^a-z0-9]/i', '', (string)$data['user_id']) : get_current_user_id();
        $session = is_array($data['session'] ?? null) ? $data['session'] : $data;
        save_session_stats($user_id, array_merge($session, ['completed_at' => date('c'), 'finished' => true]));
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    exit;
}

// ── Build training set ────────────────────────────────────────
$settings    = get_settings();
$vocabs_data = get_vocabularies();
$unlocked    = array_values(get_unlocked_vocabularies($vocabs_data, $settings));

// Filter by selected boxes
$boxes_param = $_GET['boxes'] ?? '1,2,3';
$boxes_raw   = explode(',', $boxes_param);
$selected_boxes = array_filter(array_map('intval', $boxes_raw), fn($b) => in_array($b, [1, 2, 3], true));
if (empty($selected_boxes)) $selected_boxes = [1, 2, 3];

$filtered = array_values(array_filter($unlocked, fn($v) => in_array((int)($v['box'] ?? 1), $selected_boxes, true)));

// Shuffle and take up to 30
shuffle($filtered);
$training_set = array_slice($filtered, 0, 30);

$direction    = ($_GET['direction'] ?? 'de-en') === 'en-de' ? 'en-de' : 'de-en';
$lang_front   = $direction === 'de-en' ? 'de' : 'en';

$current_user_id = get_current_user_id();

// Save session start
if (!empty($training_set)) {
    save_session_stats($current_user_id, [
        'started_at' => date('c'),
        'set_size'   => count($training_set),
        'direction'  => $direction,
        'boxes'      => array_values($selected_boxes),
        'finished'   => false,
    ]);
}

$page_title = 'Training';
require_once __DIR__ . '/partials/header.php';
?>

<?php if (empty($training_set)): ?>
<div class="card p-6 text-center">
  <div class="text-4xl mb-3">📭</div>
  <p class="font-semibold mb-4" style="color:var(--text)">Keine Vokabeln in den ausgewählten Boxen.</p>
  <a href="/index.php" class="btn btn-primary">← Zurück</a>
</div>
<?php else: ?>

<!-- Progress bar -->
<div class="mb-4">
  <div class="flex justify-between text-sm font-medium mb-2" style="color:var(--secondary)">
    <span id="card-counter" class="font-semibold">1 / <?= count($training_set) ?></span>
    <span id="progress-text">0 / <?= count($training_set) ?></span>
  </div>
  <div class="progress-bar-track">
    <div class="progress-bar-fill" id="progress-fill" style="width:0%"></div>
  </div>
</div>

<!-- Header buttons -->
<div class="flex gap-2 mb-5 justify-end">
  <button id="btn-pause" class="btn btn-secondary text-sm px-4 py-2 rounded-xl">⏸ Pause</button>
  <button id="btn-end-set" class="btn btn-secondary text-sm px-4 py-2 rounded-xl">🏁 Abbrechen</button>
</div>

<!-- Training wrapper -->
<div id="training-wrap">

  <!-- Card stack decorations (behind) -->
  <div class="card-stack mb-6">
    <div class="card-stack-item"></div>
    <div class="card-stack-item"></div>

    <!-- Active card (3D flip) -->
    <div class="card-scene active" id="card-scene">
      <div class="card-flip" id="card-flip" role="button" aria-label="Karte umdrehen" tabindex="0">

        <!-- Front face -->
        <div class="card-face card-face--front">
          <div class="text-xs font-bold mb-3 uppercase tracking-widest px-3 py-1 rounded-full"
               style="background:var(--border);color:var(--secondary)">
            <?= $lang_front === 'de' ? '🇩🇪 Deutsch' : '🇬🇧 Englisch' ?>
          </div>
          <div id="front-word" class="text-3xl font-extrabold mb-5" style="color:var(--text)"></div>
          <button id="btn-tts-front" type="button"
                  class="btn btn-secondary px-5 py-2.5 rounded-2xl text-lg" aria-label="Vorlesen">🔊</button>
          <p class="text-xs mt-5 font-medium" style="color:var(--secondary)">Tippen zum Umdrehen ↕</p>
        </div>

        <!-- Back face -->
        <div class="card-face card-face--back">
          <div class="text-xs font-bold mb-3 uppercase tracking-widest px-3 py-1 rounded-full"
               style="background:var(--border);color:var(--secondary)">
            <?= $lang_front === 'de' ? '🇬🇧 Englisch' : '🇩🇪 Deutsch' ?>
          </div>
          <div id="back-word" class="text-3xl font-extrabold mb-5" style="color:var(--text)"></div>
          <button id="btn-tts-back" type="button"
                  class="btn btn-secondary px-5 py-2.5 rounded-2xl text-lg" aria-label="Vorlesen">🔊</button>
        </div>

      </div><!-- card-flip -->
    </div><!-- card-scene -->
  </div><!-- card-stack -->

  <!-- Action bar (shown after flip) -->
  <div id="action-bar" class="hidden flex gap-3 mt-5">
    <button id="btn-wrong"   class="btn btn-error   flex-1 py-4 text-lg rounded-2xl">✗ Falsch</button>
    <button id="btn-correct" class="btn btn-success flex-1 py-4 text-lg rounded-2xl">✓ Richtig</button>
  </div>

</div><!-- training-wrap -->

<!-- Result screen (hidden initially) -->
<div id="result-screen" class="hidden card p-8 text-center">
  <div class="text-6xl mb-4" style="animation:bounce-in 0.5s cubic-bezier(0.16,1,0.3,1)">🏆</div>
  <h2 class="text-2xl font-extrabold mb-2" style="color:var(--text)">Geschafft!</h2>
  <p class="mb-6 text-muted">Du hast alle <span id="result-total" class="font-bold" style="color:var(--primary)"></span> Karten bearbeitet.</p>
  <div class="grid grid-cols-2 gap-4 mb-7">
    <div class="card p-4 stat-card-green">
      <div class="text-4xl font-extrabold" style="color:#065f46" id="result-correct">0</div>
      <div class="text-sm font-semibold mt-1" style="color:#047857">✓ Richtig</div>
    </div>
    <div class="card p-4 stat-card-red">
      <div class="text-4xl font-extrabold" style="color:#991b1b" id="result-wrong">0</div>
      <div class="text-sm font-semibold mt-1" style="color:#b91c1c">✗ Falsch</div>
    </div>
  </div>
  <div class="flex gap-3 justify-center">
    <a href="/training.php?boxes=<?= htmlspecialchars($boxes_param) ?>&direction=<?= htmlspecialchars($direction) ?>"
       class="btn btn-primary px-6 py-3 rounded-2xl">🔁 Nochmal</a>
    <a href="/index.php" class="btn btn-secondary px-6 py-3 rounded-2xl">🏠 Home</a>
  </div>
</div>

<?php endif; ?>

<!-- Inject training data for JS -->
<script>
window.TRAINING_SET       = <?= json_encode(array_map(fn($v) => ['id' => $v['id'], 'de' => $v['de'], 'en' => $v['en']], $training_set), JSON_UNESCAPED_UNICODE) ?>;
window.TRAINING_USER_ID   = <?= json_encode($current_user_id) ?>;
window.TRAINING_LANG_FRONT = <?= json_encode($lang_front) ?>;
</script>

<?php
$extra_scripts = ['/assets/js/training.js'];
require_once __DIR__ . '/partials/footer.php';
?>
