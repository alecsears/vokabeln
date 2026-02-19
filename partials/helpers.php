<?php
declare(strict_types=1);

define('BASE_PATH', realpath(__DIR__ . '/..'));

// Auto-detect the subdirectory the app lives in (e.g. "" for root or "/vokabeln" for a subfolder).
// Uses SCRIPT_NAME of the current entry-point, so it is correct regardless of which PHP file is
// being served.
if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));
}

/**
 * Redirect to a path relative to the app root and exit.
 * Example: redirect('index.php') or redirect('profile.php?user=u0001')
 */
function redirect(string $path): never {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}
function read_json(string $path, mixed $default = []): mixed {
    if (!file_exists($path)) return $default;
    $content = file_get_contents($path);
    if ($content === false || $content === '') return $default;
    $decoded = json_decode($content, true);
    return ($decoded === null) ? $default : $decoded;
}

function write_json_atomic(string $path, mixed $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $tmp = $path . '.tmp.' . uniqid();
    $fp = fopen($tmp, 'w');
    if (!$fp) return false;
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return rename($tmp, $path);
    }
    fclose($fp);
    @unlink($tmp);
    return false;
}

function get_design(): array {
    $data = read_json(BASE_PATH . '/config/design.json', []);
    return array_merge(['css_framework' => 'tailwind', 'theme' => 'light'], $data);
}

function get_settings(): array {
    $data = read_json(BASE_PATH . '/data/settings.json', []);
    return array_merge(['activation_word' => null, 'current_user_id' => 'u0001'], $data);
}

function get_users(): array {
    $data = read_json(BASE_PATH . '/data/users.json', ['users' => [], 'meta' => ['last_user_id' => 'u0000']]);
    return $data;
}

function get_current_user_id(): string {
    $settings = get_settings();
    return $settings['current_user_id'] ?? 'u0001';
}

function get_active_user(): array {
    $uid = get_current_user_id();
    $users = get_users();
    foreach ($users['users'] as $u) {
        if ($u['id'] === $uid) return $u;
    }
    return $users['users'][0] ?? [];
}

function get_vocabularies(): array {
    return read_json(BASE_PATH . '/data/vocabularies.json', ['meta' => [], 'vocabularies' => []]);
}

function get_statistics(): array {
    return read_json(BASE_PATH . '/data/statistics.json', ['users' => []]);
}

function ensure_user_stats(string $user_id): array {
    $stats = get_statistics();
    if (!isset($stats['users'][$user_id])) {
        $stats['users'][$user_id] = [
            'total_correct' => 0,
            'total_wrong' => 0,
            'daily_history' => [],
            'badges' => [],
            'current_session' => null,
            'today' => ['date' => null, 'correct' => 0, 'wrong' => 0, 'total' => 0],
        ];
        write_json_atomic(BASE_PATH . '/data/statistics.json', $stats);
    }
    return $stats;
}

function get_unlocked_vocabularies(array $vocabularies, array $settings): array {
    $activation_word = $settings['activation_word'] ?? null;
    $all = $vocabularies['vocabularies'] ?? [];
    if ($activation_word === null) {
        return array_filter($all, fn($v) => $v['enabled'] === true);
    }
    $unlocked = [];
    foreach ($all as $v) {
        if (!($v['enabled'] ?? false)) continue;
        $unlocked[] = $v;
        if (strtolower(trim($v['en'])) === strtolower(trim($activation_word))) {
            break;
        }
    }
    return array_values($unlocked);
}

function save_vocabulary_answer(string $vocab_id, bool $correct): void {
    $data = get_vocabularies();
    $vocabs = &$data['vocabularies'];
    foreach ($vocabs as &$v) {
        if ($v['id'] !== $vocab_id) continue;
        $v['stats']['last_seen'] = date('Y-m-d');
        if ($correct) {
            $v['stats']['correct'] = ($v['stats']['correct'] ?? 0) + 1;
            // Move up a box (max 3)
            $v['box'] = min(3, ($v['box'] ?? 1) + 1);
        } else {
            $v['stats']['wrong'] = ($v['stats']['wrong'] ?? 0) + 1;
            // Drop back to box 1
            $v['box'] = 1;
        }
        break;
    }
    unset($v);
    write_json_atomic(BASE_PATH . '/data/vocabularies.json', $data);
}

function save_session_stats(string $user_id, array $session_data): void {
    $stats = ensure_user_stats($user_id);
    $stats['users'][$user_id]['current_session'] = $session_data;
    write_json_atomic(BASE_PATH . '/data/statistics.json', $stats);
}

function update_user_daily_stats(string $user_id, bool $correct): void {
    $stats = ensure_user_stats($user_id);
    $today = date('Y-m-d');
    $user = &$stats['users'][$user_id];

    // Roll over day if needed
    if (($user['today']['date'] ?? null) !== $today) {
        // Archive previous day if it had activity
        if (($user['today']['date'] ?? null) !== null && $user['today']['total'] > 0) {
            $user['daily_history'][] = $user['today'];
            // Keep only last 30 days
            if (count($user['daily_history']) > 30) {
                $user['daily_history'] = array_slice($user['daily_history'], -30);
            }
        }
        $user['today'] = ['date' => $today, 'correct' => 0, 'wrong' => 0, 'total' => 0];
    }

    $user['today']['total'] += 1;
    if ($correct) {
        $user['today']['correct'] += 1;
        $user['total_correct'] = ($user['total_correct'] ?? 0) + 1;
    } else {
        $user['today']['wrong'] += 1;
        $user['total_wrong'] = ($user['total_wrong'] ?? 0) + 1;
    }
    unset($user);
    write_json_atomic(BASE_PATH . '/data/statistics.json', $stats);
    check_and_award_badges($user_id);
}

function check_and_award_badges(string $user_id): void {
    $stats = get_statistics();
    $user = $stats['users'][$user_id] ?? null;
    if (!$user) return;

    $badges = $user['badges'] ?? [];
    $existing = array_column($badges, 'id');
    $new_badges = [];

    $total_correct = $user['total_correct'] ?? 0;
    $milestones = [
        ['id' => 'first_correct',  'label' => 'Erster Treffer!',      'icon' => '🎯', 'threshold' => 1],
        ['id' => 'ten_correct',    'label' => '10 richtig!',           'icon' => '⭐', 'threshold' => 10],
        ['id' => 'fifty_correct',  'label' => '50 richtig!',           'icon' => '🏆', 'threshold' => 50],
        ['id' => 'hundred_correct','label' => '100 richtig!',          'icon' => '🥇', 'threshold' => 100],
    ];
    foreach ($milestones as $m) {
        if (!in_array($m['id'], $existing, true) && $total_correct >= $m['threshold']) {
            $new_badges[] = array_merge($m, ['earned_at' => date('Y-m-d')]);
        }
    }

    // Day streak badge – consecutive days with activity
    $history = $user['daily_history'] ?? [];
    $today_entry = $user['today'] ?? [];
    if ($today_entry['total'] > 0) {
        $history[] = $today_entry;
    }
    $streak = 0;
    $check_date = date('Y-m-d');
    $dates = array_column($history, 'date');
    while (in_array($check_date, $dates, true)) {
        $streak++;
        $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
    }
    if ($streak >= 7 && !in_array('week_streak', $existing, true)) {
        $new_badges[] = ['id' => 'week_streak', 'label' => '7 Tage dabei!', 'icon' => '🔥', 'earned_at' => date('Y-m-d')];
    }

    if (empty($new_badges)) return;

    $stats['users'][$user_id]['badges'] = array_merge($badges, $new_badges);
    write_json_atomic(BASE_PATH . '/data/statistics.json', $stats);
}
