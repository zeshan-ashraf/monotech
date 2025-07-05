<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabelThird">  Assigned @if($user->id == 18)Amount @else Percentage @endif</h5>
        <button type="button" class="close btn btn-danger" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <form action="{{route('admin.setting.save_assigned_amount')}}" method="post" enctype="multipart/form-data" class="submit_form">
        @csrf
        <input type="hidden" name="id" value="{{$user->id ?? ""}}">
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <label>Jazzcash @if($user->id == 18)Amount @else Percentage @endif</label>
                    <input class="form-control title_box" name="jc_assigned_value" value="{{$setting->jc_assigned_value}}" type="number" placehoder="0">
                </div>
                <div class="col-md-12">
                    <label>Easypaisa @if($user->id == 18)Amount @else Percentage @endif</label>
                    <input class="form-control title_box" name="ep_assigned_value" value="{{$setting->ep_assigned_value}}" type="number" placehoder="0">
                </div>
            </div>

        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
