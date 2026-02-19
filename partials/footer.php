</main>

<footer class="text-center py-8 text-sm mt-4" style="color:var(--secondary)">
  <div class="max-w-2xl mx-auto px-4">
    <div class="flex items-center justify-center gap-2 mb-1">
      <span class="text-base">📚</span>
      <span class="font-semibold" style="color:var(--text)">Vokabeltrainer</span>
    </div>
    <div style="color:var(--secondary);opacity:0.7">© <?= date('Y') ?> – Lerne täglich, wachse stetig.</div>
  </div>
</footer>

<script src="assets/js/tts.js"></script>
<script src="assets/js/api.js"></script>
<script src="assets/js/app.js"></script>
<?php if (!empty($extra_scripts)): ?>
<?php foreach ($extra_scripts as $s): ?>
<script src="<?= htmlspecialchars(ltrim($s, '/')) ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
