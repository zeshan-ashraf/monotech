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
        return {
            series: [{ data: series }],
            chart: {
                type: 'area',
                height: '100%',
                sparkline: { enabled: true },
                animations: { enabled: true, speed: 400 },
            },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 0.5,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                },
            },
            colors: [color || colors.primary],
            tooltip: { enabled: false },
        };
    }

    function overviewSparklines(data) {
        var colors = themeColors();
        var colorMap = {
            cpu: colors.primary,
            ram: colors.success,
            disk: colors.info,
            load: colors.warning,
            network: colors.secondary,
        };

        Object.keys(colorMap).forEach(function (key) {
            if (!data[key]) {
                return;
            }
            renderChart('ops-spark-' + key, sparklineOptions(data[key], colorMap[key]));
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
   * Refresh interval dropdown UI (no live polling in Phase 1).
   */
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
            });
        });
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
    }

    document.addEventListener('DOMContentLoaded', init);

    window.OpsDashboard = {
        init: init,
        destroy: function () {
            Object.keys(charts).forEach(destroyChart);
        },
    };
}(window));
