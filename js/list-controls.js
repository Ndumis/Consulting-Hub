/* Shared list controls: date-range filtering + client-side pagination */
const ListControls = (function () {
    const _pageState = {};

    function toDateOnly(d) {
        return new Date(d.getFullYear(), d.getMonth(), d.getDate());
    }

    function parseDate(str) {
        if (!str) return null;
        const d = new Date(str + 'T00:00:00');
        return isNaN(d.getTime()) ? null : toDateOnly(d);
    }

    function matchesDateRange(dateStr, range, from, to) {
        if (!range || range === 'all') return true;

        const day = parseDate(dateStr);
        if (!day) return false;

        const now = toDateOnly(new Date());

        switch (range) {
            case 'today':
                return day.getTime() === now.getTime();

            case 'week': {
                const dow = now.getDay();
                const diffToMon = (dow === 0) ? 6 : dow - 1;
                const monday = new Date(now);
                monday.setDate(now.getDate() - diffToMon);
                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);
                return day >= monday && day <= sunday;
            }

            case 'month':
                return day.getFullYear() === now.getFullYear() && day.getMonth() === now.getMonth();

            case 'year':
                return day.getFullYear() === now.getFullYear();

            case 'lastyear':
                return day.getFullYear() === (now.getFullYear() - 1);

            case 'custom': {
                const fromD = parseDate(from);
                const toD = parseDate(to);
                if (fromD && day < fromD) return false;
                if (toD && day > toD) return false;
                return true;
            }

            default:
                return true;
        }
    }

    function getDateFilterValue(selectId, fromId, toId) {
        const sel = document.getElementById(selectId);
        const fromEl = document.getElementById(fromId);
        const toEl = document.getElementById(toId);
        return {
            range: sel ? sel.value : 'all',
            from: fromEl ? fromEl.value : '',
            to: toEl ? toEl.value : ''
        };
    }

    function initDateRangeControl(selectId, fromId, toId, onChange) {
        const sel = document.getElementById(selectId);
        if (!sel) return;

        const fromEl = document.getElementById(fromId);
        const toEl = document.getElementById(toId);
        const wrap = sel.closest('.lc-date-filter');
        const customRange = wrap ? wrap.querySelector('.lc-custom-range') : null;

        function syncCustomRange() {
            if (!customRange) return;
            customRange.classList.toggle('show', sel.value === 'custom');
        }

        syncCustomRange();

        sel.addEventListener('change', function () {
            syncCustomRange();
            if (typeof onChange === 'function') onChange();
        });
        if (fromEl) fromEl.addEventListener('change', function () {
            if (typeof onChange === 'function') onChange();
        });
        if (toEl) toEl.addEventListener('change', function () {
            if (typeof onChange === 'function') onChange();
        });
    }

    function paginate(containerId, itemSelector, paginationContainerId, pageSize) {
        pageSize = pageSize || 10;

        const container = document.getElementById(containerId);
        const pagEl = document.getElementById(paginationContainerId);
        if (!container) return;

        const items = Array.prototype.slice.call(container.querySelectorAll(itemSelector));
        const visibleItems = items.filter(function (item) {
            return item.style.display !== 'none';
        });

        const total = visibleItems.length;
        const totalPages = Math.max(1, Math.ceil(total / pageSize));

        let page = _pageState[containerId] || 1;
        if (page > totalPages) page = totalPages;
        if (page < 1) page = 1;
        _pageState[containerId] = page;

        const startIdx = (page - 1) * pageSize;
        const endIdx = startIdx + pageSize;

        visibleItems.forEach(function (item, idx) {
            item.style.display = (idx >= startIdx && idx < endIdx) ? '' : 'none';
        });

        if (!pagEl) return;

        if (totalPages <= 1) {
            pagEl.innerHTML = '';
            return;
        }

        let html = '';
        html += '<button type="button" ' + (page === 1 ? 'disabled' : '') +
            ' data-page="' + (page - 1) + '">&laquo; Prev</button>';

        const maxButtons = 7;
        let startPage = Math.max(1, page - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);
        startPage = Math.max(1, endPage - maxButtons + 1);

        if (startPage > 1) {
            html += '<button type="button" data-page="1">1</button>';
            if (startPage > 2) html += '<span class="lc-ellipsis">&hellip;</span>';
        }

        for (let p = startPage; p <= endPage; p++) {
            html += '<button type="button" class="' + (p === page ? 'active' : '') +
                '" data-page="' + p + '">' + p + '</button>';
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) html += '<span class="lc-ellipsis">&hellip;</span>';
            html += '<button type="button" data-page="' + totalPages + '">' + totalPages + '</button>';
        }

        html += '<button type="button" ' + (page === totalPages ? 'disabled' : '') +
            ' data-page="' + (page + 1) + '">Next &raquo;</button>';

        pagEl.innerHTML = html;

        pagEl.querySelectorAll('button[data-page]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const p = parseInt(btn.getAttribute('data-page'), 10);
                if (isNaN(p) || p < 1 || p > totalPages || p === _pageState[containerId]) return;
                _pageState[containerId] = p;
                paginate(containerId, itemSelector, paginationContainerId, pageSize);
            });
        });
    }

    function applyDateFilterAndPaginate(containerId, itemSelector, dateSelectId, dateFromId, dateToId, paginationContainerId, pageSize) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const filterVal = getDateFilterValue(dateSelectId, dateFromId, dateToId);
        const items = container.querySelectorAll(itemSelector);

        items.forEach(function (item) {
            if (item.style.display === 'none') return;
            const dateStr = item.getAttribute('data-date') || '';
            if (!matchesDateRange(dateStr, filterVal.range, filterVal.from, filterVal.to)) {
                item.style.display = 'none';
            }
        });

        _pageState[containerId] = 1;
        paginate(containerId, itemSelector, paginationContainerId, pageSize);
    }

    return {
        matchesDateRange: matchesDateRange,
        getDateFilterValue: getDateFilterValue,
        initDateRangeControl: initDateRangeControl,
        paginate: paginate,
        applyDateFilterAndPaginate: applyDateFilterAndPaginate
    };
})();
