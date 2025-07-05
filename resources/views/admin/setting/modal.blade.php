
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel"> {{$user->name}}</h5>
        <button type="button" class="close btn btn-danger" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <form action="{{route('admin.setting.save')}}" method="post" enctype="multipart/form-data" class="submit_form">
        @csrf
        <input type="hidden" name="id" value="{{$user->id ?? ""}}">
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <label>Jazzcash</label>
                    <input class="form-control title_box" name="jazzcash" value="" type="number" placehoder="0">
                </div>
                <div class="col-md-12">
                    <label>Easypaisa Amount</label>
                    <input class="form-control title_box" name="easypaisa" value="" type="number" placehoder="0">
                </div>
            </div>

        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
