<aside class="api-docs-sidebar">
    <div class="api-docs-sidebar-brand">
        <img src="{{ asset($brand['logo'] ?? 'images/logo-2.png') }}" alt="{{ $brand['name'] ?? 'API' }}" class="api-docs-sidebar-logo">
        <span class="api-docs-sidebar-name">{{ $brand['name'] ?? 'API' }}</span>
    </div>

    <nav class="api-docs-sidebar-nav">
        <ul>
            @foreach($menu as $item)
                <li>
                    <a href="{{ route('admin.api-docs.show', $item['id']) }}"
                       class="api-docs-nav-link {{ $pageKey === $item['id'] ? 'is-active' : '' }}">
                        <i data-feather="{{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="api-docs-sidebar-help">
        <i data-feather="help-circle"></i>
        <div>
            <strong>Need help?</strong>
            <a href="mailto:{{ $brand['support_email'] ?? '' }}">{{ $brand['support_email'] ?? '' }}</a>
        </div>
    </div>
</aside>
