@extends('admin.layout.app')
@section('title', ($page['title'] ?? 'API Docs') . ' — ' . ($brand['name'] ?? 'API'))
@push('css')
<link rel="stylesheet" href="{{ asset('vendor/api-docs/css/api-docs.css') }}">
@endpush
@section('content')
<div class="app-content content api-docs-app">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper container-fluid p-0">
        <div class="api-docs-shell">
            @include('api-docs::partials.sidebar')

            <div class="api-docs-main">
                <div class="api-docs-content">
                    <span class="api-docs-version-badge">API {{ $brand['api_version'] ?? 'v1' }}</span>

                    <h1 class="api-docs-title">{{ $page['title'] }}</h1>

                    @if(isset($page['method'], $page['path']))
                        @include('api-docs::partials.endpoint-bar', [
                            'method' => $page['method'],
                            'url' => $baseUrl . $page['path'],
                        ])
                    @endif

                    @if(!empty($page['description']))
                        <p class="api-docs-description">{!! $page['description'] !!}</p>
                    @endif

                    @if(!empty($page['notices']))
                        @foreach($page['notices'] as $notice)
                            <div class="api-docs-notice api-docs-notice--{{ $notice['type'] ?? 'info' }}">
                                <div class="api-docs-notice-title">
                                    <i data-feather="alert-triangle"></i>
                                    <span>{{ $notice['title'] }}</span>
                                </div>
                                <ul class="api-docs-notice-list">
                                    @foreach($notice['items'] as $item)
                                        <li>{!! $item !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    @endif

                    @if(!empty($page['sections']))
                        @foreach($page['sections'] as $section)
                            <div class="api-docs-section">
                                <h2 class="api-docs-section-title">{{ $section['heading'] }}</h2>
                                <div class="api-docs-section-body">{!! $section['body'] !!}</div>
                            </div>
                        @endforeach
                    @endif

                    @if(!empty($page['headers']))
                        @include('api-docs::partials.headers-table', ['headers' => $page['headers']])
                    @endif

                    @if(!empty($page['parameters']))
                        @include('api-docs::partials.parameters-table', ['parameters' => $page['parameters']])
                    @endif

                    @if(!empty($page['responses']))
                        @include('api-docs::partials.responses-list', ['responses' => $page['responses']])
                    @endif

                    @if(!empty($page['error_codes']))
                        @include('api-docs::partials.error-codes', ['errorCodes' => $page['error_codes']])
                    @endif

                    @if(!empty($page['endpoints']))
                        @foreach($page['endpoints'] as $endpoint)
                            <div class="api-docs-endpoint-block">
                                <h2 class="api-docs-section-title">{{ $endpoint['title'] }}</h2>
                                @include('api-docs::partials.endpoint-bar', [
                                    'method' => $endpoint['method'],
                                    'url' => $baseUrl . $endpoint['path'],
                                ])
                                @if(!empty($endpoint['description']))
                                    <p class="api-docs-description">{!! $endpoint['description'] !!}</p>
                                @endif
                                @if(!empty($endpoint['headers']))
                                    @include('api-docs::partials.headers-table', ['headers' => $endpoint['headers']])
                                @endif
                                @if(!empty($endpoint['parameters']))
                                    @include('api-docs::partials.parameters-table', ['parameters' => $endpoint['parameters']])
                                @endif
                                @if(!empty($endpoint['responses']))
                                    @include('api-docs::partials.responses-list', ['responses' => $endpoint['responses']])
                                @endif
                                @if(!empty($endpoint['error_codes']))
                                    @include('api-docs::partials.error-codes', ['errorCodes' => $endpoint['error_codes']])
                                @endif
                                @if(!empty($endpoint['notes']))
                                    <div class="api-docs-notes">
                                        <h2 class="api-docs-section-title">Notes</h2>
                                        <ul>
                                            @foreach($endpoint['notes'] as $note)
                                                <li>{!! $note !!}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif

                    @if(!empty($page['callback_sections']))
                        <h2 class="api-docs-section-title">Callback payloads</h2>
                        <div class="api-docs-callback-grid">
                            @foreach($page['callback_sections'] as $callback)
                                <div class="api-docs-callback-card api-docs-callback-card--{{ $callback['type'] }}">
                                    <h3>{{ $callback['title'] }}</h3>
                                    <pre><code>{{ json_encode($callback['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($page['notes']))
                        <div class="api-docs-notes">
                            <h2 class="api-docs-section-title">Notes</h2>
                            <ul>
                                @foreach($page['notes'] as $note)
                                    <li>{!! $note !!}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                @include('api-docs::partials.code-panel')
            </div>
        </div>
    </div>
</div>
@endsection
@push('js')
<script src="{{ asset('vendor/api-docs/js/api-docs.js') }}"></script>
@endpush
