<?php
declare(strict_types=1);
require_once __DIR__ . '/partials/helpers.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    if ($post_action === 'save_activation') {
        $word = trim($_POST['activation_word'] ?? '');
        $vocabs = get_vocabularies();
        $all_en = array_map(fn($v) => strtolower(trim($v['en'])), $vocabs['vocabularies'] ?? []);

        if ($word === '') {
            // Clear activation word – unlock all
            $settings = get_settings();
            $settings['activation_word'] = null;
            write_json_atomic(BASE_PATH . '/data/settings.json', $settings);
            $success = 'Aktivierungswort entfernt – alle Vokabeln freigeschaltet.';
        } elseif (in_array(strtolower($word), $all_en, true)) {
            $settings = get_settings();
            $settings['activation_word'] = $word;
            write_json_atomic(BASE_PATH . '/data/settings.json', $settings);
            $success = 'Aktivierungswort gespeichert: „' . htmlspecialchars($word) . '"';
        } else {
            $error = 'Dieses Wort/Satz existiert nicht in den Vokabeln (englische Seite).';
        }
    }

    if ($post_action === 'save_design') {
        $allowed_frameworks = ['tailwind'];
        $framework = in_array($_POST['css_framework'] ?? '', $allowed_frameworks, true)
            ? $_POST['css_framework'] : 'tailwind';
        $theme = in_array($_POST['theme'] ?? '', ['light', 'soft', 'contrast'], true)
            ? $_POST['theme'] : 'light';
        $design = ['css_framework' => $framework, 'theme' => $theme];
        write_json_atomic(BASE_PATH . '/config/design.json', $design);

        // Also update current user's settings
        $uid = get_current_user_id();
        $users_data = get_users();
        foreach ($users_data['users'] as &$u) {
            if ($u['id'] === $uid) {
                $u['settings']['theme']         = $theme;
                $u['settings']['css_framework'] = $framework;
                break;
            }
        }
        unset($u);
        write_json_atomic(BASE_PATH . '/data/users.json', $users_data);
        $success = 'Design-Einstellungen gespeichert.';
        // Redirect to reload with new theme
        header('Location: /settings.php?saved=1');
        exit;
    }
}

if (isset($_GET['saved'])) $success = 'Einstellungen gespeichert.';

$settings = get_settings();
$design   = get_design();

$page_title = 'Einstellungen';
require_once __DIR__ . '/partials/header.php';
?>

<h1 class="text-2xl font-bold mb-5" style="color:var(--text)">⚙️ Einstellungen</h1>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Activation word -->
<div class="card p-4 mb-5">
  <div class="section-title">🔑 Aktivierungswort</div>
  <p class="text-sm text-muted mb-3">
    Gib einen englischen Satz aus den Vokabeln ein, bis zu dem Vokabeln freigeschaltet werden.
    Leer lassen = alle Vokabeln freischalten.
  </p>
  <form method="post">
    <input type="hidden" name="post_action" value="save_activation">
    <label class="label" for="activation_word">Aktivierungswort (englisch)</label>
    <input type="text" id="activation_word" name="activation_word"
           class="input mb-3"
           value="<?= htmlspecialchars($settings['activation_word'] ?? '') ?>"
           placeholder="z.B. I'm from Greenwich.">
    <button type="submit" class="btn btn-primary w-full">Speichern</button>
  </form>
</div>

<!-- Design settings -->
<div class="card p-4 mb-5">
  <div class="section-title">🎨 Design</div>
  <form method="post">
    <input type="hidden" name="post_action" value="save_design">

    <div class="mb-4">
      <label class="label" for="css_framework">CSS-Framework</label>
      <select id="css_framework" name="css_framework" class="input">
        <option value="tailwind" <?= ($design['css_framework'] ?? '') === 'tailwind' ? 'selected' : '' ?>>Tailwind CSS</option>
      </select>
    </div>

    <div class="mb-4">
      <label class="label" for="theme">Farbschema</label>
      <select id="theme" name="theme" class="input">
        <option value="light"    <?= ($design['theme'] ?? '') === 'light'    ? 'selected' : '' ?>>☀️ Hell (Light)</option>
        <option value="soft"     <?= ($design['theme'] ?? '') === 'soft'     ? 'selected' : '' ?>>🌸 Sanft (Soft)</option>
        <option value="contrast" <?= ($design['theme'] ?? '') === 'contrast' ? 'selected' : '' ?>>🌙 Kontrast (Dark)</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary w-full">Design speichern</button>
  </form>
</div>

<!-- Vocab list preview -->
<?php
$vocabs  = get_vocabularies();
$unlocked = array_values(get_unlocked_vocabularies($vocabs, $settings));
?>
<div class="card p-4 mb-5">
  <div class="section-title">📋 Freigeschaltete Vokabeln (<?= count($unlocked) ?>)</div>
  <div class="overflow-auto max-h-72">
    <table class="w-full text-sm">
      <thead>
        <tr style="color:var(--secondary)">
          <th class="text-left py-1 pr-2">Deutsch</th>
          <th class="text-left py-1">Englisch</th>
          <th class="text-center py-1">Box</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($unlocked as $v): ?>
          <tr style="border-top:1px solid var(--border)">
            <td class="py-1 pr-2"><?= htmlspecialchars($v['de']) ?></td>
            <td class="py-1"><?= htmlspecialchars($v['en']) ?></td>
            <td class="text-center py-1 font-bold"><?= (int)($v['box'] ?? 1) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
