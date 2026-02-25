@extends('backend.master')

@section('title', 'Roles & Permissions')

@push('style')
<style>
.roles-header { background: linear-gradient(45deg, #800000, #A01010); border-radius: 12px 12px 0 0; }
.role-row { transition: background 0.15s; }
.role-row:hover { background: #fdf5f5 !important; }
.badge-role-count {
    background: rgba(255,255,255,0.2);
    color: #fff;
    font-size: .75rem;
    padding: 3px 9px;
    border-radius: 20px;
    font-weight: 600;
}
.role-search-input { border-radius: 8px; border: 1px solid #dee2e6; padding: 8px 14px; width: 240px; font-size: .9rem; }
.role-search-input:focus { outline: none; border-color: #800000; box-shadow: 0 0 0 3px rgba(128,0,0,0.1); }
</style>
@endpush

@section('content')



<div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">

    {{-- ── Premium Header ─────────────────────────────────────────────────────── --}}
    <div class="roles-header px-4 py-3 d-flex align-items-center">
        <i class="fas fa-user-shield fa-lg text-white mr-3"></i>
        <h3 class="font-weight-bold text-white mb-0">Roles &amp; Permissions</h3>
        <span class="badge-role-count ml-2">{{ $roles->count() }} {{ Str::plural('role', $roles->count()) }}</span>
        @can('role_create')
        <button class="btn btn-light btn-md px-4 ml-auto shadow-sm hover-lift font-weight-bold text-maroon"
                data-toggle="modal" data-target="#roleModal">
            <i class="fas fa-plus-circle mr-1"></i> Add Role
        </button>
        @endcan
    </div>

    {{-- ── Flash messages ──────────────────────────────────────────────────────── --}}
    @if(session('success'))
    <div class="alert alert-success border-0 rounded-0 mb-0 py-2 px-4 animate__animated animate__fadeIn">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('warning'))
    <div class="alert alert-warning border-0 rounded-0 mb-0 py-2 px-4">
        <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('warning') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger border-0 rounded-0 mb-0 py-2 px-4">
        <i class="fas fa-times-circle mr-2"></i> {{ session('error') }}
    </div>
    @endif

    {{-- ── Roles Table ─────────────────────────────────────────────────────────── --}}
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="rolesTable">
            <thead style="background: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                <tr>
                    <th class="pl-4" style="font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; font-weight: 700;">Role Name</th>
                    <th class="text-center" style="font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; font-weight: 700; width: 130px;">Permissions</th>
                    <th class="text-center" style="font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; font-weight: 700; width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                <tr class="role-row" data-role-name="{{ strtolower($role->name) }}">
                    <td class="pl-4 align-middle">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mr-3"
                                 style="width:36px;height:36px;background:{{ $role->name === 'Admin' ? '#800000' : '#6c757d' }};flex-shrink:0">
                                <i class="fas fa-{{ $role->name === 'Admin' ? 'crown' : 'user' }} text-white" style="font-size:.8rem;"></i>
                            </div>
                            <div>
                                <strong>{{ $role->name }}</strong>
                                @if($role->name === 'Admin')
                                <span class="badge badge-danger ml-1" style="font-size:.65rem;">Super Admin</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge badge-light border" style="font-size:.8rem; padding:5px 10px;">
                            {{ $role->permissions_count }} {{ Str::plural('permission', $role->permissions_count) }}
                        </span>
                    </td>
                    <td class="text-center align-middle">
                        <a href="{{ route('backend.admin.roles.show', $role->id) }}"
                           class="btn btn-sm btn-outline-secondary" title="Manage Permissions">
                            <i class="fas fa-key mr-1"></i> Permissions
                        </a>

                        @if(strtolower($role->name) !== 'admin')

                        @can('role_create')
                        <button class="btn btn-sm bg-gradient-primary ml-1" title="Edit Role"
                                data-toggle="modal" data-target="#editRole-{{ $role->id }}">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        @endcan

                        @can('role_delete')
                        <form action="{{ route('backend.admin.roles.delete', $role->id) }}"
                              method="POST" class="d-inline"
                              onsubmit="return confirm('Delete role \"{{ $role->name }}\"? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger ml-1" title="Delete Role">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                        @endcan

                        @endif
                    </td>
                </tr>

                {{-- Edit modal --}}
                @can('role_create')
                <div class="modal fade" id="editRole-{{ $role->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('backend.admin.roles.update', $role->id) }}">
                            @csrf @method('PUT')
                            <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                                <div class="modal-header" style="background: linear-gradient(45deg,#800000,#A01010);">
                                    <h5 class="modal-title text-white font-weight-bold">
                                        <i class="fas fa-pencil-alt mr-2"></i> Edit Role
                                    </h5>
                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-bold">Role Name</label>
                                        <input type="text" name="name" class="form-control"
                                               value="{{ $role->name }}" placeholder="Role Name" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn bg-gradient-primary">Save Changes</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endcan

                @empty
                <tr>
                    <td colspan="3" class="text-center py-5 text-muted">
                        <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                        No roles found. Create one to get started.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Create Role Modal ────────────────────────────────────────────────────── --}}
@can('role_create')
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('backend.admin.roles.create') }}">
            @csrf
            <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(45deg,#800000,#A01010);">
                    <h5 class="modal-title text-white font-weight-bold">
                        <i class="fas fa-plus-circle mr-2"></i> Add New Role
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Cashier" required autofocus>
                        <small class="text-muted">Role names must be unique. Avoid spaces (use underscores).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn bg-gradient-primary">Create Role</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

@endsection

@push('script')
<script>
// Live search filtering
document.getElementById('roleSearchInput').addEventListener('input', function () {
    const query = this.value.trim().toLowerCase();
    document.querySelectorAll('#rolesTable tbody tr[data-role-name]').forEach(function (row) {
        const name = row.getAttribute('data-role-name');
        row.style.display = (!query || name.includes(query)) ? '' : 'none';
    });
});
</script>
@endpush