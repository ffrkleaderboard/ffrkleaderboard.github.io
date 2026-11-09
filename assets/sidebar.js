/* Home dropdown: Escape or a click outside it closes it. */

(function () {
    const sidebar = document.querySelector('.sidebar');

    sidebar.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            sidebar.open = false;
            sidebar.querySelector('summary').focus();
        }
    });

    document.addEventListener('click', function (event) {
        if (!sidebar.contains(event.target)) {
            sidebar.open = false;
        }
    });
})();
