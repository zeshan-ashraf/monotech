/**
 * OPS Dashboard charts — ApexCharts with static data (Phase 1).
 * Requires: ApexCharts (loaded globally via admin layout).
 */
(function (window) {
    'use strict';

    var charts = {};

    function isDarkLayout() {
        return document.documentElement.classList.contains('dark-layout')
            || document.body.classList.contains('dark-layout');
    }

    function themeColors() {
        var dark = isDarkLayout();
        return {
            text: dark ? '#b4b7bd' : '#6e6b7b',
            grid: dark ? '#3b4253' : '#ebe9f1',
            primary: getComputedStyle(document.documentElement).getPropertyValue('--bs-primary').trim() || '#7367f0',
            success: getComputedStyle(document.documentElement).getPropertyValue('--bs-success').trim() || '#28c76f',
            warning: getComputedStyle(document.documentElement).getPropertyValue('--bs-warning').trim() || '#ff9f43',
            danger: getComputedStyle(document.documentElement).getPropertyValue('--bs-danger').trim() || '#ea5455',
            info: getComputedStyle(document.documentElement).getPropertyValue('--bs-info').trim() || '#00cfe8',
            secondary: getComputedStyle(document.documentElement).getPropertyValue('--bs-secondary').trim() || '#82868b',
        };
    }

    function destroyChart(id) {
        if (charts[id]) {
            charts[id].destroy();
            delete charts[id];
        }
    }

    function renderChart(id, options) {
        var el = document.getElementById(id);
        if (!el || typeof ApexCharts === 'undefined') {
            return;
        }

        destroyChart(id);
        charts[id] = new ApexCharts(el, options);
        charts[id].render();
    }

    function sparklineOptions(series, color) {
        var colors = themeColors();
        var safeSeries = (series && series.length) ? series : [1, 2, 3, 4, 5, 4, 3, 4, 5, 4, 3, 2];
        var chartColor = color || colors.primary || '#7367f0';

        return {
            series: [{ name: 'value', data: safeSeries }],
            chart: {
                type: 'area',
                height: 48,
                width: '100%',
                sparkline: { enabled: true },
                animations: { enabled: true, speed: 400 },
            },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 0.5,
                    opacityFrom: 0.45,
                    opacityTo: 0.08,
                },
            },
            colors: [chartColor],
            tooltip: { enabled: false },
            yaxis: {
                min: 0,
                show: false,
            },
        };
    }

    function overviewSparklines(data) {
        var colors = themeColors();
        var colorMap = {
            cpu: colors.primary || '#7367f0',
            ram: colors.success || '#28c76f',
            disk: colors.info || '#00cfe8',
            load: colors.warning || '#ff9f43',
            network: colors.secondary || '#82868b',
        };

        Object.keys(colorMap).forEach(function (key) {
            var chartId = 'ops-spark-' + key;
            var el = document.getElementById(chartId);

            if (!el || !data[key]) {
                return;
            }

            try {
                renderChart(chartId, sparklineOptions(data[key], colorMap[key]));
            } catch (error) {
                console.warn('OPS dashboard sparkline failed:', chartId, error);
            }
        });
    }

    function phpFpmDonut(workers) {
        var colors = themeColors();
        renderChart('ops-phpfpm-donut', {
            series: [workers.busy, workers.idle, workers.queue, workers.max_children],
            chart: {
                type: 'donut',
                height: 240,
            },
            labels: ['Busy', 'Idle', 'Queue', 'Max Children'],
            colors: [colors.primary, colors.success, colors.warning, colors.danger],
            legend: { show: false },
            dataLabels: { enabled: false },
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        labels: {
                            show: true,
                            name: { show: false },
                            value: {
                                fontSize: '22px',
                                fontWeight: 700,
                                color: colors.text,
                                formatter: function () {
                                    return workers.busy + workers.idle;
                                },
                            },
                            total: {
                                show: true,
                                label: 'Workers',
                                fontSize: '12px',
                                color: colors.text,
                                formatter: function () {
                                    return workers.busy + workers.idle;
                                },
                            },
                        },
                    },
                },
            },
            stroke: { width: 2 },
        });
    }

    function phpFpmMiniCharts(data) {
        var colors = themeColors();
        renderChart('ops-phpfpm-requests', sparklineOptions(data.requests, colors.primary));
        renderChart('ops-phpfpm-response', sparklineOptions(data.response, colors.warning));
        renderChart('ops-phpfpm-slow', sparklineOptions(data.slow, colors.danger));
    }

    function paymentSparklines(data, gatewayKey) {
        var colors = themeColors();
        var colorMap = {
            success: colors.success,
            pending: colors.warning,
            failed: colors.danger,
            rejected: colors.info,
        };

        Object.keys(colorMap).forEach(function (key) {
            if (!data || !data[key]) {
                return;
            }

            var chartId = gatewayKey
                ? 'ops-payment-' + gatewayKey + '-' + key
                : 'ops-payment-' + key;

            renderChart(chartId, sparklineOptions(data[key], colorMap[key]));
        });
    }

    function initPaymentSparklines(paymentsData) {
        if (!paymentsData) {
            return;
        }

        Object.keys(paymentsData).forEach(function (gatewayKey) {
            paymentSparklines(paymentsData[gatewayKey], gatewayKey);
        });
    }

    function historyChartOptions(labels, series, colors, ySuffix) {
        var theme = themeColors();
        return {
            series: series,
            chart: {
                type: 'area',
                height: 220,
                toolbar: { show: false },
                zoom: { enabled: false },
                fontFamily: 'Montserrat, sans-serif',
            },
            colors: colors,
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 0.4,
                    opacityFrom: 0.5,
                    opacityTo: 0.05,
                },
            },
            grid: {
                borderColor: theme.grid,
                strokeDashArray: 4,
                padding: { left: 8, right: 8 },
            },
            xaxis: {
                categories: labels,
                labels: {
                    style: { colors: theme.text, fontSize: '10px' },
                    rotate: -45,
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                labels: {
                    style: { colors: theme.text, fontSize: '11px' },
                    formatter: function (val) {
                        return ySuffix ? val + ySuffix : val;
                    },
                },
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                labels: { colors: theme.text },
                fontSize: '12px',
            },
            tooltip: {
                theme: isDarkLayout() ? 'dark' : 'light',
                x: { show: true },
            },
        };
    }

    function historyCharts(history) {
        var colors = themeColors();
        var labels = history.labels || [];

        renderChart('ops-chart-cpu', historyChartOptions(
            labels,
            [{ name: 'CPU', data: history.cpu }],
            [colors.primary],
            '%'
        ));

        renderChart('ops-chart-ram', historyChartOptions(
            labels,
            [{ name: 'RAM', data: history.ram }],
            [colors.success],
            ' GB'
        ));

        renderChart('ops-chart-network', historyChartOptions(
            labels,
            [
                { name: 'In', data: history.network_in },
                { name: 'Out', data: history.network_out },
            ],
            [colors.info, colors.success]
        ));

        renderChart('ops-chart-mysql', historyChartOptions(
            labels,
            [{ name: 'Queries/sec', data: history.mysql_qps }],
            [colors.primary]
        ));
    }

  /**
   * Refresh interval dropdown UI and live payment metrics polling.
   */
    function parseIntervalMs(intervalLabel) {
        if (!intervalLabel || typeof intervalLabel !== 'string') {
            return 10000;
        }

        var value = parseInt(intervalLabel, 10);
        if (Number.isNaN(value)) {
            return 10000;
        }

        if (intervalLabel.indexOf('m') !== -1) {
            return value * 60000;
        }

        return value * 1000;
    }

    function getSelectedRefreshInterval() {
        var active = document.querySelector('.ops-refresh-option.active');

        return active ? active.getAttribute('data-interval') : '10s';
    }

    function updatePaymentStatValue(gatewayKey, metricKey, value) {
        var chartId = 'ops-payment-' + gatewayKey + '-' + metricKey;
        var chartEl = document.getElementById(chartId);

        if (!chartEl) {
            return;
        }

        var stat = chartEl.closest('.ops-payment-stat');

        if (!stat) {
            return;
        }

        var valueEl = stat.querySelector('.ops-payment-stat__value');

        if (valueEl) {
            valueEl.textContent = Number(value || 0).toLocaleString();
        }
    }

    function updateGatewayResponseStats(gatewayKey, stats) {
        if (!stats) {
            return;
        }

        var avgEl = document.querySelector('.ops-gateway-avg[data-gateway="' + gatewayKey + '"]');
        var maxEl = document.querySelector('.ops-gateway-max[data-gateway="' + gatewayKey + '"]');

        if (avgEl) {
            avgEl.textContent = stats.avg || '0.00 sec';
        }

        if (maxEl) {
            maxEl.textContent = stats.max || '0.00 sec';
        }
    }

    function refreshPaymentMetrics() {
        var url = window.opsDashboardPaymentMetricsUrl;

        if (!url) {
            return;
        }

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Payment metrics request failed');
                }

                return response.json();
            })
            .then(function (payload) {
                if (!payload || !payload.gateways) {
                    return;
                }

                payload.gateways.forEach(function (gateway) {
                    if (!gateway.cards) {
                        return;
                    }

                    gateway.cards.forEach(function (card) {
                        updatePaymentStatValue(gateway.key, card.key, card.value);
                    });

                    if (gateway.sparklines) {
                        paymentSparklines(gateway.sparklines, gateway.key);
                    }

                    updateGatewayResponseStats(gateway.key, gateway.payment_stats);
                });
            })
            .catch(function (error) {
                console.warn('OPS dashboard payment metrics refresh failed:', error);
            });
    }

    var paymentMetricsTimer = null;

    function schedulePaymentMetricsRefresh() {
        if (paymentMetricsTimer) {
            clearInterval(paymentMetricsTimer);
            paymentMetricsTimer = null;
        }

        var autoRefresh = document.getElementById('ops-auto-refresh');

        if (autoRefresh && !autoRefresh.checked) {
            return;
        }

        var intervalMs = parseIntervalMs(getSelectedRefreshInterval());
        paymentMetricsTimer = setInterval(refreshPaymentMetrics, intervalMs);
    }

    function bindNavbarControls() {
        document.querySelectorAll('.ops-refresh-option').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.ops-refresh-option').forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');
                var label = document.getElementById('ops-refresh-label');
                if (label) {
                    label.textContent = btn.getAttribute('data-interval');
                }
                schedulePaymentMetricsRefresh();
                updateTrafficLiveIndicator();
            });
        });

        var autoRefresh = document.getElementById('ops-auto-refresh');

        if (autoRefresh) {
            autoRefresh.addEventListener('change', function () {
                schedulePaymentMetricsRefresh();
                scheduleTrafficMetricsRefresh();
                scheduleRuntimeMetricsRefresh();
                updateTrafficLiveIndicator();
            });
        }
    }

  /**
   * API Traffic panel — live Redis metrics.
   */
    var trafficMetricsTimer = null;
    var trafficWindowMinutes = 5;
    var TRAFFIC_REFRESH_MS = 5000;

    function getTrafficMetricsUrl() {
        var baseUrl = window.opsDashboardTrafficMetricsUrl;

        if (!baseUrl) {
            return null;
        }

        var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';

        return baseUrl + separator + 'minutes=' + encodeURIComponent(trafficWindowMinutes);
    }

    function updateTrafficLiveIndicator() {
        var liveEl = document.getElementById('ops-traffic-live');
        var autoRefresh = document.getElementById('ops-auto-refresh');

        if (!liveEl) {
            return;
        }

        if (autoRefresh && !autoRefresh.checked) {
            liveEl.classList.add('is-paused');
        } else {
            liveEl.classList.remove('is-paused');
        }
    }

    function updateTrafficStatValue(metricKey, value) {
        var stat = document.querySelector('.ops-traffic-stat[data-metric="' + metricKey + '"]');

        if (!stat) {
            return;
        }

        var valueEl = stat.querySelector('[data-field="value"]');

        if (!valueEl) {
            return;
        }

        if (metricKey === 'incoming' || metricKey === 'rejected') {
            valueEl.textContent = Number(value || 0).toLocaleString();
        } else {
            valueEl.textContent = value;
        }
    }

    function updateTrafficApiRows(rows) {
        var listEl = document.getElementById('ops-traffic-api-list');

        if (!listEl || !rows || !rows.length) {
            return;
        }

        var maxIncoming = 1;

        rows.forEach(function (row) {
            maxIncoming = Math.max(maxIncoming, Number(row.incoming || 0));
        });

        rows.forEach(function (row) {
            var rowEl = listEl.querySelector('.ops-traffic-api-row[data-api="' + row.key + '"]');

            if (!rowEl) {
                return;
            }

            var incomingEl = rowEl.querySelector('[data-field="incoming"]');
            var barEl = rowEl.querySelector('[data-field="bar"]');
            var incoming = Number(row.incoming || 0);
            var percent = Math.round((incoming / maxIncoming) * 1000) / 10;

            if (incomingEl) {
                incomingEl.textContent = incoming.toLocaleString();
            }

            if (barEl) {
                barEl.style.width = percent + '%';
            }
        });
    }

    function updateTrafficErrorBadges(errors) {
        if (!errors || !errors.length) {
            return;
        }

        errors.forEach(function (error) {
            var badge = document.querySelector('.ops-traffic-error[data-error="' + error.key + '"]');

            if (!badge) {
                return;
            }

            var valueEl = badge.querySelector('[data-field="value"]');

            if (valueEl) {
                valueEl.textContent = Number(error.value || 0).toLocaleString();
            }
        });
    }

    function trafficIncomingChartOptions(labels, series) {
        var colors = themeColors();

        return {
            series: [{ name: 'Incoming', data: series || [] }],
            chart: {
                type: 'area',
                height: 220,
                toolbar: { show: false },
                zoom: { enabled: false },
                fontFamily: 'Montserrat, sans-serif',
            },
            colors: [colors.primary],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 0.4,
                    opacityFrom: 0.5,
                    opacityTo: 0.05,
                },
            },
            grid: {
                borderColor: colors.grid,
                strokeDashArray: 4,
                padding: { left: 8, right: 8 },
            },
            xaxis: {
                categories: labels || [],
                labels: {
                    style: { colors: colors.text, fontSize: '10px' },
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                min: 0,
                labels: {
                    style: { colors: colors.text, fontSize: '11px' },
                },
            },
            tooltip: {
                theme: isDarkLayout() ? 'dark' : 'light',
            },
        };
    }

    function renderTrafficCharts(payload) {
        if (!payload) {
            return;
        }

        if (payload.chart) {
            renderChart(
                'ops-traffic-incoming-chart',
                trafficIncomingChartOptions(payload.chart.labels, payload.chart.series)
            );
        }

        var incomingCard = (payload.cards || []).find(function (card) {
            return card.key === 'incoming';
        });

        if (incomingCard && payload.chart && payload.chart.series) {
            renderChart(
                'ops-traffic-spark-incoming',
                sparklineOptions(payload.chart.series, themeColors().primary)
            );
        }

        var windowEl = document.getElementById('ops-traffic-chart-window');

        if (windowEl && payload.window_minutes) {
            windowEl.textContent = payload.window_minutes;
        }
    }

    function applyTrafficPayload(payload) {
        if (!payload) {
            return;
        }

        (payload.cards || []).forEach(function (card) {
            updateTrafficStatValue(card.key, card.value);
        });

        updateTrafficApiRows(payload.api_rows || []);
        updateTrafficErrorBadges(payload.errors || []);
        renderTrafficCharts(payload);
    }

    function refreshTrafficMetrics() {
        var url = getTrafficMetricsUrl();

        if (!url || !document.getElementById('ops-traffic-panel')) {
            return;
        }

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Traffic metrics request failed');
                }

                return response.json();
            })
            .then(function (payload) {
                applyTrafficPayload(payload);
            })
            .catch(function (error) {
                console.warn('OPS dashboard traffic metrics refresh failed:', error);
            });
    }

    function scheduleTrafficMetricsRefresh() {
        if (trafficMetricsTimer) {
            clearInterval(trafficMetricsTimer);
            trafficMetricsTimer = null;
        }

        var autoRefresh = document.getElementById('ops-auto-refresh');

        if (autoRefresh && !autoRefresh.checked) {
            updateTrafficLiveIndicator();

            return;
        }

        trafficMetricsTimer = setInterval(refreshTrafficMetrics, TRAFFIC_REFRESH_MS);
        updateTrafficLiveIndicator();
    }

    function bindTrafficWindowControls() {
        document.querySelectorAll('.ops-traffic-window').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var minutes = parseInt(btn.getAttribute('data-minutes'), 10);

                if (Number.isNaN(minutes)) {
                    return;
                }

                trafficWindowMinutes = minutes;

                document.querySelectorAll('.ops-traffic-window').forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');

                refreshTrafficMetrics();
            });
        });
    }

    function initTrafficPanel() {
        if (!document.getElementById('ops-traffic-panel')) {
            return;
        }

        var initial = window.opsDashboardTraffic;

        if (initial && initial.window_minutes) {
            trafficWindowMinutes = initial.window_minutes;
        }

        applyTrafficPayload(initial);
        bindTrafficWindowControls();
        scheduleTrafficMetricsRefresh();
    }

  /**
   * Application Runtime panel — live metrics polling.
   */
    var runtimeMetricsTimer = null;
    var RUNTIME_REFRESH_MS = 5000;

    function updateRuntimeLiveIndicator() {
        var liveEl = document.getElementById('ops-runtime-live');
        var autoRefresh = document.getElementById('ops-auto-refresh');

        if (!liveEl) {
            return;
        }

        if (autoRefresh && !autoRefresh.checked) {
            liveEl.classList.add('is-paused');
        } else {
            liveEl.classList.remove('is-paused');
        }
    }

    function updateRuntimeSummaryCards(summary) {
        if (!summary || !summary.length) {
            return;
        }

        summary.forEach(function (card) {
            var el = document.querySelector('[data-runtime-summary="' + card.key + '"]');

            if (!el) {
                return;
            }

            var valueEl = el.querySelector('[data-field="value"]');
            var subtitleEl = el.querySelector('[data-field="subtitle"]');
            var statusEl = el.querySelector('[data-field="status"]');

            if (valueEl) {
                valueEl.textContent = card.value;
            }

            if (subtitleEl) {
                subtitleEl.textContent = card.subtitle;
            }

            if (statusEl) {
                statusEl.textContent = card.status_label;
                statusEl.className = 'ops-health-badge ops-health-badge--' + card.status_color;
            }

            el.className = 'ops-card ops-metric-card ops-metric-card--' + card.color;
        });
    }

    function updateRuntimeSection(sectionKey, data, fields) {
        var section = document.querySelector('[data-runtime-section="' + sectionKey + '"]');

        if (!section || !data) {
            return;
        }

        fields.forEach(function (field) {
            var el = section.querySelector('[data-field="' + field + '"]');

            if (el) {
                el.textContent = data[field] !== undefined && data[field] !== null ? data[field] : '—';
            }
        });

        if (sectionKey === 'php_fpm') {
            var bar = section.querySelector('[data-field="utilization_bar"]');
            var utilization = Number(data.worker_utilization || 0);

            if (bar) {
                bar.style.width = Math.min(100, utilization) + '%';
                bar.className = 'progress-bar bg-' + (data.status_color || 'primary');
            }
        }
    }

    function updateRuntimeStuckTable(stuck) {
        var tbody = document.getElementById('ops-runtime-stuck-table');
        var totalEl = document.querySelector('[data-field="stuck_total"]');

        if (totalEl) {
            totalEl.textContent = stuck && stuck.total !== undefined ? stuck.total : 0;
        }

        if (!tbody) {
            return;
        }

        var processes = (stuck && stuck.processes) ? stuck.processes : [];

        if (!processes.length) {
            tbody.innerHTML = '<tr data-empty-row="1"><td colspan="7" class="text-center text-muted py-4">No stuck processes detected</td></tr>';

            return;
        }

        tbody.innerHTML = processes.map(function (process) {
            return '<tr>'
                + '<td>' + (process.type_label || '') + '</td>'
                + '<td>' + (process.name || '') + '</td>'
                + '<td>' + (process.pid || '—') + '</td>'
                + '<td>' + (process.started || '') + '</td>'
                + '<td>' + (process.running_for || '') + '</td>'
                + '<td><span class="ops-health-badge ops-health-badge--' + (process.status_color || 'secondary') + '"><span class="ops-health-badge__dot"></span>' + (process.status_label || '') + '</span></td>'
                + '<td>' + (process.recommendation || '') + '</td>'
                + '</tr>';
        }).join('');
    }

    function updateRuntimeRecommendations(recommendations) {
        var container = document.getElementById('ops-runtime-recommendations');

        if (!container || !recommendations || !recommendations.length) {
            return;
        }

        container.innerHTML = recommendations.map(function (item) {
            var severityLabel = item.severity ? item.severity.charAt(0).toUpperCase() + item.severity.slice(1) : 'Info';

            return '<div class="col-xl-4 col-md-6">'
                + '<div class="ops-card h-100 p-3 border">'
                + '<div class="d-flex align-items-center gap-2 mb-2">'
                + '<span class="ops-health-badge ops-health-badge--' + (item.severity_color || 'secondary') + '"><span class="ops-health-badge__dot"></span>' + severityLabel + '</span>'
                + '<strong>' + (item.title || '') + '</strong>'
                + '</div>'
                + '<p class="text-muted small mb-2">' + (item.description || '') + '</p>'
                + '<p class="mb-0 small"><strong>Action:</strong> ' + (item.action || '') + '</p>'
                + '</div>'
                + '</div>';
        }).join('');
    }

    function applyRuntimePayload(payload) {
        if (!payload) {
            return;
        }

        updateRuntimeSummaryCards(payload.summary || []);
        updateRuntimeSection('php_fpm', payload.php_fpm, [
            'total_workers',
            'busy_workers',
            'idle_workers',
            'listen_queue',
            'max_children_reached',
            'slow_requests',
            'avg_response_ms',
            'requests_per_second',
            'worker_utilization',
        ]);
        updateRuntimeSection('scheduler', payload.scheduler, [
            'status_label',
            'last_tick',
            'next_tick',
            'scheduled_commands',
            'running_commands',
            'failed_today',
            'avg_runtime',
            'longest_runtime',
        ]);
        updateRuntimeSection('queue', payload.queue, [
            'pending_jobs',
            'processing_jobs',
            'failed_jobs',
            'retrying_jobs',
            'avg_runtime',
            'longest_running_for',
            'worker_count',
            'status_label',
        ]);
        updateRuntimeStuckTable(payload.stuck_processes || {});
        updateRuntimeRecommendations(payload.recommendations || []);
    }

    function refreshRuntimeMetrics() {
        var url = window.opsDashboardRuntimeMetricsUrl;

        if (!url || !document.getElementById('ops-runtime-panel')) {
            return;
        }

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Runtime metrics request failed');
                }

                return response.json();
            })
            .then(function (payload) {
                applyRuntimePayload(payload);
            })
            .catch(function (error) {
                console.warn('OPS dashboard runtime metrics refresh failed:', error);
            });
    }

    function scheduleRuntimeMetricsRefresh() {
        if (runtimeMetricsTimer) {
            clearInterval(runtimeMetricsTimer);
            runtimeMetricsTimer = null;
        }

        var autoRefresh = document.getElementById('ops-auto-refresh');

        if (autoRefresh && !autoRefresh.checked) {
            updateRuntimeLiveIndicator();

            return;
        }

        runtimeMetricsTimer = setInterval(refreshRuntimeMetrics, RUNTIME_REFRESH_MS);
        updateRuntimeLiveIndicator();
    }

    function initRuntimePanel() {
        if (!document.getElementById('ops-runtime-panel')) {
            return;
        }

        applyRuntimePayload(window.opsDashboardRuntime || null);
        scheduleRuntimeMetricsRefresh();
    }

    function init() {
        var data = window.opsDashboardData || {};

        if (data.overview) {
            overviewSparklines(data.overview);
        }

        if (data.phpfpm) {
            phpFpmDonut(data.phpfpm.workers);
            phpFpmMiniCharts(data.phpfpm);
        }

        if (data.payments) {
            initPaymentSparklines(data.payments);
        }

        if (data.history) {
            historyCharts(data.history);
        }

        bindNavbarControls();
        schedulePaymentMetricsRefresh();
        initTrafficPanel();
        initRuntimePanel();
    }

    document.addEventListener('DOMContentLoaded', init);

    window.OpsDashboard = {
        init: init,
        refreshPaymentMetrics: refreshPaymentMetrics,
        refreshTrafficMetrics: refreshTrafficMetrics,
        refreshRuntimeMetrics: refreshRuntimeMetrics,
        destroy: function () {
            if (paymentMetricsTimer) {
                clearInterval(paymentMetricsTimer);
                paymentMetricsTimer = null;
            }
            if (trafficMetricsTimer) {
                clearInterval(trafficMetricsTimer);
                trafficMetricsTimer = null;
            }
            if (runtimeMetricsTimer) {
                clearInterval(runtimeMetricsTimer);
                runtimeMetricsTimer = null;
            }
            Object.keys(charts).forEach(destroyChart);
        },
    };
}(window));
