@extends('backend.master')

@section('title', $role->name . ' — Permissions')

@push('style')
<style>
.perm-header { background: linear-gradient(45deg, #800000, #A01010); border-radius: 12px 12px 0 0; }
.perm-group-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 1.5rem;
    height: 100%;
}
.perm-group-header {
    background: #f8f9fa;
    padding: 12px 16px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.perm-group-title {
    font-weight: 700;
    font-size: .85rem;
    text-transform: uppercase;
    color: #4E342E;
}
.perm-group-body { padding: 16px; }
.perm-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}
.perm-item label {
    font-size: .9rem;
    margin-bottom: 0;
    cursor: pointer;
    color: #495057;
    font-weight: 500;
}
.select-all-btn {
    font-size: .75rem;
    color: #800000;
    font-weight: 600;
    background: none;
    border: none;
    padding: 0;
    text-decoration: underline dotted;
}
.select-all-btn:hover { color: #500000; text-decoration: underline; }
</style>
@endpush

@section('content')

<form action="{{ route('backend.admin.update.role-permissions', $role->id) }}" method="post" id="permissionsForm">
@csrf

<div class="card shadow-sm border-0 border-radius-15 overflow-hidden">

    {{-- ── Header ───────────────────────────────────────────────────────────── --}}
    <div class="perm-header px-4 py-3 d-flex align-items-center">
        <div class="d-flex align-items-center">
            <h3 class="text-white font-weight-bold mb-0">{{ $role->name }} &mdash; Permissions</h3>
            <span class="badge ml-3" style="background:rgba(255,255,255,0.2);color:#fff;font-size:0.75rem;padding:4px 12px;border-radius:20px;">
                {{ $grouped->flatten()->count() }} Total
            </span>
        </div>
        <div class="ml-auto">
            <a href="{{ route('backend.admin.roles') }}" class="btn btn-light btn-md px-4 font-weight-bold shadow-sm hover-lift text-maroon">
                Back to Roles
            </a>
        </div>
    </div>

    {{-- ── Flash messages ──────────────────────────────────────────────────── --}}
    @if(session('success'))
    <div class="alert alert-success border-0 rounded-0 mb-0 py-3 px-4">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
    </div>
    @endif

    {{-- ── Admin notice ─────────────────────────────────────────────────────── --}}
    @if(strtolower($role->name) === 'admin')
    <div class="alert alert-info border-0 rounded-0 mb-0 py-2 px-4">
        <i class="fas fa-info-circle mr-2"></i>
        The <strong>Admin</strong> role automatically receives all permissions.
    </div>
    @endif

    <div class="card-body p-4 bg-light">

        {{-- Global Controls ──────────────────────────────────────────────── --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <p class="text-muted small mb-0 font-weight-bold text-uppercase">Module Specific Permissions</p>
            <div class="btn-group shadow-sm">
                <button type="button" class="btn btn-sm btn-white font-weight-bold" onclick="toggleAll(true)">Select All</button>
                <button type="button" class="btn btn-sm btn-white font-weight-bold" onclick="toggleAll(false)">Deselect All</button>
            </div>
        </div>

        {{-- ── Permission groups ─────────────────────────────────────────── --}}
        <div class="row">
            @foreach($grouped as $module => $perms)
            @php
                $allChecked = $perms->every(fn($p) => $role->hasPermissionTo($p->name));
            @endphp
            <div class="col-md-4 mb-4">
                <div class="card perm-group-card shadow-xs bg-white">
                    <div class="perm-group-header">
                        <span class="perm-group-title">
                            {{ $module }}
                        </span>
                        <button type="button" class="select-all-btn"
                                onclick="toggleGroup('group-{{ $module }}', this)">
                            {{ $allChecked ? 'Deselect all' : 'Select all' }}
                        </button>
                    </div>
                    <div class="perm-group-body" id="group-{{ $module }}">
                        @foreach($perms as $permission)
                        <div class="perm-item custom-control custom-switch">
                            <input type="checkbox"
                                   class="custom-control-input perm-checkbox"
                                   id="perm-{{ $permission->id }}"
                                   name="permissions[]"
                                   value="{{ $permission->name }}"
                                   {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="perm-{{ $permission->id }}">
                                {{ snakeToTitle($permission->name) }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Card Footer with Save Options ────────────────────────────────────── --}}
    <div class="card-footer bg-white border-top p-4 text-right">
        <a href="{{ route('backend.admin.roles') }}" class="btn btn-secondary px-4 mr-2">Cancel</a>
        <button type="submit" class="btn bg-gradient-primary px-5 font-weight-bold shadow-sm">
            Save Permissions
        </button>
    </div>
</div>

</form>

<style>
    .text-maroon { color: #800000 !important; }
    .border-radius-15 { border-radius: 15px; }
    .shadow-xs { box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .btn-white { background: #fff; border: 1px solid #dee2e6; color: #495057; }
    .btn-white:hover { background: #f8f9fa; }
</style>
@endsection

@push('script')
<script>
function toggleAll(checked) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = checked);
    document.querySelectorAll('.select-all-btn').forEach(btn => {
        btn.textContent = checked ? 'Deselect all' : 'Select all';
    });
}

function toggleGroup(groupId, btn) {
    const checkboxes = document.querySelectorAll('#' + groupId + ' .perm-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
    btn.textContent = allChecked ? 'Select all' : 'Deselect all';
}
</script>
@endpush