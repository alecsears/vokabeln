// Global app helpers
document.addEventListener('DOMContentLoaded', () => {
    // Hamburger menu toggle
    const menuBtn = document.getElementById('menu-btn');
    const flyout  = document.getElementById('flyout-menu');
    if (menuBtn && flyout) {
        menuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            flyout.classList.toggle('hidden');
        });
        document.addEventListener('click', () => flyout.classList.add('hidden'));
        flyout.addEventListener('click', e => e.stopPropagation());
    }
});
