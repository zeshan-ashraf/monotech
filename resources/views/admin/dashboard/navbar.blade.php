{{-- OPS Dashboard toolbar --}}
<header class="ops-navbar">
    <div class="ops-navbar__brand d-flex align-items-center">
        <i class="fa fa-line-chart me-2 text-primary"></i>
        <span class="fw-semibold">Operations Dashboard</span>
    </div>

    <div class="ops-navbar__actions d-flex align-items-center gap-3 ms-auto">
        {{-- Auto refresh toggle (UI only) --}}
        <div class="d-none d-md-flex align-items-center gap-2 ops-navbar__control">
            <span class="ops-navbar__label">Auto Refresh</span>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch" id="ops-auto-refresh" checked>
            </div>
        </div>

        {{-- Refresh interval dropdown (UI only) --}}
        <div class="dropdown ops-navbar__control">
            <button class="btn btn-sm ops-btn-ghost dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-refresh"></i>
                <span id="ops-refresh-label">10s</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end ops-dropdown">
                @foreach($refreshIntervals as $interval)
                    <li>
                        <button class="dropdown-item ops-refresh-option {{ $interval === '10s' ? 'active' : '' }}" type="button" data-interval="{{ $interval }}">
                            {{ $interval }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</header>
