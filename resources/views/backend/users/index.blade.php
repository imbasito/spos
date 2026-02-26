@extends('backend.master')

@section('title', 'User Management')

@section('content')



<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden" style="min-height: 70vh;">

      {{-- ── Premium Header ──────────────────────────────────────────────────── --}}
      <div class="card-header bg-gradient-maroon py-3 d-flex align-items-center">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-users mr-2"></i> System Users
        </h3>
        <span class="badge ml-3" style="background:rgba(255,255,255,0.2);color:#fff;font-size:.75rem;padding:3px 10px;border-radius:20px;font-weight:600;">
            {{ \App\Models\User::count() }} {{ Str::plural('user', \App\Models\User::count()) }}
        </span>
        @can('user_create')
        <a href="{{ route('backend.admin.user.create') }}" class="btn btn-light btn-md px-4 ml-auto shadow-sm hover-lift font-weight-bold text-maroon">
          <i class="fas fa-plus-circle mr-1"></i> Add New User
        </a>
        @endcan
      </div>

      {{-- ── Flash messages ──────────────────────────────────────────────────── --}}
      @if(session('success'))
      <div class="alert alert-success border-0 rounded-0 mb-0 py-2 px-4">
          <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
      </div>
      @endif
      @if(session('error'))
      <div class="alert alert-danger border-0 rounded-0 mb-0 py-2 px-4">
          <i class="fas fa-times-circle mr-2"></i> {{ session('error') }}
      </div>
      @endif

      <div class="card-body p-4">

        <!-- Spotlight Search -->
        <div class="row mb-4">
          <div class="col-md-12">
            <div class="input-group shadow-sm spotlight-search-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white border-0 pl-3">
                  <i class="fas fa-search text-maroon"></i>
                </span>
              </div>
              <input type="text" id="quickSearchInput" class="form-control border-0 py-4 apple-input"
                placeholder="Search by name or email..." autofocus style="font-size: 1rem; box-shadow: none;">
            </div>
          </div>
        </div>
          <table id="datatables" class="table table-hover mb-0 custom-premium-table">
            <thead>
              <tr>
                <th class="pl-4">Photo</th>
                <th>Full Name</th>
                <th>Email Address</th>
                <th>Role / Group</th>
                <th>Status</th>
                <th>Created</th>
                <th class="text-right pr-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              {{-- Loaded via AJAX DataTables --}}
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<style>
.custom-premium-table thead th {
    background-color: #4E342E !important;
    color: #ffffff !important;
    border: none;
    letter-spacing: .05em;
    padding-top: 13px;
    padding-bottom: 13px;
    font-size: .8rem;
    text-transform: uppercase;
    font-weight: 700;
}
.custom-premium-table tbody td {
    vertical-align: middle;
    color: #2d3748;
    border-bottom: 1px solid #edf2f9;
}
.custom-premium-table tr:last-child td { border-bottom: none; }
.custom-premium-table tbody tr:hover { background: #f8fafc; }
.text-maroon { color: #800000 !important; }
.img-circle { border-radius: 50%; }
</style>

@endsection

@push('script')
<script>
$(function () {
    var table = $('#datatables').DataTable({
        processing : true,
        serverSide : true,
        ajax       : '{{ route('backend.admin.users') }}',
        order      : [[1, 'asc']],
        columns    : [
            { data: 'thumb',    name: 'thumb',      orderable: false, searchable: false, className: 'pl-4' },
            { data: 'name',     name: 'name',        className: 'font-weight-bold' },
            { data: 'email',    name: 'email' },
            { data: 'roles',    name: 'roles' },
            { data: 'suspend',  name: 'is_suspended' },
            { data: 'created',  name: 'created_at' },
            { data: 'action',   name: 'action', orderable: false, searchable: false, className: 'text-right pr-4' }
        ],
        dom: 't<"p-3 d-flex justify-content-between align-items-center"ip>',
        language: {
            paginate: {
                previous: '<i class="fas fa-chevron-left"></i>',
                next:     '<i class="fas fa-chevron-right"></i>'
            }
        }
    });

    $('#quickSearchInput').on('keyup input', function () {
        table.search(this.value).draw();
    });
});
</script>
@endpush