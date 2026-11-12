/*
 * Fight pages on touch screens: a swipe to the left opens the next fight, one to
 * the right the previous fight. Their pages come in data-next and data-prev.
 * Swipes that start in something that scrolls sideways are left to it.
 */

(function () {
    const page = document.querySelector('.block-page[data-next]');
    if (!page) {
        return;
    }
    const MIN_DISTANCE = 60;
    let start = null;

    function scrollsSideways(element) {
        for (; element && element !== document.body; element = element.parentElement) {
            if (element.scrollWidth > element.clientWidth
                && /auto|scroll/.test(getComputedStyle(element).overflowX)) {
                return true;
            }
        }
        return false;
    }

    document.addEventListener('touchstart', function (event) {
        start = event.touches.length === 1 && !scrollsSideways(event.target)
            ? { x: event.touches[0].clientX, y: event.touches[0].clientY }
            : null;
    }, { passive: true });

    document.addEventListener('touchmove', function (event) {
        if (event.touches.length > 1) {
            start = null;
        }
    }, { passive: true });

    document.addEventListener('touchend', function (event) {
        if (!start) {
            return;
        }
        const dx = event.changedTouches[0].clientX - start.x;
        const dy = event.changedTouches[0].clientY - start.y;
        start = null;
        if (Math.abs(dx) < MIN_DISTANCE || Math.abs(dx) < Math.abs(dy) * 1.5) {
            return;
        }
        if (window.visualViewport && window.visualViewport.scale > 1) {
            return;
        }
        window.location.href = dx < 0 ? page.dataset.next : page.dataset.prev;
    });
})();
