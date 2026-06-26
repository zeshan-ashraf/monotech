{{-- Alerts & errors panel --}}
<section class="ops-card ops-panel h-100" aria-label="Alerts and errors">
    <div class="ops-panel__header d-flex align-items-center justify-content-between">
        <h5 class="ops-panel__title mb-0">
            <i class="fa fa-exclamation-circle text-danger me-2"></i>Alerts &amp; Errors
        </h5>
        <span class="ops-badge ops-badge--danger">{{ count($alerts) }}</span>
    </div>
    <div class="ops-panel__body ops-alerts-list">
        @foreach($alerts as $alert)
            <x-ops-dashboard.alert-item
                :severity="$alert['severity']"
                :icon="$alert['icon']"
                :title="$alert['title']"
                :description="$alert['description']"
                :time="$alert['time']"
            />
        @endforeach
    </div>
</section>
