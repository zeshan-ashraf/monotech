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

    function paymentSparklines(data) {
        var colors = themeColors();
        var colorMap = {
            success: colors.success,
            pending: colors.warning,
            failed: colors.danger,
            refunds: colors.info,
        };

        Object.keys(colorMap).forEach(function (key) {
            if (!data[key]) {
                return;
            }
            renderChart('ops-payment-' + key, sparklineOptions(data[key], colorMap[key]));
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

    function updatePaymentStatValue(key, value) {
        var chartEl = document.getElementById('ops-payment-' + key);

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

    function updatePaymentResponseStats(stats) {
        if (!stats) {
            return;
        }

        var footerStats = document.querySelectorAll('.ops-payments-panel .ops-panel__footer-stat strong');

        if (footerStats.length >= 2) {
            footerStats[0].textContent = stats.avg || '0.00 sec';
            footerStats[1].textContent = stats.max || '0.00 sec';
        }
    }

    function updatePaymentSparklines(sparklines) {
        if (!sparklines) {
            return;
        }

        paymentSparklines(sparklines);
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
                if (!payload || !payload.payments) {
                    return;
                }

                payload.payments.forEach(function (payment) {
                    updatePaymentStatValue(payment.key, payment.value);
                });

                updatePaymentSparklines(payload.sparklines);
                updatePaymentResponseStats(payload.payment_stats);
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
            });
        });

        var autoRefresh = document.getElementById('ops-auto-refresh');

        if (autoRefresh) {
            autoRefresh.addEventListener('change', schedulePaymentMetricsRefresh);
        }
    }

    function init() {
        var data = window.opsDashboardData;
        if (!data) {
            return;
        }

        if (data.overview) {
            overviewSparklines(data.overview);
        }

        if (data.phpfpm) {
            phpFpmDonut(data.phpfpm.workers);
            phpFpmMiniCharts(data.phpfpm);
        }

        if (data.payments) {
            paymentSparklines(data.payments);
        }

        if (data.history) {
            historyCharts(data.history);
        }

        bindNavbarControls();
        schedulePaymentMetricsRefresh();
    }

    document.addEventListener('DOMContentLoaded', init);

    window.OpsDashboard = {
        init: init,
        refreshPaymentMetrics: refreshPaymentMetrics,
        destroy: function () {
            if (paymentMetricsTimer) {
                clearInterval(paymentMetricsTimer);
                paymentMetricsTimer = null;
            }
            Object.keys(charts).forEach(destroyChart);
        },
    };
}(window));
