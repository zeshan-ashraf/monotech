@extends('admin.layout.app')
@section('title', 'Payin')
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
                <section class="invoice-preview-wrapper">
                    <div class="row sticky-top">
                        <div class="col-12">
                            <div class="card w-100">
                                <div class="card-body">
                                    <div>
                                        <div class="toolbar w-100">
                                            <form action="{{ route('admin.transaction.zig_list') }}" method="GET"
                                                class="d-flex justify-content-between">
                                                <div class="col-md-4">
                                                    <fieldset>
                                                        <div class="input-group">
                                                            <input name="start_date" type="date"
                                                                class="form-control border-primary"
                                                                value="{{ $start }}">
                                                            <span class="btn btn-outline-primary">to</span>
                                                            <input name="end_date" type="date"
                                                                class="form-control border-primary"
                                                                value="{{ $end }}">
                                                        </div>
                                                    </fieldset>
                                                </div>
                                                <div class="col-md-4">
                                                    <select name="status" class="form-select border-primary">
                                                        <option selected disabled>Apply Filter</option>
                                                        <option value=""
                                                            {{ request()->status == '' ? 'selected' : '' }}>All
                                                        </option>
                                                        <option value="pending"
                                                            {{ request()->status == 'pending' ? 'selected' : '' }}>Pending
                                                        </option>
                                                        <option value="failed"
                                                            {{ request()->status == 'failed' ? 'selected' : '' }}>Failed
                                                        </option>
                                                        <option value="success"
                                                            {{ request()->status == 'success' ? 'selected' : '' }}>Success
                                                        </option>
                                                        
                                                    </select>
                                                </div>
                                                <div>
                                                    <button class="btn btn-outline-primary waves-effect me-3"
                                                        type="submit">Apply</button>
                                                    <a href="{{ route('admin.transaction.zig_list') }}"
                                                        class="btn btn-outline-danger waves-effect" type="submit">Reset</a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card bg-primary">
                                        <div class="card-body pb-50">
                                            <h5 class="text-white">Dated: <span class="fw-bolder" style="font-size:20px">{{ now()->format('d-m-Y') }}</span> </h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success">
                                        <div class="card-body pb-50">
                                            <h5 class="text-white">Total Payin: <span class="fw-bolder" style="font-size:20px">{{ number_format(round($totalPayinSuccessAmount, 2)) }} PKR</span>
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning">
                                        <div class="card-body pb-50">
                                            <h5 class="text-white">No Of Orders: <span class="fw-bolder" style="font-size:20px"> {{ number_format($totalPayinTransactionsCount)}} </span></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info">
                                        <div class="card-body pb-50">
                                            <h5 class="text-white">SR: <span class="fw-bolder" style="font-size:20px"> {{ round($payinSuccessRate, 2) }}% </span></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="row invoice-preview">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        {{ $dataTable->table(['class' => 'table text-center table-striped w-100'], true) }}
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
    @include('admin.components.datatablesScript')
    <script>
        $(document).on('change', '.status-dropdown', function() {
            var status = $(this).val();
            var id = $(this).data('id');
    
            $.ajax({
                url: '{{ route("admin.transaction.change_status") }}', // Correct route
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}', // CSRF protection
                    id: id,
                    status: status
                },
                success: function(response) {
                    alert('Status updated successfully!');
                    location.reload(); // Reload page to reflect changes
                },
                error: function(xhr, status, error) {
                    alert('Failed to update status: ' + xhr.responseJSON.message);
                }
            });
        });
    </script>
    
@endpush
