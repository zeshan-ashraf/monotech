<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Client</h5>
        <button type="button" class="close btn btn-danger" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <form action="{{route('admin.client.store')}}" method="post" enctype="multipart/form-data" class="submit_form">
        @csrf
        <input type="hidden" name="id" value="{{$item->id ?? ""}}">
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <label>Name</label>
                    <input class="form-control title_box" name="name" value="{{$item->name ?? ""}}" required type="text">
                </div>
                <div class="col-md-12">
                    <label>Url</label>
                    <input class="form-control title_box" name="url" value="{{$item->url ?? ""}}" required type="text">
                </div>
                <div class="col-md-12">
                    <label>Image</label>
                    <input class="form-control title_box" name="photo" value="{{$item->photo ?? ""}}" type="file">
                </div>
            </div>

        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
