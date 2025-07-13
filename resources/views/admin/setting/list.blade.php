@extends('admin.layout.app')
@section('title','Reversed Payin')
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
                        <div class="card w-100">
                            <div class="card-body">
                                <div>
                                    <div class="toolbar w-100">
                                        <form action="{{ route('admin.setting.list') }}" method="GET"
                                            class="d-flex justify-content-between">
                                            <div class="col-md-4">
                                                <fieldset>
                                                    <div class="input-group">
                                                        <input name="start_date" type="date"
                                                            class="form-control border-primary"
                                                            value="{{ $start_date }}">
                                                        <span class="btn btn-outline-primary">to</span>
                                                        <input name="end_date" type="date"
                                                            class="form-control border-primary"
                                                            value="{{ $end_date }}">
                                                    </div>
                                                </fieldset>
                                            </div>
                                            <div class="col-md-4">
                                                <select name="txn_type" class="form-select border-primary">
                                                    <option selected disabled>Filter Type</option>
                                                    <option value="easypaisa"
                                                        {{ request()->txn_type == 'easypaisa' ? 'selected' : '' }}>Easypaisa
                                                    </option>
                                                    <option value="jazzcash"
                                                        {{ request()->txn_type == 'jazzcash' ? 'selected' : '' }}>Jazzcash
                                                    </option>
                                                </select>
                                            </div>
                                            <div>
                                                <button class="btn btn-outline-primary waves-effect me-3"
                                                    type="submit">Apply</button>
                                                <a href="{{ route('admin.setting.list') }}"
                                                    class="btn btn-outline-danger waves-effect" type="submit">Reset</a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="col-md-4">
                                    <div class="card bg-primary">
                                        <div class="card-body pb-50">
                                            <h5 class="text-white">Dated: <span class="fw-bolder"  style="font-size:20px">{{now()->format('d-m-Y')}}</span> </h5>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card bg-success">
                                        <div class="card-body pb-50">
                                            <h5 class="text-white">Total Reverse Amount: <span class="fw-bolder"  style="font-size:20px">{{number_format(round($summary->total_reverse_amount,2))}} PKR</span></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-success">
                                        <div class="card-body pb-50">
                                            <h5 class="text-white">Number Of Orders: <span class="fw-bolder"  style="font-size:20px">{{number_format(round($summary->reverse_count,2))}} PKR</span></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h4>Reversed Payin</h4>
                            </div>
                            <div class="card-body p-1">
                                <div class="material-datatables">
                                    <table class="table table-hover m-b-0 datatables" cellspacing="0" width="100%" style="width:100%">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Order Id</th>
                                                <th>Phone</th>
                                                <th>Transaction Id</th>
                                                <th>Transaction Ref No</th>
                                                <th>Amount</th>
                                                <th>Tran Type</th>
                                                <th>Status</th>
                                                <th>Created At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            
                                            @foreach($list as $item)
                                            <tr>
                                                <td>{{$loop->iteration}}</td>
                                                <td>{{$item->orderId}}</td>
                                                <td>{{$item->phone}}</td>
                                                <td>{{$item->transactionId}}</td>
                                                <td>{{$item->txn_ref_no}}</td>
                                                <td>{{$item->amount}}</td>
                                                <td>{{$item->txn_type}}</td>
                                                <th><span class="badge bg-secondary text-capitalize">Reverse</span></th>
                                                <td>{{$item->updated_at}}</td>
                                                
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
            $('.datatables').DataTable({
                dom: 'Bfrtip', 
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
