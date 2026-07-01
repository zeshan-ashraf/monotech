/**
 * Settlement dashboard live polling — cell-level updates with ticker flash.
 */
(function (window) {
    'use strict';

    var config = window.settlementDashboardPollConfig || null;
    var countdownTimer = null;
    var isFetching = false;
    var secondsRemaining = 0;
    var lastValues = {};
    var STORAGE_KEY = 'settlement_dashboard_poll_prefs';

    var INTERVALS = {
        '30s': 30000,
        '1m': 60000,
        '5m': 300000,
        '10m': 600000,
    };

    function parseIntervalMs(interval) {
        return INTERVALS[interval] || INTERVALS['30s'];
    }

    function intervalToSeconds(interval) {
        return Math.round(parseIntervalMs(interval) / 1000);
    }

    function loadPrefs() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                return { enabled: false, interval: '30s' };
            }
            var parsed = JSON.parse(raw);
            return {
                enabled: !!parsed.enabled,
                interval: INTERVALS[parsed.interval] ? parsed.interval : '30s',
            };
        } catch (e) {
            return { enabled: false, interval: '30s' };
        }
    }

    function savePrefs(enabled, interval) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ enabled: enabled, interval: interval }));
        } catch (e) {
            // ignore
        }
    }

    function formatNumber(value) {
        var num = Number(value);
        if (!isFinite(num)) {
            return '0';
        }
        return Math.round(num).toLocaleString('en-US');
    }

    function metricKey(scope, metric, userId) {
        return scope + ':' + (userId || '') + ':' + metric;
    }

    function findCell(scope, metric, userId) {
        var selector = '[data-poll-scope="' + scope + '"][data-poll-metric="' + metric + '"]';
        if (userId !== undefined && userId !== null && userId !== '') {
            selector += '[data-user-id="' + userId + '"]';
        }
        return document.querySelector(selector);
    }

    function flashCell(el, direction) {
        if (!el) {
            return;
        }
        el.classList.remove('poll-tick-up', 'poll-tick-down', 'poll-sync-flash');
        void el.offsetWidth;
        el.classList.add(direction === 'up' ? 'poll-tick-up' : 'poll-tick-down');
        window.setTimeout(function () {
            el.classList.remove('poll-tick-up', 'poll-tick-down');
        }, 900);
    }

    function flashSyncPulse(el) {
        if (!el) {
            return;
        }
        el.classList.remove('poll-sync-flash', 'poll-tick-up', 'poll-tick-down');
        void el.offsetWidth;
        el.classList.add('poll-sync-flash');
        window.setTimeout(function () {
            el.classList.remove('poll-sync-flash');
        }, 600);
    }

    function getDisplayText(el, value) {
        return formatNumber(value);
    }

    function setCellText(el, value) {
        el.textContent = getDisplayText(el, value);
    }

    function animateValue(el, fromValue, toValue) {
        var from = Number(fromValue);
        var to = Number(toValue);
        if (!isFinite(from) || !isFinite(to)) {
            setCellText(el, to);
            return false;
        }

        if (from === to) {
            return false;
        }

        var direction = to > from ? 'up' : 'down';
        flashCell(el, direction);

        var start = performance.now();
        var duration = 650;

        function frame(now) {
            var progress = Math.min((now - start) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = from + (to - from) * eased;
            setCellText(el, current);
            if (progress < 1) {
                requestAnimationFrame(frame);
            } else {
                setCellText(el, to);
            }
        }

        requestAnimationFrame(frame);
        return true;
    }

    function updateMetric(scope, metric, value, userId) {
        var el = findCell(scope, metric, userId);
        if (!el) {
            return false;
        }

        var key = metricKey(scope, metric, userId);
        var nextValue = Number(value);
        var prevValue = lastValues[key];

        if (prevValue === undefined) {
            lastValues[key] = nextValue;
            setCellText(el, nextValue);
            return false;
        }

        if (prevValue === nextValue) {
            return false;
        }

        lastValues[key] = nextValue;
        return animateValue(el, prevValue, nextValue);
    }

    function applyTopCards(cards) {
        var changed = 0;
        if (!cards) {
            return changed;
        }

        Object.keys(cards).forEach(function (metric) {
            if (updateMetric('card', metric, cards[metric])) {
                changed += 1;
            }
        });

        return changed;
    }

    function applySurplus(surplus) {
        var changed = 0;
        if (!surplus) {
            return changed;
        }

        if (updateMetric('surplus', 'jazzcash', surplus.jazzcash)) {
            changed += 1;
        }
        if (updateMetric('surplus', 'easypaisa', surplus.easypaisa)) {
            changed += 1;
        }

        return changed;
    }

    function applyRows(rows) {
        var changed = 0;
        if (!Array.isArray(rows)) {
            return changed;
        }

        rows.forEach(function (row) {
            var userId = row.user_id;
            Object.keys(row).forEach(function (metric) {
                if (metric === 'user_id') {
                    return;
                }
                if (updateMetric('row', metric, row[metric], userId)) {
                    changed += 1;
                }
            });
        });

        return changed;
    }

    function applyTotals(totals) {
        var changed = 0;
        if (!totals) {
            return changed;
        }

        Object.keys(totals).forEach(function (metric) {
            if (updateMetric('totals', metric, totals[metric])) {
                changed += 1;
            }
        });

        return changed;
    }

    function pulseAllMetricCells() {
        document.querySelectorAll('[data-poll-scope][data-poll-metric]').forEach(function (el) {
            flashSyncPulse(el);
        });
    }

    function setToolbarSyncing(isSyncing) {
        var toolbar = document.getElementById('settlement-poll-toolbar');
        if (toolbar) {
            toolbar.classList.toggle('is-syncing', !!isSyncing);
        }
    }

    function formatCountdownLabel(seconds) {
        var unit = seconds === 1 ? 'second' : 'seconds';
        return 'Next Refresh In: ' + seconds + ' ' + unit;
    }

    function updateCountdownDisplay() {
        var el = document.getElementById('settlement-poll-countdown');
        if (!el) {
            return;
        }

        var toggle = document.getElementById('settlement-auto-refresh');
        el.classList.remove('is-ticking');

        if (!toggle || !toggle.checked) {
            el.textContent = '';
            return;
        }

        if (document.hidden || isModalOpen()) {
            el.textContent = 'Next Refresh In: paused';
            return;
        }

        if (isFetching) {
            el.textContent = 'Refreshing…';
            return;
        }

        var seconds = Math.max(0, secondsRemaining);
        el.textContent = formatCountdownLabel(seconds);
        el.classList.add('is-ticking');
    }

    function resetCountdown() {
        secondsRemaining = intervalToSeconds(getSelectedInterval());
        updateCountdownDisplay();
    }

    function clearCountdownTimer() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    function tickCountdown() {
        var toggle = document.getElementById('settlement-auto-refresh');
        if (!toggle || !toggle.checked) {
            updateCountdownDisplay();
            return;
        }

        if (document.hidden || isModalOpen()) {
            updateCountdownDisplay();
            return;
        }

        if (isFetching) {
            updateCountdownDisplay();
            return;
        }

        secondsRemaining -= 1;
        updateCountdownDisplay();

        if (secondsRemaining <= 0) {
            refreshSettlementGrid();
        }
    }

    function startCountdown() {
        clearCountdownTimer();
        resetCountdown();
        countdownTimer = setInterval(tickCountdown, 1000);
    }

    function setSyncState(state, changeCount) {
        var dot = document.getElementById('settlement-poll-status-dot');
        var label = document.getElementById('settlement-poll-status-label');
        var summary = document.getElementById('settlement-poll-change-summary');
        if (!dot || !label) {
            return;
        }

        dot.classList.remove('is-live', 'is-syncing', 'is-off');
        setToolbarSyncing(state === 'syncing');

        if (state === 'syncing') {
            dot.classList.add('is-syncing');
            label.textContent = 'Syncing';
            if (summary) {
                summary.textContent = '';
            }
            updateCountdownDisplay();
            return;
        }

        if (state === 'live') {
            dot.classList.add('is-live');
            label.textContent = 'Live';
            if (summary) {
                if (typeof changeCount === 'number' && changeCount > 0) {
                    summary.textContent = '· ' + changeCount + ' updated';
                    summary.style.color = '#28c76f';
                    summary.style.fontWeight = '600';
                } else {
                    summary.textContent = '· up to date';
                    summary.style.color = '#6e6b7b';
                    summary.style.fontWeight = '400';
                }
            }
            updateCountdownDisplay();
            return;
        }

        dot.classList.add('is-off');
        label.textContent = 'Paused';
        if (summary) {
            summary.textContent = '';
        }
        updateCountdownDisplay();
    }

    function setUpdatedAt(isoString) {
        var el = document.getElementById('settlement-poll-updated-at');
        if (!el || !isoString) {
            return;
        }

        var date = new Date(isoString);
        if (isNaN(date.getTime())) {
            return;
        }

        el.textContent = '· Updated ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        el.classList.remove('poll-ts-flash');
        void el.offsetWidth;
        el.classList.add('poll-ts-flash');
    }

    function isModalOpen() {
        var modal = document.querySelector('.CustomTypeModal');
        if (!modal) {
            return false;
        }
        return modal.classList.contains('show');
    }

    function shouldPoll() {
        var toggle = document.getElementById('settlement-auto-refresh');
        if (!toggle || !toggle.checked) {
            return false;
        }
        if (document.hidden) {
            return false;
        }
        if (isModalOpen()) {
            return false;
        }
        return true;
    }

    function refreshSettlementGrid() {
        if (!config || !config.url || isFetching || !shouldPoll()) {
            return;
        }

        isFetching = true;
        setSyncState('syncing');

        fetch(config.url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (payload) {
                pulseAllMetricCells();

                var changeCount = 0;
                changeCount += applyTopCards(payload.top_cards);
                changeCount += applySurplus(payload.surplus);
                changeCount += applyRows(payload.rows);
                changeCount += applyTotals(payload.totals);

                setUpdatedAt(payload.generated_at);
                setSyncState('live', changeCount);
            })
            .catch(function (error) {
                console.warn('Settlement dashboard poll failed:', error);
                setSyncState('live', 0);
            })
            .finally(function () {
                isFetching = false;
                if (shouldPoll()) {
                    resetCountdown();
                } else {
                    updateCountdownDisplay();
                }
            });
    }

    function schedulePolling() {
        clearCountdownTimer();

        var toggle = document.getElementById('settlement-auto-refresh');
        if (!toggle || !toggle.checked) {
            setSyncState('off');
            setToolbarSyncing(false);
            secondsRemaining = 0;
            updateCountdownDisplay();
            return;
        }

        setSyncState('live', 0);
        startCountdown();
        refreshSettlementGrid();
    }

    function getSelectedInterval() {
        var active = document.querySelector('.settlement-poll-interval.active');
        return active ? active.getAttribute('data-interval') : '30s';
    }

    function seedLastValuesFromDom() {
        document.querySelectorAll('[data-poll-scope][data-poll-metric]').forEach(function (el) {
            var scope = el.getAttribute('data-poll-scope');
            var metric = el.getAttribute('data-poll-metric');
            var userId = el.getAttribute('data-user-id') || '';
            var raw = (el.textContent || '').replace(/[▲▼,\s]/g, '');
            var value = parseFloat(raw);
            if (!isNaN(value)) {
                lastValues[metricKey(scope, metric, userId)] = value;
            }
        });
    }

    function bindControls() {
        var prefs = loadPrefs();
        var toggle = document.getElementById('settlement-auto-refresh');
        var label = document.getElementById('settlement-poll-interval-label');

        document.querySelectorAll('.settlement-poll-interval').forEach(function (btn) {
            var interval = btn.getAttribute('data-interval');
            btn.classList.toggle('active', interval === prefs.interval);
        });

        if (label) {
            label.textContent = prefs.interval;
        }

        if (toggle) {
            toggle.checked = prefs.enabled;
            toggle.addEventListener('change', function () {
                savePrefs(toggle.checked, getSelectedInterval());
                schedulePolling();
            });
        }

        document.querySelectorAll('.settlement-poll-interval').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var interval = btn.getAttribute('data-interval');
                document.querySelectorAll('.settlement-poll-interval').forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');
                if (label) {
                    label.textContent = interval;
                }
                if (toggle) {
                    savePrefs(toggle.checked, interval);
                }
                schedulePolling();
            });
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                clearCountdownTimer();
                setSyncState('off');
                setToolbarSyncing(false);
                updateCountdownDisplay();
            } else {
                schedulePolling();
            }
        });

        var modal = document.querySelector('.CustomTypeModal');
        if (modal) {
            modal.addEventListener('shown.bs.modal', function () {
                clearCountdownTimer();
                updateCountdownDisplay();
            });
            modal.addEventListener('hidden.bs.modal', schedulePolling);
        }

        seedLastValuesFromDom();
        schedulePolling();
    }

    function init() {
        if (!config || !config.enabled) {
            return;
        }
        bindControls();
    }

    window.SettlementDashboardPoll = {
        init: init,
        refresh: refreshSettlementGrid,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window);
