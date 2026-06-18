/**
 * Dashboard metrics charts — plug-and-play polling panel for payin SR + pending counts.
 *
 * Requires: ApexCharts (loaded globally), fetch API.
 * Copy: this file + config/dashboard_metrics.php + DashboardMetricsService + controller + Blade components.
 */
(function (window) {
    'use strict';

    var charts = {};
    var pollTimer = null;
    var apiUrl = null;

    function chartKey(userId, metric) {
        return 'dm-' + metric + '-' + userId;
    }

    function elementId(userId, metric) {
        return chartKey(userId, metric);
    }

    function createSemiCircleChart(containerId, value, color) {
        var el = document.getElementById(containerId);
        if (!el || typeof ApexCharts === 'undefined') {
            return;
        }

        if (charts[containerId]) {
            charts[containerId].destroy();
        }

        var options = {
            series: [value],
            chart: {
                type: 'radialBar',
                height: 160,
            },
            plotOptions: {
                radialBar: {
                    hollow: { size: '60%' },
                    startAngle: -90,
                    endAngle: 90,
                    track: { background: '#e0e0e0' },
                    dataLabels: {
                        name: { show: false },
                        value: {
                            fontSize: '22px',
                            color: '#333',
                            fontWeight: 'bold',
                            offsetY: 10,
                            formatter: function (val) {
                                return val + '%';
                            },
                        },
                    },
                },
            },
            colors: [color || '#FF4500'],
        };

        charts[containerId] = new ApexCharts(el, options);
        charts[containerId].render();
    }

    function createPendingChart(containerId, value, color, label) {
        var el = document.getElementById(containerId);
        if (!el || typeof ApexCharts === 'undefined') {
            return;
        }

        if (charts[containerId]) {
            charts[containerId].destroy();
        }

        var options = {
            chart: {
                type: 'radialBar',
                height: 150,
            },
            series: [value],
            labels: [label || 'Pending'],
            colors: [color || '#FF5733'],
            plotOptions: {
                radialBar: {
                    hollow: { size: '70%' },
                    dataLabels: {
                        show: true,
                        value: {
                            fontSize: '20px',
                            fontWeight: 'bold',
                            formatter: function (val) {
                                return val;
                            },
                        },
                    },
                },
            },
        };

        charts[containerId] = new ApexCharts(el, options);
        charts[containerId].render();
    }

    function renderClient(client) {
        var uid = client.user_id;

        createSemiCircleChart(elementId(uid, 'jc-sr'), client.jc_success_rate);
        createSemiCircleChart(elementId(uid, 'ep-sr'), client.ep_success_rate);
        createPendingChart(elementId(uid, 'jc-pending'), client.jc_pending, '#FF5733', 'JC Pending');
        createPendingChart(elementId(uid, 'ep-pending'), client.ep_pending, '#28C76F', 'EP Pending');
    }

    function updateClient(client) {
        var uid = client.user_id;
        var ids = [
            elementId(uid, 'jc-sr'),
            elementId(uid, 'ep-sr'),
            elementId(uid, 'jc-pending'),
            elementId(uid, 'ep-pending'),
        ];
        var values = [
            client.jc_success_rate,
            client.ep_success_rate,
            client.jc_pending,
            client.ep_pending,
        ];

        ids.forEach(function (id, index) {
            if (charts[id]) {
                charts[id].updateSeries([values[index]]);
            }
        });
    }

    function poll() {
        if (!apiUrl) {
            return;
        }

        fetch(apiUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Metrics poll failed: ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                (data.clients || []).forEach(updateClient);
            })
            .catch(function (error) {
                console.warn('[DashboardMetrics]', error.message);
            });
    }

    window.DashboardMetrics = {
        init: function (config) {
            config = config || {};
            apiUrl = config.apiUrl;

            (config.clients || []).forEach(renderClient);

            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }

            var intervalSeconds = parseInt(config.pollIntervalSeconds, 10);
            if (intervalSeconds > 0) {
                pollTimer = setInterval(poll, intervalSeconds * 1000);
            }
        },

        destroy: function () {
            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }

            Object.keys(charts).forEach(function (id) {
                if (charts[id]) {
                    charts[id].destroy();
                }
            });

            charts = {};
        },
    };
})(window);
