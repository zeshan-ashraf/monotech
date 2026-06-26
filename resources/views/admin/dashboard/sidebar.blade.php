{{-- OPS Dashboard left sidebar --}}
<aside class="ops-sidebar">
    <nav class="ops-sidebar__nav" aria-label="OPS navigation">
        <ul class="ops-sidebar__menu list-unstyled mb-0">
            @foreach($sidebarNav as $item)
                <li class="ops-sidebar__item {{ $item['active'] ? 'ops-sidebar__item--active' : '' }}">
                    <a href="#" class="ops-sidebar__link d-flex align-items-center justify-content-between">
                        <span class="d-flex align-items-center gap-2">
                            <i class="fa {{ $item['icon'] }} ops-sidebar__icon"></i>
                            <span>{{ $item['label'] }}</span>
                        </span>
                        @if(!empty($item['badge']))
                            <span class="ops-badge ops-badge--danger">{{ $item['badge'] }}</span>
                        @elseif(!empty($item['has_children']))
                            <i class="fa fa-chevron-down ops-sidebar__chevron"></i>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- Server information panel --}}
    @include('admin.dashboard.server-info', ['serverInfo' => $serverInfo])
</aside>
