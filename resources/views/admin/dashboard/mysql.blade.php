{{-- MySQL status panel --}}
<section class="ops-card ops-panel h-100" aria-label="MySQL status">
    <div class="ops-panel__header">
        <h5 class="ops-panel__title mb-0">
            <i class="fa fa-database text-info me-2"></i>MySQL Status
        </h5>
    </div>
    <div class="ops-panel__body">
        <div class="row g-3">
            @foreach($mysql as $metric)
                <div class="col-sm-6">
                    <div class="ops-mysql-metric d-flex align-items-center gap-3">
                        <div class="ops-mysql-metric__icon bg-{{ $metric['color'] }} bg-opacity-10 text-{{ $metric['color'] }}">
                            <i class="fa {{ $metric['icon'] }}"></i>
                        </div>
                        <div>
                            <p class="ops-mysql-metric__label mb-0">{{ $metric['label'] }}</p>
                            <h5 class="ops-mysql-metric__value mb-0">{{ $metric['value'] }}</h5>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
