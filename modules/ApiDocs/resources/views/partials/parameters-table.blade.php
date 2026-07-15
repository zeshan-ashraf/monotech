<h2 class="api-docs-section-title">Request body parameters</h2>
<div class="table-responsive">
    <table class="api-docs-table">
        <thead>
            <tr>
                <th>Parameter</th>
                <th>Type</th>
                <th>Required</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($parameters as $param)
                <tr>
                    <td><code>{{ $param['name'] }}</code></td>
                    <td>{{ $param['type'] }}</td>
                    <td>
                        @if(!empty($param['required']))
                            <span class="api-docs-badge api-docs-badge--required">Yes</span>
                        @else
                            <span class="api-docs-badge">No</span>
                        @endif
                    </td>
                    <td>{!! $param['description'] !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
