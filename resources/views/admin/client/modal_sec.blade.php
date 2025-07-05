<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Client Fee Update</h5>
        <button type="button" class="close btn btn-danger" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <form action="{{route('admin.client.user.store')}}" method="post" enctype="multipart/form-data" class="submit_form">
        @csrf
        <input type="hidden" name="id" value="{{$item->id ?? ""}}">
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <label>Payin Fee</label>
                    <input class="form-control title_box" name="payin_fee" value="{{$item->payin_fee ?? ""}}" required type="text">
                </div>
                <div class="col-md-12">
                    <label>Payout Fee</label>
                    <input class="form-control title_box" name="payout_fee" value="{{$item->payout_fee ?? ""}}" required type="text">
                </div>
                <div class="col-md-12">
                    <label>Per Payin Fee</label>
                    <input class="form-control title_box" name="per_payin_fee" value="{{$item->per_payin_fee ?? ""}}" required type="text">
                </div>
                <div class="col-md-12">
                    <label>Per Payout Fee</label>
                    <input class="form-control title_box" name="per_payout_fee" value="{{$item->per_payout_fee ?? ""}}" required type="text">
                </div>
            </div>

        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
