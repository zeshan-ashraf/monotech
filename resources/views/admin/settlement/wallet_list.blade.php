@extends('admin.layout.app')
@section('title','Wallet History')
@push('css')
<link rel="stylesheet" href="{{ asset('admin/assets/dashboard/css/dataTables.bootstrap4.min.css') }}" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css" />
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
                                <h4 class="card-title text-capitalize">Wallet Transfer History</h4>
                            </div>
                            <div class="card-body p-1">
                                <div class="material-datatables">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-bordered m-b-0 datatables" cellspacing="0" width="100%">
                                            <thead class="table-dark border">
                                                <tr class="text-center">
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Client Name</th>
                                                    <th>Store Name</th>
                                                    <th>Wallet Transfer Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody class="border">
                                                @foreach($list as $item)
                                                    <tr class="text-center">
                                                        <td>{{ $item->date->format('d-M-Y') }}</td>
                                                        <td>{{ $item->time->format('h:i A') }}</td>
                                                        <td>{{ $item->user->name }}</td>
                                                        <td>{{ $item->store_name }}</td>
                                                        <td>{{ number_format(round($item->trans_amount, 0)) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.13.6/sorting/datetime-moment.js"></script>
    <!-- Buttons Export Dependencies -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $.fn.dataTable.moment('DD-MMM');
            $('.datatables').DataTable({
                dom: 'Bfrtip', 
                order: [[0, 'desc']],
                pageLength: 50,
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: 'Download Excel',
                        className: 'btn btn-success',
                        exportOptions: {
                            columns: ':visible'
                        }
                    }
                ],
                responsive: true
            });
        });
    </script>
@endpush