{{-- Payments overview panel --}}
<section class="ops-card ops-panel h-100" aria-label="Payments overview">
    <div class="ops-panel__header d-flex align-items-center justify-content-between">
        <h5 class="ops-panel__title mb-0">
            <i class="fa fa-credit-card text-success me-2"></i>Payments Overview
        </h5>
    </div>
    <div class="ops-panel__body">

        {{-- Payment summary cards --}}
        <div class="row g-3 mb-3">
            @foreach($payments as $payment)
                <div class="col-sm-6 col-xl-3">
                    <div class="ops-payment-stat ops-payment-stat--{{ $payment['color'] }}">
                        <p class="ops-payment-stat__label mb-1">{{ $payment['label'] }}</p>
                        <h4 class="ops-payment-stat__value text-{{ $payment['color'] }} mb-1">{{ number_format($payment['value']) }}</h4>
                        <div id="ops-payment-{{ $payment['key'] }}" class="ops-payment-stat__chart"></div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Recent transactions table --}}
        <h6 class="ops-subtitle mb-2">Recent Transactions</h6>
        <div class="table-responsive ops-table-wrap">
            <table class="table table-sm ops-table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Response Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $txn)
                        <tr>
                            <td><a href="#" class="ops-link">{{ $txn['id'] }}</a></td>
                            <td>{{ $txn['type'] }}</td>
                            <td>{{ $txn['amount'] }}</td>
                            <td><x-ops-dashboard.status-badge :status="$txn['status']" /></td>
                            <td class="text-muted">{{ $txn['time'] }}</td>
                            <td>{{ $txn['response_time'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- API response time footer --}}
        <div class="ops-panel__footer d-flex flex-wrap gap-4 mt-3 pt-3">
            <span class="text-muted small">
                Avg. API Response Time: <strong class="text-body">{{ $paymentStats['avg'] }}</strong>
            </span>
            <span class="text-muted small">
                Max API Response Time: <strong class="text-danger">{{ $paymentStats['max'] }}</strong>
            </span>
        </div>
    </div>
</section>
