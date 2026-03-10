function initMobileDrawer(){

    var drawer = document.getElementById("hmpro-mobile-drawer");
    if(!drawer) return;

    if(drawer.getAttribute('data-hmpro-drawer-init') === '1') return;
    drawer.setAttribute('data-hmpro-drawer-init','1');

    var toggle =
        document.querySelector('.hmpro-mobile-menu-toggle[aria-controls="hmpro-mobile-drawer"]') ||
        document.querySelector('.hmpro-mobile-menu-toggle');

    function setExpanded(isOpen){
        if(toggle){
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }

    function openDrawer(){
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden','false');
        document.documentElement.classList.add('hmpro-mobile-drawer-open');
        document.documentElement.classList.add('hmpro-lock-scroll');
        setExpanded(true);
    }

    function closeDrawer(){
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden','true');
        document.documentElement.classList.remove('hmpro-mobile-drawer-open');
        document.documentElement.classList.remove('hmpro-lock-scroll');
        setExpanded(false);
    }

    // güvenli başlangıç durumu
    drawer.setAttribute('aria-hidden','true');
    setExpanded(false);

    if(toggle){
        toggle.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();

            if(drawer.classList.contains('is-open')){
                closeDrawer();
            }else{
                openDrawer();
            }
        });
    }

    // Overlay + close button + button içindeki SVG/icon tıklamalarını tek yerden yakala
    drawer.addEventListener('click', function(e){
        var target = e.target;
        if(!target) return;

        var closeEl = null;

        if(typeof target.closest === 'function'){
            closeEl = target.closest('[data-hmpro-close="1"]');
        } else if (
            target.getAttribute &&
            target.getAttribute('data-hmpro-close') === '1'
        ) {
            closeEl = target;
        }

        if(closeEl){
            e.preventDefault();
            e.stopPropagation();
            closeDrawer();
        }
    });

    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape' && drawer.classList.contains('is-open')){
            closeDrawer();
        }
    });
}

if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', initMobileDrawer);
}else{
    initMobileDrawer();
}
