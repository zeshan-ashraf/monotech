@extends('admin.layout.app')
@section('title','Settlement')
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
                                <h4 class="card-title text-capitalize">{{ $results[0]->user->name }} Settlement</h4>
                            </div>
                            <div class="card-body p-1">
                                <div class="material-datatables">
                                    @php
                                        // Group the results by user_id
                                        $groupedResults = $results->groupBy('user_id');
                                    @endphp
                                    @if($groupedResults && $groupedResults->isNotEmpty())
                                        @foreach($groupedResults as $userId => $items)
                                            <div class="table-responsive">
                                                <table class="table table-hover table-bordered m-b-0 datatables" cellspacing="0" width="100%">
                                                    <thead class="table-dark border">
                                                        <tr class="text-center">
                                                            <th rowspan="2">Date</th>
                                                            <th rowspan="2">Opening Bal</th>
                                                            <th colspan="3">Payin</th>
                                                            <th rowspan="2">Payin Fee</th>
                                                            <th rowspan="2">Payin Bal</th>
                                                            <th colspan="3">Payout</th>
                                                            <th rowspan="2">Payout Fee</th>
                                                            <th rowspan="2">USDT</th>
                                                            <th rowspan="2">Settled</th>
                                                            <th rowspan="2">Closing Bal/Unsettled</th>
                                                            @if(auth()->user()->user_role == "Super Admin" && $loop->iteration == 1)
                                                                <th rowspan="2">Action</th>
                                                            @endif
                                                        </tr>
                                                        <tr class="text-center">
                                                            <th>JC</th>
                                                            <th>EP</th>
                                                            <th>Total</th>
                                                            <th>JC</th>
                                                            <th>EP</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="border">
                                                        @foreach($items as $item)
                                                            <tr class="text-center">
                                                                <td>{{ $item->date->format('d-M') }}</td>
                                                                <td>{{ number_format(round($item->opening_bal,0))}}</td>
                                                                <td>{{ number_format(round($item->jc_payin,0)) }}</td>
                                                                <td>{{ number_format(round($item->ep_payin,0)) }}</td>
                                                                <td>{{ number_format(round($item->jc_payin+$item->ep_payin,0)) }}</td>
                                                                <td>{{ number_format(round($item->jc_payin_fee + $item->ep_payin_fee,0)) }}</td>
                                                                <td>{{ number_format(round($item->payin_bal,0)) }}</td>
                                                                <td>{{ number_format(round($item->jc_payout,0)) }}</td>
                                                                <td>{{ number_format(round($item->ep_payout,0)) }}</td>
                                                                <td>{{ number_format(round($item->jc_payout+$item->ep_payout,0)) }}</td>
                                                                <td>{{ number_format($item->jc_payout_fee + $item->ep_payout_fee) }}</td>
                                                                <td>{{ number_format(round($item->usdt,0)) }}</td>
                                                                <td>{{ number_format(round(($item->settled),0)) }}</td>
                                                                <td>{{ number_format($item->closing_bal) }}</td>
                                                                @if(auth()->user()->user_role == "Super Admin")
                                                                    @if($loop->iteration == 1)
                                                                        <td>
                                                                            <a data-target="#attributeModal"
                                                                               class="btn btn-primary waves-effect waves-float waves-light open_modal" 
                                                                               data-url="{{route('admin.settlement.modal',$item->id)}}">
                                                                                Manual
                                                                            </a>
                                                                        </td>
                                                                    @else
                                                                        <td></td>
                                                                    @endif
                                                                @endif
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endforeach
                                    @else
                                        <p style="
                                            padding: 20px 0; 
                                            text-align: center; 
                                            font-size: 20px; 
                                            font-weight: bold; 
                                            color: #FF5733; 
                                            background-color: #f9f9f9; 
                                            border: 2px solid #FF5733; 
                                            border-radius: 10px; 
                                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
                                            font-family: 'Arial', sans-serif; 
                                            margin: 20px 0;">
                                            No record found
                                        </p>
                                    @endif
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