// Global app helpers
document.addEventListener('DOMContentLoaded', () => {
    // Hamburger menu toggle
    const menuBtn = document.getElementById('menu-btn');
    const flyout  = document.getElementById('flyout-menu');
    if (menuBtn && flyout) {
        menuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !flyout.classList.contains('hidden');
            flyout.classList.toggle('hidden');
            menuBtn.classList.toggle('open', !isOpen);
        });
        document.addEventListener('click', () => {
            flyout.classList.add('hidden');
            menuBtn.classList.remove('open');
        });
        flyout.addEventListener('click', e => e.stopPropagation());
    }
});
