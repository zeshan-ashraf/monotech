<script src="{{  asset('admin/assets/dashboard/js/jquery.dataTables.min.js')}}"></script>
<script src="{{  asset('admin/assets/dashboard/js/dataTables.bootstrap4.min.js')}}"></script>


@if(in_array('data-table',$assets ?? []))
<script type="text/javascript" src="{{ asset('admin/vendors/js/datatables/dataTables.buttons.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('admin/vendors/js/datatables/pdfmake.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('admin/vendors/js/datatables/vfs_fonts.js') }}"></script>
<script type="text/javascript" src="{{ asset('admin/vendors/js/datatables/buttons.html5.min.js')}}"></script>
<script type="text/javascript" src="{{ asset('admin/vendors/js/datatables/buttons.print.min.js')}}"></script>
<script src="{{ asset('admin/vendors/js/datatables/buttons.server-side.js')}}"></script>
@endif
{{ $dataTable->scripts() }}