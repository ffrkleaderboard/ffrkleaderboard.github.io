/*
 * Filters of a keeper's page. Each row carries what they match: data-type,
 * data-mode and data-podium. Choices of different filters add up.
 */

(function () {
    const filters = document.querySelector('.record-filters');
    if (!filters) {
        return;
    }
    const rows = Array.from(document.querySelectorAll('.keeper-records tbody tr'));
    const count = filters.querySelector('.filter-count');
    const empty = document.querySelector('.filter-empty');
    const chosen = { type: '', mode: '', podium: false };

    function matches(row) {
        return (chosen.type === '' || row.dataset.type === chosen.type)
            && (chosen.mode === '' || row.dataset.mode === chosen.mode)
            && (!chosen.podium || row.dataset.podium === '1');
    }

    function apply() {
        let shown = 0;
        rows.forEach(function (row) {
            row.hidden = !matches(row);
            if (!row.hidden) {
                shown++;
            }
        });
        count.textContent = shown === rows.length ? '' : 'Showing ' + shown + ' of ' + rows.length + ' records';
        empty.hidden = shown > 0;
    }

    filters.addEventListener('click', function (event) {
        const button = event.target.closest('button');
        if (!button) {
            return;
        }
        const filter = button.dataset.filter;
        if (filter === 'podium') {
            chosen.podium = !chosen.podium;
            button.setAttribute('aria-pressed', String(chosen.podium));
        } else {
            chosen[filter] = button.dataset.value;
            button.parentElement.querySelectorAll('button').forEach(function (other) {
                other.setAttribute('aria-pressed', String(other === button));
            });
        }
        apply();
    });

    filters.hidden = false;
})();
