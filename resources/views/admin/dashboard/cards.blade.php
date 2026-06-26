{{-- Overview metric cards --}}
<section class="ops-section mb-3" aria-label="System overview">
    <div class="row g-3">
        @foreach($overviewCards as $card)
            <div class="col-xl col-md-6">
                <x-ops-dashboard.metric-card
                    :title="$card['title']"
                    :value="$card['value']"
                    :subtitle="$card['subtitle']"
                    :icon="$card['icon']"
                    :color="$card['color']"
                    :chart-id="'ops-spark-' . $card['key']"
                />
            </div>
        @endforeach
    </div>
</section>
