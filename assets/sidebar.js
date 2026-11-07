/* Keeper search of the home dropdown. The names come from #keeper-index in the page. */

(function () {
    const MAX_RESULTS = 8;

    const sidebar = document.querySelector('.sidebar');
    const input = document.getElementById('keeper-search');
    const results = document.getElementById('keeper-results');
    const keepers = JSON.parse(document.getElementById('keeper-index').textContent);

    /** Names starting with the query go first, then names containing it. */
    function findKeepers(query) {
        const starting = [];
        const containing = [];
        keepers.forEach(function (keeper) {
            const position = keeper.name.toLowerCase().indexOf(query);
            if (position === 0) {
                starting.push(keeper);
            } else if (position > 0) {
                containing.push(keeper);
            }
        });
        return starting.concat(containing).slice(0, MAX_RESULTS);
    }

    function resultItem(keeper) {
        const item = document.createElement('li');
        const link = document.createElement('a');
        link.href = keeper.url;
        link.textContent = keeper.name;
        item.append(link);
        return item;
    }

    function noResultsItem() {
        const item = document.createElement('li');
        item.className = 'keeper-none';
        item.textContent = 'No keepers found';
        return item;
    }

    function showResults() {
        const query = input.value.trim().toLowerCase();
        const found = query === '' ? [] : findKeepers(query);
        results.hidden = query === '';
        results.replaceChildren(...(found.length > 0 ? found.map(resultItem) : [noResultsItem()]));
    }

    /** Up and down walk from the search box through the results and back. */
    function moveFocus(step) {
        const stops = [input, ...results.querySelectorAll('a')];
        const next = stops[stops.indexOf(document.activeElement) + step];
        if (next) {
            next.focus();
        }
    }

    input.addEventListener('input', showResults);

    input.addEventListener('keydown', function (event) {
        const first = results.querySelector('a');
        if (event.key === 'Enter' && first) {
            first.click();
        }
    });

    sidebar.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            sidebar.open = false;
            sidebar.querySelector('summary').focus();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            if (event.target === input || results.contains(event.target)) {
                event.preventDefault();
                moveFocus(event.key === 'ArrowDown' ? 1 : -1);
            }
        }
    });

    document.addEventListener('click', function (event) {
        if (!sidebar.contains(event.target)) {
            sidebar.open = false;
        }
    });

    // A reload can bring back what was typed.
    showResults();
})();
