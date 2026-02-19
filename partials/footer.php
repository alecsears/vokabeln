</main>

<footer class="text-center py-6 text-sm" style="color:var(--secondary)">
  © <?= date('Y') ?> Vokabeltrainer
</footer>

<script src="/assets/js/tts.js"></script>
<script src="/assets/js/api.js"></script>
<script src="/assets/js/app.js"></script>
<?php if (!empty($extra_scripts)): ?>
<?php foreach ($extra_scripts as $s): ?>
<script src="<?= htmlspecialchars($s) ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
