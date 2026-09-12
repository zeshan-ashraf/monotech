@extends('admin.layout.app')
@section('title','Sub Store')
@push('css')
<link rel="stylesheet" href="{{ asset('admin/assets/dashboard/css/dataTables.bootstrap4.min.css') }}" />
<style>
    .site-status-spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid #d8d6de;
        border-top-color: #7367f0;
        border-radius: 50%;
        animation: site-status-spin 0.7s linear infinite;
        vertical-align: middle;
    }
    @keyframes site-status-spin {
        to { transform: rotate(360deg); }
    }
    .site-health-row th {
        width: 40%;
        font-weight: 600;
    }
</style>
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
                                <h4 class="card-title text-capitalize">Sub Stores</h4>
                                <div class="">
                                    <a data-target="#attributeModal"
                                        class="btn btn-primary waves-effect waves-float waves-light open_modal" data-url="{{route('admin.client.modal')}}">Add
                                        Sub Store</a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                        <div class="material-datatables">
                            <table class="table table-hover m-b-0 datatables" cellspacing="0" width="100%" style="width:100%">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        {{-- Image column hidden on list page only --}}
                                        {{-- <th>Image</th> --}}
                                        <th>Site Status</th>
                                        <th>Created On</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($list as $item)
                                    <tr data-client-id="{{ $item->id }}">
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$item->name}}</td>
                                        {{-- Image column hidden on list page only --}}
                                        {{--
                                        <td>
                                            <img src="{{asset($item->image)}}" width="100" alt="">
                                        </td>
                                        --}}
                                        <td class="site-status-cell" data-client-id="{{ $item->id }}">
                                            <span class="site-status-spinner" title="Checking..."></span>
                                            <span class="site-status-text text-muted ms-50">Checking...</span>
                                        </td>
                                        <td>{{$item->created_at->format('d-m-Y')}}</td>
                                        <td>
                                            <div class="d-flex justify-content-start">
                                                <a class="dropdown-item btn btn-primary w-auto open_modal me-1" data-url="{{route('admin.client.modal')}}" data-id="{{$item->id}}" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" data-bs-original-title="Edit">
                                                    <i class="fa fa-edit" ></i>
                                                </a>
                                                <a href="javascript:void(0)"
                                                   class="dropdown-item btn btn-info w-auto me-1 test-site-url"
                                                   data-id="{{ $item->id }}"
                                                   data-name="{{ $item->name }}"
                                                   data-bs-toggle="tooltip"
                                                   data-bs-placement="top"
                                                   title="Site health check"
                                                   data-bs-original-title="Site health check">
                                                    <i class="fa fa-heartbeat"></i>
                                                </a>
                                                <a onclick="deleteAlert('{{ route('admin.client.destroy', $item->id) }}')"
                                                class="dropdown-item delete-btn btn btn-danger w-auto mr-10p" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" data-bs-original-title="Delete">
                                                    <i class="fa fa-trash" ></i>
                                                </a>
                                            </div>
                                        </td>
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

{{-- Site health check detail modal --}}
<div class="modal fade" id="siteHealthModal" tabindex="-1" role="dialog" aria-labelledby="siteHealthModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="siteHealthModalLabel">Site health check</h5>
                <button type="button" class="close btn btn-danger" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="siteHealthLoading" class="text-center py-2">
                    <span class="site-status-spinner"></span>
                    <p class="mt-1 mb-0 text-muted">Checking live URL...</p>
                </div>
                <div id="siteHealthResult" class="d-none">
                    <table class="table table-bordered mb-0 site-health-row">
                        <tbody>
                            <tr>
                                <th>Name</th>
                                <td id="sh-name">—</td>
                            </tr>
                            <tr>
                                <th>Checked URL</th>
                                <td id="sh-url">—</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td id="sh-status">—</td>
                            </tr>
                            <tr>
                                <th>HTTP status code</th>
                                <td id="sh-http">—</td>
                            </tr>
                            <tr>
                                <th>Response time (latency)</th>
                                <td id="sh-latency">—</td>
                            </tr>
                            <tr>
                                <th>Final URL after redirects</th>
                                <td id="sh-final-url">—</td>
                            </tr>
                            <tr>
                                <th>SSL / certificate OK?</th>
                                <td id="sh-ssl">—</td>
                            </tr>
                            <tr>
                                <th>DNS resolve OK?</th>
                                <td id="sh-dns">—</td>
                            </tr>
                            <tr>
                                <th>Error message</th>
                                <td id="sh-error">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function ($) {
    var CHECK_URL = @json(url('/admin/client/check-url'));
    var CONCURRENCY = 3;
    var queue = [];
    var active = 0;

    function yesNo(value) {
        if (value === null || typeof value === 'undefined') {
            return 'N/A';
        }
        return value ? 'Yes' : 'No';
    }

    function renderRowStatus($cell, data) {
        var working = !!(data && data.working);
        var badgeClass = working ? 'badge badge-light-success' : 'badge badge-light-danger';
        var label = working ? 'Working' : 'Not working';
        $cell.html('<span class="' + badgeClass + '">' + label + '</span>');
    }

    function renderRowError($cell, message) {
        $cell.html('<span class="badge badge-light-danger">Not working</span>');
        if (message) {
            $cell.append(' <small class="text-muted d-block">' + $('<div>').text(message).html() + '</small>');
        }
    }

    function fetchCheck(id) {
        return $.ajax({
            type: 'GET',
            url: CHECK_URL + '/' + id,
            dataType: 'json',
            timeout: 20000
        });
    }

    function pumpQueue() {
        while (active < CONCURRENCY && queue.length > 0) {
            (function (job) {
                active++;
                var $cell = $('.site-status-cell[data-client-id="' + job.id + '"]');
                fetchCheck(job.id)
                    .done(function (data) {
                        renderRowStatus($cell, data);
                    })
                    .fail(function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Check failed';
                        renderRowError($cell, msg);
                    })
                    .always(function () {
                        active--;
                        pumpQueue();
                    });
            })(queue.shift());
        }
    }

    function enqueueStatusChecks() {
        $('.site-status-cell').each(function () {
            var id = $(this).data('client-id');
            if (id) {
                queue.push({ id: id });
            }
        });
        pumpQueue();
    }

    function fillHealthModal(data) {
        var working = !!(data && data.working);
        $('#sh-name').text(data.name || '—');
        $('#sh-url').text(data.checked_url || data.url || '—');
        $('#sh-status').html(
            working
                ? '<span class="badge badge-light-success">Working</span>'
                : '<span class="badge badge-light-danger">Not working</span>'
        );
        $('#sh-http').text(data.http_status != null ? data.http_status : '—');
        $('#sh-latency').text(data.latency_ms != null ? (data.latency_ms + ' ms') : '—');
        $('#sh-final-url').text(data.final_url || '—');
        $('#sh-ssl').text(yesNo(data.ssl_ok));
        $('#sh-dns').text(yesNo(data.dns_ok));
        $('#sh-error').text(data.error || '—');
    }

    function showHealthModalLoading(name) {
        $('#siteHealthModalLabel').text(name ? ('Site health check – ' + name) : 'Site health check');
        $('#siteHealthLoading').removeClass('d-none');
        $('#siteHealthResult').addClass('d-none');
        $('#siteHealthModal').modal('show');
    }

    function showHealthModalResult(data) {
        fillHealthModal(data);
        $('#siteHealthLoading').addClass('d-none');
        $('#siteHealthResult').removeClass('d-none');
    }

    $(document).on('click', '.test-site-url', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        var name = $(this).data('name') || '';
        var $btn = $(this);

        if (!id || $btn.data('busy')) {
            return;
        }

        $btn.data('busy', true);
        showHealthModalLoading(name);

        fetchCheck(id)
            .done(function (data) {
                showHealthModalResult(data);
                var $cell = $('.site-status-cell[data-client-id="' + id + '"]');
                renderRowStatus($cell, data);
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || xhr.statusText || 'Check failed';
                showHealthModalResult({
                    name: name,
                    url: '',
                    working: false,
                    http_status: null,
                    latency_ms: null,
                    final_url: null,
                    ssl_ok: null,
                    dns_ok: false,
                    error: msg
                });
                renderRowError($('.site-status-cell[data-client-id="' + id + '"]'), msg);
            })
            .always(function () {
                $btn.data('busy', false);
            });
    });

    $(function () {
        enqueueStatusChecks();
    });
})(jQuery);
</script>
@endpush
