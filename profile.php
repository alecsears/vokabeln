<?php
declare(strict_types=1);
require_once __DIR__ . '/partials/helpers.php';

$uid = preg_replace('/[^a-z0-9]/i', '', $_GET['user'] ?? get_current_user_id());

$users_data = get_users();
$profile_user = null;
foreach ($users_data['users'] as $u) {
    if ($u['id'] === $uid) { $profile_user = $u; break; }
}

if ($profile_user === null) {
    header('Location: /users.php');
    exit;
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    // ── Update name ────────────────────────────────────────────
    if ($post_action === 'update_name') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $error = 'Name darf nicht leer sein.';
        } else {
            foreach ($users_data['users'] as &$u) {
                if ($u['id'] === $uid) { $u['name'] = $name; break; }
            }
            unset($u);
            write_json_atomic(BASE_PATH . '/data/users.json', $users_data);
            $profile_user['name'] = $name;
            $success = 'Name gespeichert.';
        }
    }

    // ── Upload avatar ──────────────────────────────────────────
    if ($post_action === 'upload_avatar') {
        $file = $_FILES['avatar'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            if (!in_array($mime, $allowed_mime, true)) {
                $error = 'Nur JPG, PNG, GIF oder WebP erlaubt.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = 'Datei zu groß (max. 2 MB).';
            } else {
                $ext = match($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/gif'  => 'gif',
                    'image/webp' => 'webp',
                    default      => 'jpg',
                };
                $avatar_dir  = BASE_PATH . '/uploads/avatars/';
                $avatar_file = $uid . '.' . $ext;
                $avatar_path = $avatar_dir . $avatar_file;
                if (!is_dir($avatar_dir)) mkdir($avatar_dir, 0755, true);
                if (move_uploaded_file($file['tmp_name'], $avatar_path)) {
                    foreach ($users_data['users'] as &$u) {
                        if ($u['id'] === $uid) { $u['avatar'] = $avatar_file; break; }
                    }
                    unset($u);
                    write_json_atomic(BASE_PATH . '/data/users.json', $users_data);
                    $profile_user['avatar'] = $avatar_file;
                    $success = 'Avatar gespeichert.';
                } else {
                    $error = 'Upload fehlgeschlagen.';
                }
            }
        } else {
            $error = 'Kein Bild gewählt oder Upload-Fehler.';
        }
    }

    // ── Update settings ────────────────────────────────────────
    if ($post_action === 'update_settings') {
        $daily_goal = max(1, min(200, (int)($_POST['daily_goal'] ?? 30)));
        $theme      = in_array($_POST['theme'] ?? '', ['light', 'soft', 'contrast'], true)
                      ? $_POST['theme'] : 'light';
        foreach ($users_data['users'] as &$u) {
            if ($u['id'] === $uid) {
                $u['settings']['daily_goal'] = $daily_goal;
                $u['settings']['theme']      = $theme;
                $profile_user = $u;
                break;
            }
        }
        unset($u);
        write_json_atomic(BASE_PATH . '/data/users.json', $users_data);
        // If this is the current user, also update design.json
        if ($uid === get_current_user_id()) {
            $design = get_design();
            $design['theme'] = $theme;
            write_json_atomic(BASE_PATH . '/config/design.json', $design);
            header('Location: /profile.php?user=' . urlencode($uid) . '&saved=1');
            exit;
        }
        $success = 'Einstellungen gespeichert.';
    }

    // ── Delete user ────────────────────────────────────────────
    if ($post_action === 'delete_user') {
        if (count($users_data['users']) <= 1) {
            $error = 'Mindestens ein Nutzer muss vorhanden bleiben.';
        } else {
            $users_data['users'] = array_values(array_filter(
                $users_data['users'], fn($u) => $u['id'] !== $uid
            ));
            write_json_atomic(BASE_PATH . '/data/users.json', $users_data);

            $stats = get_statistics();
            unset($stats['users'][$uid]);
            write_json_atomic(BASE_PATH . '/data/statistics.json', $stats);

            // Switch current user if needed
            $settings = get_settings();
            if ($settings['current_user_id'] === $uid) {
                $settings['current_user_id'] = $users_data['users'][0]['id'] ?? 'u0001';
                write_json_atomic(BASE_PATH . '/data/settings.json', $settings);
            }
            header('Location: /users.php');
            exit;
        }
    }
}

if (isset($_GET['saved'])) $success = 'Einstellungen gespeichert.';

// Switch active user
if (isset($_GET['switch']) && $_GET['switch'] === '1') {
    $settings = get_settings();
    $settings['current_user_id'] = $uid;
    write_json_atomic(BASE_PATH . '/data/settings.json', $settings);
    header('Location: /index.php');
    exit;
}

$user_stats  = get_statistics();
$u_stats     = $user_stats['users'][$uid] ?? ['total_correct' => 0, 'total_wrong' => 0, 'badges' => []];

$page_title = htmlspecialchars($profile_user['name'] ?? 'Profil');
require_once __DIR__ . '/partials/header.php';
?>

<div class="flex items-center gap-3 mb-5">
  <div class="w-16 h-16 rounded-full overflow-hidden flex items-center justify-center text-3xl"
       style="background:var(--border)">
    <?php if (!empty($profile_user['avatar']) && file_exists(BASE_PATH . '/uploads/avatars/' . basename($profile_user['avatar']))): ?>
      <img src="/uploads/avatars/<?= htmlspecialchars(basename($profile_user['avatar'])) ?>"
           alt="Avatar" class="w-full h-full object-cover">
    <?php else: ?>👤<?php endif; ?>
  </div>
  <div>
    <h1 class="text-2xl font-bold" style="color:var(--text)"><?= htmlspecialchars($profile_user['name']) ?></h1>
    <div class="text-sm text-muted">ID: <?= htmlspecialchars($uid) ?></div>
  </div>
  <?php if ($uid !== get_current_user_id()): ?>
    <a href="/profile.php?user=<?= htmlspecialchars($uid) ?>&switch=1"
       class="ml-auto btn btn-primary text-sm px-3 py-2">Als <?= htmlspecialchars($profile_user['name']) ?> spielen</a>
  <?php endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Quick stats -->
<div class="grid grid-cols-3 gap-3 mb-5">
  <div class="card p-3 text-center">
    <div class="text-xl font-bold" style="color:var(--success)"><?= (int)($u_stats['total_correct'] ?? 0) ?></div>
    <div class="text-xs text-muted">Richtig</div>
  </div>
  <div class="card p-3 text-center">
    <div class="text-xl font-bold" style="color:var(--error)"><?= (int)($u_stats['total_wrong'] ?? 0) ?></div>
    <div class="text-xs text-muted">Falsch</div>
  </div>
  <div class="card p-3 text-center">
    <div class="text-xl font-bold" style="color:var(--primary)"><?= count($u_stats['badges'] ?? []) ?></div>
    <div class="text-xs text-muted">Abzeichen</div>
  </div>
</div>

<!-- Update name -->
<div class="card p-4 mb-4">
  <div class="section-title">✏️ Name ändern</div>
  <form method="post">
    <input type="hidden" name="post_action" value="update_name">
    <label class="label" for="name">Name</label>
    <input type="text" id="name" name="name" class="input mb-3"
           value="<?= htmlspecialchars($profile_user['name']) ?>" maxlength="50" required>
    <button type="submit" class="btn btn-primary w-full">Speichern</button>
  </form>
</div>

<!-- Avatar upload -->
<div class="card p-4 mb-4">
  <div class="section-title">🖼️ Avatar</div>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="post_action" value="upload_avatar">
    <label class="label" for="avatar">Bild wählen (JPG, PNG, GIF, WebP · max. 2 MB)</label>
    <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp"
           class="input mb-3">
    <button type="submit" class="btn btn-primary w-full">Hochladen</button>
  </form>
</div>

<!-- Settings -->
<div class="card p-4 mb-4">
  <div class="section-title">⚙️ Einstellungen</div>
  <form method="post">
    <input type="hidden" name="post_action" value="update_settings">

    <label class="label" for="daily_goal">Tagesziel (Vokabeln)</label>
    <input type="number" id="daily_goal" name="daily_goal" class="input mb-3"
           value="<?= (int)($profile_user['settings']['daily_goal'] ?? 30) ?>"
           min="1" max="200">

    <label class="label" for="theme">Farbschema</label>
    <select id="theme" name="theme" class="input mb-3">
      <option value="light"    <?= ($profile_user['settings']['theme'] ?? '') === 'light'    ? 'selected' : '' ?>>☀️ Hell</option>
      <option value="soft"     <?= ($profile_user['settings']['theme'] ?? '') === 'soft'     ? 'selected' : '' ?>>🌸 Sanft</option>
      <option value="contrast" <?= ($profile_user['settings']['theme'] ?? '') === 'contrast' ? 'selected' : '' ?>>🌙 Kontrast</option>
    </select>

    <button type="submit" class="btn btn-primary w-full">Einstellungen speichern</button>
  </form>
</div>

<!-- Delete user -->
<div class="card p-4 mb-4" style="border-color:var(--error)">
  <div class="section-title" style="color:var(--error)">🗑️ Nutzer löschen</div>
  <p class="text-sm text-muted mb-3">Diese Aktion kann nicht rückgängig gemacht werden.</p>
  <form method="post" onsubmit="return confirm('Nutzer wirklich löschen?')">
    <input type="hidden" name="post_action" value="delete_user">
    <button type="submit" class="btn btn-error w-full">Nutzer löschen</button>
  </form>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
