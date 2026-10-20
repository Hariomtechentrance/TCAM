/* ============================================================
   TCAM — Site-wide Header (mobile nav drawer behaviour)
   Shared by every page that includes the site header.
   ============================================================ */
function toggleMenu() {
    const menu = document.getElementById('navMenu');
    const ham  = document.getElementById('hamburger');
    const overlay = document.getElementById('navOverlay');
    const open = menu.classList.toggle('open');
    ham.classList.toggle('open', open);
    overlay.classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
}
function closeMenu() {
    document.getElementById('navMenu').classList.remove('open');
    document.getElementById('hamburger').classList.remove('open');
    document.getElementById('navOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
// Close on Escape key
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });
// Close when resizing to desktop
window.addEventListener('resize', () => { if (window.innerWidth > 768) closeMenu(); });
// Close on outside tap (mobile only)
document.addEventListener('click', function(e) {
    if (window.innerWidth > 768) return;
    const menu = document.getElementById('navMenu');
    const ham  = document.getElementById('hamburger');
    if (!menu || !ham) return;
    if (!menu.classList.contains('open')) return;
    if (!menu.contains(e.target) && !ham.contains(e.target)) closeMenu();
});
