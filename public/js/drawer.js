/**
 * Drawer Menu Management
 * Handles open/close animations and overlay interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    const drawer = document.querySelector('.sidebar-drawer');
    const overlay = document.querySelector('.drawer-overlay');
    const drawerToggle = document.querySelector('.mobile-menu-toggle');
    const drawerClose = document.querySelector('.drawer-close');
    const drawerLinks = document.querySelectorAll('.drawer-menu a.drawer-link');
    
    if (!drawer) return;

    function getHeader() {
        return drawerToggle ? drawerToggle.closest('header') : null;
    }

    function openDrawer() {
        drawer.classList.add('drawer-open');
        if (overlay) overlay.classList.add('overlay-visible');
        if (drawerToggle) {
            drawerToggle.setAttribute('aria-expanded', 'true');
            const icon = drawerToggle.querySelector('i');
            if (icon) { icon.classList.replace('fa-bars', 'fa-times'); }
        }
        const hdr = getHeader();
        if (hdr) hdr.classList.add('menu-is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer.classList.remove('drawer-open');
        if (overlay) overlay.classList.remove('overlay-visible');
        if (drawerToggle) {
            drawerToggle.setAttribute('aria-expanded', 'false');
            const icon = drawerToggle.querySelector('i');
            if (icon) { icon.classList.replace('fa-times', 'fa-bars'); }
        }
        const hdr = getHeader();
        if (hdr) hdr.classList.remove('menu-is-open');
        document.body.style.overflow = 'auto';
    }

    // Toggle drawer on button click — only if header.php inline script isn't handling this
    // (guard: check if openDrawer is already defined globally by the inline script)
    if (drawerToggle && typeof window._drawerInlineInit === 'undefined') {
        drawerToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (drawer.classList.contains('drawer-open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });
    }

    if (drawerClose) {
        drawerClose.addEventListener('click', closeDrawer);
    }

    if (overlay) {
        overlay.addEventListener('click', closeDrawer);
    }

    drawerLinks.forEach(link => {
        if (!link.closest('.language-selector')) {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (!href || href === '#' || href === window.location.pathname) {
                    e.preventDefault();
                    return;
                }
                closeDrawer();
            });
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && drawer.classList.contains('drawer-open')) {
            closeDrawer();
        }
    });

    if (drawer) {
        drawer.addEventListener('wheel', function(e) {
            if (this.scrollHeight <= this.clientHeight) {
                e.preventDefault();
            }
        }, { passive: false });
    }
});
