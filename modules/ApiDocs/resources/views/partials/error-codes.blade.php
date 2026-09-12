<h2 class="api-docs-section-title">Error codes</h2>
<div class="table-responsive">
    <table class="api-docs-table">
        @php
            $showDetail = collect($errorCodes)->contains(function ($item) {
                return isset($item['message']) || isset($item['reason']);
            });
        @endphp
        <thead>
            <tr>
                <th>HTTP</th>
                @if($showDetail)
                    <th>API message</th>
                    <th>Why it happens</th>
                @else
                    <th>Description</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($errorCodes as $error)
                <tr>
                    <td><code>{{ $error['code'] }}</code></td>
                    @if($showDetail)
                        <td>{!! $error['message'] ?? ($error['description'] ?? '') !!}</td>
                        <td>{!! $error['reason'] ?? '' !!}</td>
                    @else
                        <td>{!! $error['description'] ?? '' !!}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
