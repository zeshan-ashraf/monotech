{{-- OPS Dashboard top navigation --}}
<header class="ops-navbar">
    <div class="ops-navbar__brand d-none d-md-flex align-items-center">
        <i class="fa fa-line-chart me-2 text-primary"></i>
        <span class="fw-semibold">MONOTECH OPS DASHBOARD</span>
    </div>

    <div class="ops-navbar__search flex-grow-1 mx-md-3">
        <div class="input-group ops-search">
            <span class="input-group-text border-0 bg-transparent">
                <i class="fa fa-search text-muted"></i>
            </span>
            <input type="search" class="form-control border-0 bg-transparent" placeholder="Search anything..." aria-label="Search">
        </div>
    </div>

    <div class="ops-navbar__actions d-flex align-items-center gap-3">
        {{-- Auto refresh toggle (UI only) --}}
        <div class="d-none d-lg-flex align-items-center gap-2 ops-navbar__control">
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

        {{-- Notifications (UI only) --}}
        <button class="btn btn-sm ops-btn-ghost ops-navbar__icon-btn position-relative" type="button" aria-label="Notifications">
            <i class="fa fa-bell"></i>
            <span class="ops-badge ops-badge--danger">3</span>
        </button>

        {{-- User profile dropdown --}}
        <div class="dropdown">
            <button class="btn ops-navbar__profile dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="{{ asset('admin/images/portrait/small/avatar-s-11.jpg') }}" alt="Admin" class="ops-navbar__avatar">
                <div class="d-none d-md-block text-start">
                    <div class="ops-navbar__username">{{ auth()->user()->name ?? 'Admin' }}</div>
                    <div class="ops-navbar__role">{{ auth()->user()->user_role ?? 'Super Admin' }}</div>
                </div>
                <i class="fa fa-chevron-down ms-1 ops-navbar__chevron"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end ops-dropdown">
                <li><a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="fa fa-user me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="{{ route('admin.account.settings') }}"><i class="fa fa-cog me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="logout(); return false;"><i class="fa fa-sign-out me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</header>
