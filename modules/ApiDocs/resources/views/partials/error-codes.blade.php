<h2 class="api-docs-section-title">Error codes</h2>
<div class="table-responsive">
    <table class="api-docs-table">
        <thead>
            <tr>
                <th>Error code</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($errorCodes as $error)
                <tr>
                    <td><code>{{ $error['code'] }}</code></td>
                    <td>{{ $error['description'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
