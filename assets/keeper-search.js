/*
 * Keeper search: lists the keepers whose name contains what is typed. The names
 * load from the file in the box's data-index the first time it is used.
 */

(function () {
    const MAX_RESULTS = 8;

    let index = null;

    function loadIndex(url) {
        if (index === null) {
            index = fetch(url).then(function (response) {
                if (!response.ok) {
                    throw new Error(response.status);
                }
                return response.json();
            });
            index.catch(function () {
                index = null; // Try again on the next keystroke.
            });
        }
        return index;
    }

    /** Names starting with the query go first, then names containing it. */
    function findKeepers(keepers, query) {
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

    function messageItem(text) {
        const item = document.createElement('li');
        item.className = 'keeper-none';
        item.textContent = text;
        return item;
    }

    function setUp(finder) {
        const input = finder.querySelector('.keeper-search');
        const results = finder.querySelector('.keeper-results');

        function showResults() {
            const query = input.value.trim().toLowerCase();
            if (query === '') {
                results.hidden = true;
                return;
            }
            loadIndex(finder.dataset.index).then(function (keepers) {
                if (input.value.trim().toLowerCase() !== query) {
                    return; // Something else was typed meanwhile.
                }
                const found = findKeepers(keepers, query);
                results.replaceChildren(...(found.length > 0 ? found.map(resultItem) : [messageItem('No keepers found')]));
                results.hidden = false;
            }, function () {
                results.replaceChildren(messageItem('Search unavailable'));
                results.hidden = false;
            });
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
        input.addEventListener('focus', function () {
            loadIndex(finder.dataset.index);
            showResults();
        });

        input.addEventListener('keydown', function (event) {
            const first = results.querySelector('a');
            if (event.key === 'Enter' && first) {
                first.click();
            }
        });

        finder.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                moveFocus(event.key === 'ArrowDown' ? 1 : -1);
            } else if (event.key === 'Escape' && !results.hidden) {
                // A first Escape closes the results, a second one whatever holds the box.
                event.stopPropagation();
                results.hidden = true;
                input.focus();
            }
        });

        // Safari doesn't focus a clicked link: keep the focus in the box so that the
        // results stay open until the click lands.
        results.addEventListener('mousedown', function (event) {
            event.preventDefault();
        });

        finder.addEventListener('focusout', function (event) {
            if (!finder.contains(event.relatedTarget)) {
                results.hidden = true;
            }
        });

        // A reload can bring back what was typed.
        if (input.value !== '') {
            showResults();
        }
    }

    document.querySelectorAll('.keeper-finder').forEach(setUp);
})();
