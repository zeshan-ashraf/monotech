{{-- Bottom history charts row --}}
<section class="ops-section" aria-label="Historical metrics">
    <div class="row g-3">
        <div class="col-xl-6">
            <div class="ops-card ops-panel ops-chart-panel">
                <div class="ops-panel__header">
                    <h6 class="ops-panel__title mb-0">CPU Usage (%)</h6>
                </div>
                <div class="ops-panel__body">
                    <div id="ops-chart-cpu" class="ops-history-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="ops-card ops-panel ops-chart-panel">
                <div class="ops-panel__header">
                    <h6 class="ops-panel__title mb-0">RAM Usage (GB)</h6>
                </div>
                <div class="ops-panel__body">
                    <div id="ops-chart-ram" class="ops-history-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="ops-card ops-panel ops-chart-panel">
                <div class="ops-panel__header">
                    <h6 class="ops-panel__title mb-0">Network I/O (MB/s)</h6>
                </div>
                <div class="ops-panel__body">
                    <div id="ops-chart-network" class="ops-history-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="ops-card ops-panel ops-chart-panel">
                <div class="ops-panel__header">
                    <h6 class="ops-panel__title mb-0">MySQL Queries / Sec</h6>
                </div>
                <div class="ops-panel__body">
                    <div id="ops-chart-mysql" class="ops-history-chart"></div>
                </div>
            </div>
        </div>
    </div>
</section>
