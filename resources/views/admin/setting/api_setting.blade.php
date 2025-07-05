@extends('admin.layout.app')
@section('title','Api Setting')
@push('css')
<link rel="stylesheet" href="{{ asset('admin/assets/dashboard/css/dataTables.bootstrap4.min.css') }}" />
@endpush
@section('content')
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="content-wrapper container-xxl pt-0 px-0 pb-sm-0 pb-5">
        <div class="content-header row">
        </div>
        <div class="content-body">
            <section id="row-grouping-datatable">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-bottom d-flex justify-content-between">
                                <h4 class="card-title text-capitalize">Api Setting</h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="material-datatables">
                                    <table class="table table-hover m-b-0 datatables" cellspacing="0" width="100%" style="width:100%">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Client Name</th>
                                                <th>Jazzcash</th>
                                                <th>Easypaisa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($list as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input 
                                                            class="form-check-input toggle-switch" 
                                                            type="checkbox" 
                                                            data-id="{{ $item->id }}"
                                                            data-type="jc_api"
                                                            @if($item->jc_api == 1) checked @endif
                                                        >
                                                        <label class="form-check-label">
                                                            <span class="status-label">
                                                                {{ $item->jc_api == 1 ? 'ON' : 'OFF' }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input 
                                                            class="form-check-input toggle-switch" 
                                                            type="checkbox" 
                                                            data-id="{{ $item->id }}" 
                                                            data-type="ep_api"
                                                            @if($item->ep_api == 1) checked @endif
                                                        >
                                                        <label class="form-check-label">
                                                            <span class="status-label">
                                                                {{ $item->ep_api == 1 ? 'ON' : 'OFF' }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header border-bottom d-flex justify-content-between">
                                <h4 class="card-title text-capitalize">Jazzcash Schedule Setting</h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="material-datatables">
                                    <table class="table table-hover m-b-0 datatables" cellspacing="0" width="100%" style="width:100%">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Type</th>
                                                <th>Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($list2 as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->type }}</td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input 
                                                            class="form-check-input toggle-switch-jc" 
                                                            type="checkbox" 
                                                            data-id="{{ $item->id }}" 
                                                            @if($item->value == 1) checked @endif
                                                        >
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card">
                            <div class="card-header border-bottom d-flex justify-content-between">
                                <h4 class="card-title text-capitalize">Easypaisa Schedule Setting</h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="material-datatables">
                                    <table class="table table-hover m-b-0 datatables" cellspacing="0" width="100%" style="width:100%">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Type</th>
                                                <th>Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($list3 as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->type }}</td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input 
                                                            class="form-check-input toggle-switch-ep" 
                                                            type="checkbox" 
                                                            data-id="{{ $item->id }}" 
                                                            @if($item->value == 1) checked @endif
                                                        >
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

@endsection
@push('js')
<script>
$(document).ready(function () {
    $('.toggle-switch').on('change', function () {
        const isChecked = $(this).is(':checked'); 
        const id = $(this).data('id');
        const type = $(this).data('type');
        const statusLabel = $(this).siblings('.form-check-label').find('.status-label');

        // Update label text
        statusLabel.text(isChecked ? 'ON' : 'OFF');

        // Send AJAX request to update backend
        updateToggleStatus(id, type, isChecked);
    });

    function updateToggleStatus(id, type, status) {
        $.ajax({
            url: '{{ route("admin.setting.api.suspend") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            contentType: 'application/json',
            data: JSON.stringify({ id: id, type: type, status: status ? 1 : 0 }),
            success: function (response) {
                console.log('Toggle updated:', response);
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
            },
        });
    }
});
</script>
<script>
    $(document).ready(function () {
        const switches = $('.toggle-switch-jc');

        switches.on('change', function () {
            if ($(this).is(':checked')) {
                // Uncheck all other switches
                switches.not(this).prop('checked', false);

                // Optional: Make an AJAX request to update the backend
                const id = $(this).data('id');
                updateCheckedToggleJc(id);
            }
        });

        function updateCheckedToggleJc(id) {
            var URL = '{{ route("admin.setting.schedule.save") }}';
            $.ajax({
                url: URL,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                success: function (response) {
                    console.log(response);
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                },
            });
        }
    });
</script>
<script>
    $(document).ready(function () {
        const switches = $('.toggle-switch-ep');

        switches.on('change', function () {
            if ($(this).is(':checked')) {
                // Uncheck all other switches
                switches.not(this).prop('checked', false);

                // Optional: Make an AJAX request to update the backend
                const id = $(this).data('id');
                updateCheckedToggleEp(id);
            }
        });

        function updateCheckedToggleEp(id) {
            var URL = '{{ route("admin.setting.schedule.save") }}';
            $.ajax({
                url: URL,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                success: function (response) {
                    console.log(response);
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                },
            });
        }
    });
</script>
@endpush
