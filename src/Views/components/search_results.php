<div id="searchResults" class="search-results" style="display:none; position:absolute; top:100%; left:0; right:0; background:white; border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,0.15); z-index:9999; max-height:400px; overflow-y:auto; margin-top:4px;"></div>

<style>
.search-results .result-item {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    text-decoration: none;
    color: #333;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.15s;
    cursor: pointer;
}
.search-results .result-item:hover { background: #f0f4ff; }
.search-results .result-item:last-child { border-bottom: none; }
.search-results .result-type {
    font-size: 10px;
    background: var(--primary);
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    margin-right: 10px;
    white-space: nowrap;
    text-transform: uppercase;
}
.search-results .result-label { font-weight: 500; font-size: 13px; }
.search-results .result-sub { font-size: 11px; color: #888; display: block; }
.search-results .result-empty {
    padding: 20px;
    text-align: center;
    color: #999;
    font-size: 13px;
}
</style>

<script <?= nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('globalSearch');
    var searchResults = document.getElementById('searchResults');

    if (!searchInput || !searchResults) return;

    var debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        var q = this.value.trim();

        if (q.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(function() {
            fetch('../api/search.php?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.length === 0) {
                        searchResults.innerHTML = '<div class="result-empty">Aucun résultat trouvé</div>';
                    } else {
                        var html = '';
                        data.forEach(function(item) {
                            html += '<a href="' + item.url + '" class="result-item">' +
                                '<span class="result-type">' + item.type + '</span>' +
                                '<div><div class="result-label">' + escapeHtml(item.label) + '</div>' +
                                (item.sub ? '<span class="result-sub">' + escapeHtml(item.sub) + '</span>' : '') +
                                '</div></a>';
                        });
                        searchResults.innerHTML = html;
                    }
                    searchResults.style.display = 'block';
                });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
});
</script>
