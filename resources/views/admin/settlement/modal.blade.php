<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Manual</h5>
        <button type="button" class="close btn btn-danger" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <form action="{{route('admin.settlement.store')}}" method="post" enctype="multipart/form-data" class="submit_form">
        @csrf
        <input type="hidden" name="id" value="{{$item->id ?? ""}}">
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <label>Previous USDT</label>
                    <input class="form-control title_box" name="previous_usdt" value="{{$item->usdt ?? ""}}" required readonly type="text">
                </div>
                <div class="col-md-12">
                    <label>USDT</label>
                    <input class="form-control title_box" name="usdt" value="0" required type="text">
                </div>
            </div>

        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
