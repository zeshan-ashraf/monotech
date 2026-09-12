<h2 class="api-docs-section-title">Required request headers</h2>
<div class="table-responsive">
    <table class="api-docs-table">
        <thead>
            <tr>
                <th>Header</th>
                <th>Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($headers as $header)
                <tr>
                    <td><code>{{ $header['name'] }}</code></td>
                    <td>{{ $header['type'] }}</td>
                    <td>{!! $header['description'] !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
