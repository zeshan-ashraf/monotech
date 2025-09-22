<div class="main-menu menu-fixed menu-accordion menu-shadow menu-dark expanded" data-scroll-to-active="true">
    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
            <li class="nav-item me-auto">
                <a class="navbar-brand m-0 pt-1" href="{{ route('home') }}">
                    MONOTECH
                </a></li>
            <li class="nav-item nav-toggle"><a class="nav-link modern-nav-toggle pe-0" data-bs-toggle="collapse"><i
                        class="d-block d-xl-none text-primary toggle-icon font-medium-4" data-feather="x"></i><i
                        class="d-none d-xl-block collapse-toggle-icon font-medium-4  text-primary" data-feather="disc"
                        data-ticon="disc"></i></a></li>
        </ul>
    </div>
    <div class="shadow-bottom"></div>
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
            @if(auth()->user()->user_role != "Viewer")
            <li class="@if(url()->current() == route('admin.dashboard')) active @endif nav-item"><a
                    class="d-flex align-items-center" href="{{ route('admin.dashboard') }}">
                    <i data-feather="home"></i>Dashboard</a>
            </li>
            <li class="@if(url()->current() == route('admin.profile')) active @endif nav-item"><a
                    class="d-flex align-items-center" href="{{ route('admin.profile') }}">
                    <i data-feather="user-check"></i>Profile</a>
            </li>
            @endif
            @if(auth()->user()->user_role == "Viewer")
            <li class="@if(url()->current() == route('admin.zig_dashboard')) active @endif nav-item"><a
                    class="d-flex align-items-center" href="{{ route('admin.zig_dashboard') }}">
                    <i data-feather="home"></i>Dashboard</a>
            </li>
            @endif
            @if(auth()->user()->user_role == "Super Admin")
                <li class="@if (url()->current() == route('admin.searching.sr_list')) active @endif  nav-item">
                    <a class="d-flex align-items-center" href="{{ route('admin.searching.sr_list') }}"><i
                            data-feather="users"></i>SR Calculator</a>
                </li>
            @endif
            @can('Clients')
                <li class="@if (url()->current() == route('admin.client.user_list')) active @endif  nav-item">
                    <a class="d-flex align-items-center" href="{{ route('admin.client.user_list') }}"><i
                            data-feather="users"></i>Client Fee</a>
                </li>
                <li class="@if (url()->current() == route('admin.client.list')) active @endif  nav-item">
                    <a class="d-flex align-items-center" href="{{ route('admin.client.list') }}"><i
                            data-feather="globe"></i>Sub Store</a>
                </li>
            @endcan
            @can('Authorization')
            <li class=" "><a class="d-flex align-items-center" href="#"><i data-feather='git-pull-request'></i>Permission</a>
                <ul class="menu-content">
                    @can('Roles')
                    <li class="nav-item @if(Route::is('admin.roles.index')) active @endif">
                        <a href="{{ route('admin.roles.index') }}"><i data-feather='zap'></i>Roles</a>
                    </li>
                    @endcan
                    @can('Team Members')
                    <li class="nav-item @if(Route::is('admin.teams.index')) active @endif">
                        <a href="{{ route('admin.teams.index') }}"><i data-feather='user'></i>Team</a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcan
            @can('Transactions')
            <li class="@if (url()->current() == route('admin.transaction.list')) active @endif nav-item"><a
                    class="d-flex align-items-center" href="{{ route('admin.transaction.list') }}">
                    <i data-feather="dollar-sign"></i>Payin</a>
            </li>
            @endcan
            @can('Payouts')
            <li class="@if (url()->current() == route('admin.payout.list')) active @endif nav-item"><a
                    class="d-flex align-items-center" href="{{ route('admin.payout.list') }}">
                    <i data-feather="award"></i>Payout</a>
            </li>
            @endcan
            @if(auth()->user()->user_role == "Super Admin")
                <li class="  nav-item">
                    <a class="d-flex align-items-center @if (url()->current() == route('admin.setting.list')) active @endif"
                        href="{{ route('admin.setting.list') }}"><i data-feather='credit-card'></i>Reversed Payin</a>
                </li>
            @endif
            @if(auth()->user()->id == "2")
                <li class="nav-item">
                    <a class="d-flex align-items-center @if (url()->current() == route('admin.setting.ok_list')) active @endif"
                        href="{{ route('admin.setting.ok_list') }}"><i data-feather='credit-card'></i>Reversed Payin</a>
                </li>
            @endif
            @can('Settlement')
                @if(auth()->user()->user_role == "Super Admin")
                    <li class="  nav-item">
                        <a class="d-flex align-items-center" href="#"><i
                                data-feather='briefcase'></i>Settlement</a>
                        <ul class="menu-content">
                            <li class="@if (url()->current() == route('admin.settlement.ok')) active @endif nav-item"><a class="d-flex align-items-center"
                                    href="{{ route('admin.settlement.ok') }}">
                                    <i data-feather="circle"></i>OK Pay</a>
                            </li>
                            <li class="@if (url()->current() == route('admin.settlement.piq')) active @endif nav-item"><a class="d-flex align-items-center"
                                    href="{{ route('admin.settlement.piq') }}">
                                    <i data-feather="circle"></i>PIQ Pay</a>
                            </li>
                            <li class="@if (url()->current() == route('admin.settlement.pkn')) active @endif nav-item"><a class="d-flex align-items-center"
                                    href="{{ route('admin.settlement.pkn') }}">
                                    <i data-feather="circle"></i>PK9 Pay</a>
                            </li>
                            {{--<li class="@if (url()->current() == route('admin.settlement.cspkr')) active @endif nav-item"><a class="d-flex align-items-center"
                                    href="{{ route('admin.settlement.cspkr') }}">
                                    <i data-feather="circle"></i>C7 PKR</a>
                            </li>
                            <li class="@if (url()->current() == route('admin.settlement.toppay')) active @endif nav-item"><a class="d-flex align-items-center"
                                    href="{{ route('admin.settlement.toppay') }}">
                                    <i data-feather="circle"></i>Top Pay</a>
                            </li>
                            <li class="@if (url()->current() == route('admin.settlement.corepay')) active @endif nav-item"><a class="d-flex align-items-center"
                                    href="{{ route('admin.settlement.corepay') }}">
                                    <i data-feather="circle"></i>Core Pay</a>
                            </li>
                            <li class="@if (url()->current() == route('admin.settlement.genxpay')) active @endif nav-item"><a class="d-flex align-items-center"
                                    href="{{ route('admin.settlement.genxpay') }}">
                                    <i data-feather="circle"></i>Genx Pay</a>
                            </li>--}}
                            <li class="@if (url()->current() == route('admin.settlement.moneypay')) active @endif nav-item"><a class="d-flex align-items-center"
                                    href="{{ route('admin.settlement.moneypay') }}">
                                    <i data-feather="circle"></i>Money Pay</a>
                            </li>
                        </ul>
                    </li>
                @endif
                @if(auth()->user()->id == "2")
                    <li class=" nav-item"><a class="d-flex align-items-center"
                            href="{{ route('admin.settlement.ok') }}">
                            <i data-feather="briefcase"></i>Settlement</a>
                    </li>
                @endif
                @if(auth()->user()->id == "4")
                    <li class=" nav-item"><a class="d-flex align-items-center"
                            href="{{ route('admin.settlement.piq') }}">
                            <i data-feather="briefcase"></i>Settlement</a>
                    </li>
                @endif
                @if(auth()->user()->id == "5")
                    <li class=" nav-item"><a class="d-flex align-items-center"
                            href="{{ route('admin.settlement.pkn') }}">
                            <i data-feather="briefcase"></i>Settlement</a>
                    </li>
                @endif
                @if(auth()->user()->id == "9")
                    <li class=" nav-item"><a class="d-flex align-items-center"
                            href="{{ route('admin.settlement.cspkr') }}">
                            <i data-feather="briefcase"></i>Settlement</a>
                    </li>
                @endif
                @if(auth()->user()->id == "14")
                    <li class=" nav-item"><a class="d-flex align-items-center"
                            href="{{ route('admin.settlement.moneypay') }}">
                            <i data-feather="briefcase"></i>Settlement</a>
                    </li>
                @endif
            @endcan
            @can('Archive Transactions')
                <li class="  nav-item">
                    <a class="d-flex align-items-center" href="#"><i
                            data-feather='dollar-sign'></i>Archive Folders</a>
                    <ul class="menu-content">
                        <li class="@if (url()->current() == route('admin.archive.list')) active @endif  nav-item">
                            <a class="d-flex align-items-center" href="{{ route('admin.archive.list') }}"><i
                                    data-feather="dollar-sign"></i>Archive Payin</a>
                        </li>
                        <li class="@if (url()->current() == route('admin.archive.payout_list')) active @endif  nav-item">
                            <a class="d-flex align-items-center" href="{{ route('admin.archive.payout_list') }}"><i
                                    data-feather="award"></i>Archive Payout</a>
                        </li>
                        <li class="@if (url()->current() == route('admin.archive.backup_list')) active @endif  nav-item">
                            <a class="d-flex align-items-center" href="{{ route('admin.archive.backup_list') }}"><i
                                    data-feather="dollar-sign"></i>Backup Payin</a>
                        </li>
                    </ul>
                </li>
            @endcan
            @can('Searching')
                <li class="  nav-item">
                    <a class="d-flex align-items-center @if (url()->current() == route('admin.searching.list')) active @endif"
                        href="{{ route('admin.searching.list') }}"><i data-feather='search'></i>Payin Search</a>
                </li>
                <li class="  nav-item">
                    <a class="d-flex align-items-center @if (url()->current() == route('admin.searching.payout_list')) active @endif"
                        href="{{ route('admin.searching.payout_list') }}"><i data-feather='search'></i>Payout Search</a>
                </li>
            @endcan
            @if(auth()->user()->user_role == "Super Admin")
                <li class="@if (url()->current() == route('admin.setting.get.suspend')) active @endif  nav-item">
                    <a class="d-flex align-items-center" href="{{ route('admin.setting.get.suspend') }}"><i
                            data-feather="settings"></i>Setting</a>
                </li>
            @endif
            <li class="nav-item">
                <a class="d-flex align-items-center" href="#" onclick="logout();">
                    <i data-feather="log-out"></i>Logout</a>
            </li>
        </ul>
    </div>
</div>
