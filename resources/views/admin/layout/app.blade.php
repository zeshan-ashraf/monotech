<!doctype html>
<html lang="en" class="light-layout">

<head>
    @include('admin.layout.include.head')
    @stack('css')
</head>

<body class="pace-done vertical-layout vertical-menu-modern navbar-floating footer-static loaded light-layout menu-expanded" data-open="click" data-menu="vertical-menu-modern" data-col="">


<!--start wrapper-->
<div class="wrapper">
   {{--@include('admin.layout.include.header')--}}
   @include('admin.layout.include.sidebar')
   @yield('content')
   @include('admin.layout.include.footer')
   @include('admin.layout.include.script')

   <form id="logout-form" action="{{ route('logout') }}" method="POST"
      style="display: none;">
    @csrf
  </form>

  <form id="delete-form" action="" method="POST"
      style="display: none;">
    @csrf
    @method('delete')
  </form>
      <div class="modal fade CustomTypeModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
      aria-hidden="true">
      <div class="modal-dialog CustomTypeBody" role="document">

      </div>
  </div>
  <div class="d-flex justify-content-center">
    <div class="sipnner d-none position-absolute" style="top: 100px; z-index:9999">
        <div class=" rotate  " style="transform: rotate(270deg);">
            <div class="spinner-grow text-secondary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="spinner-grow text-secondary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="spinner-grow text-secondary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="spinner-grow text-secondary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>
</div>
<!--end wrapper-->
<script>
  @if(session('message'))
    toastr.success("{{ session('message') }}");
    @elseif(session('error'))
    toastr.error("{{ session('error') }}");
    @endif
</script>
<script>
  function logout(){
    $('#logout-form').submit();
  }
</script>
<script>
    $(document).on('click', '.open_modal', function () {
            var url = $(this).attr('data-url');
            var id = $(this).attr('data-id');
            var type = $(this).attr('data-type');
            $.ajax({
                type: "GET",
                data: {
                    id: id,
                    type: type,
                },
                url: url,
                success: function (response) {
                    $('.CustomTypeBody').empty();
                    $('.CustomTypeBody').html(response.html);
                    $('.CustomTypeModal').modal('show');
                }
            });
        })
    </script>
@stack('js')

</body>
</html>
