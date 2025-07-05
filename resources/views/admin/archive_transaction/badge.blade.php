@if($type == 'success')
    <span class="badge bg-success text-capitalize">{{$type}}</span>
@elseif($type == 'pending')
    <span class="badge bg-primary text-capitalize">{{$type}}</span>
@elseif($type == 'failed')
    <span class="badge bg-danger text-capitalize">{{$type}}</span>
@else
    <span class="badge bg-info text-capitalize">{{$type}}</span>
@endif

