{{-- Payments overview panel --}}
<section class="ops-card ops-panel ops-payments-panel h-100" aria-label="Payments overview">
    <div class="ops-panel__header d-flex align-items-center justify-content-between">
        <h5 class="ops-panel__title mb-0">
            <i class="fa fa-credit-card text-success me-2"></i>Payments Overview
        </h5>
        <a href="{{ route('admin.transaction.list') }}" class="ops-panel__action">View All</a>
    </div>

    <div class="ops-panel__body">

        @foreach($gatewayPayments as $gatewaySection)
            <x-ops-dashboard.gateway-payments
                :gateway="$gatewaySection"
                :cards="$gatewaySection['cards']"
                :payment-stats="$gatewaySection['payment_stats']"
            />

            @if(! $loop->last)
                <hr class="ops-gateway-payments__divider my-4">
            @endif
        @endforeach

        {{-- Recent transactions --}}
        <div class="d-flex align-items-center justify-content-between mb-2 mt-4">
            <h6 class="ops-subtitle mb-0">Recent Transactions</h6>
        </div>
        <div class="table-responsive ops-table-wrap">
            <table class="table table-sm ops-table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th class="text-end">Response</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $txn)
                        <tr>
                            <td><a href="#" class="ops-link">#{{ $txn['id'] }}</a></td>
                            <td>{{ $txn['type'] }}</td>
                            <td class="fw-semibold">{{ $txn['amount'] }}</td>
                            <td><x-ops-dashboard.status-badge :status="$txn['status']" /></td>
                            <td class="text-muted">{{ $txn['time'] }}</td>
                            <td class="text-end {{ ($txn['response_slow'] ?? false) ? 'text-danger fw-semibold' : '' }}">
                                {{ $txn['response_time'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
