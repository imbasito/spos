@extends('backend.master')

@section('title', 'Backup Manager')

@section('content')
<div class="row animate__animated animate__fadeIn">

    {{-- Alerts --}}
    <div class="col-12">
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-3">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm mb-3">
                <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
            </div>
        @endif
    </div>

    {{-- ====================== PAGE HEADER (single gradient header) ====================== --}}
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">
            <div class="card-header bg-gradient-maroon py-3 d-flex align-items-center">
                <h3 class="card-title font-weight-bold text-white mb-0">
                    <i class="fas fa-database mr-2"></i> Backup Manager
                </h3>
                <form action="{{ route('backend.admin.settings.backup.create') }}" method="post" class="ml-auto">
                    @csrf
                    <button type="submit" class="btn btn-light btn-md px-4 shadow-sm hover-lift font-weight-bold text-maroon">
                        <i class="fas fa-plus-circle mr-1"></i> Create Backup
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ====================== LEFT: Configuration ====================== --}}
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 border-radius-15 h-100">
            <div class="card-body p-4">
                <h6 class="font-weight-bold text-uppercase text-muted mb-4" style="letter-spacing: .05em; font-size: .75rem;">
                    <i class="fas fa-cog mr-1"></i> Configuration
                </h6>

                <form action="{{ route('backend.admin.settings.backup.save') }}" method="post">
                    @csrf

                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Backup Storage Path</label>
                        <div class="input-group">
                            <input type="text" name="backup_path" id="backup_path_input"
                                   class="form-control apple-input"
                                   value="{{ $backupPath }}"
                                   placeholder="e.g. D:/Backups/SPOS">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-secondary btn-sm" id="btn-browse-dir" title="Browse folder">
                                    <i class="fas fa-folder-open"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Full server path for backup storage.</small>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold small">Auto Backup</label>
                        <select name="auto_backup" class="form-control custom-select apple-input">
                            <option value="off"    {{ $autoBackup == 'off'    ? 'selected' : '' }}>Disabled</option>
                            <option value="daily"  {{ $autoBackup == 'daily'  ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ $autoBackup == 'weekly' ? 'selected' : '' }}>Weekly</option>
                        </select>
                    </div>

                    <button type="submit" class="btn bg-gradient-maroon text-white px-4 font-weight-bold shadow-sm">
                        <i class="fas fa-save mr-1"></i> Save
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ====================== RIGHT: Backup History ====================== --}}
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">
            <div class="card-body p-0">
                <div class="px-4 pt-4 pb-2 d-flex align-items-center">
                    <h6 class="font-weight-bold text-uppercase text-muted mb-0" style="letter-spacing: .05em; font-size: .75rem;">
                        <i class="fas fa-history mr-1"></i> Backup History
                    </h6>
                    <span class="badge badge-light border ml-auto">{{ $backups->total() }} Files</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0 custom-premium-table">
                        <thead>
                            <tr>
                                <th class="pl-4">Filename</th>
                                <th class="text-right pr-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($backups as $backup)
                            <tr>
                                <td class="pl-4 align-middle">
                                    <div class="font-weight-bold text-dark">{{ $backup['filename'] }}</div>
                                    <div class="small text-muted">
                                        {{ $backup['date'] }} <span class="mx-1">·</span> {{ $backup['size'] }}
                                    </div>
                                </td>
                                <td class="text-right pr-4 align-middle">
                                    <div class="d-flex justify-content-end" style="gap: 0.25rem;">

                                        <form action="{{ route('backend.admin.settings.backup.restore', $backup['filename']) }}"
                                              method="POST" class="d-inline restore-form">
                                            @csrf
                                            <button type="button" class="btn btn-sm btn-danger restore-btn"
                                                    data-filename="{{ $backup['filename'] }}">
                                                <i class="fas fa-undo-alt mr-1"></i> Restore
                                            </button>
                                        </form>

                                        <a href="{{ route('backend.admin.settings.backup.download', $backup['filename']) }}"
                                           class="btn btn-sm btn-light border shadow-sm" title="Download">
                                            <i class="fas fa-download text-primary"></i>
                                        </a>

                                        <form action="{{ route('backend.admin.settings.backup.delete', $backup['filename']) }}"
                                              method="POST" class="d-inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-light border shadow-sm delete-btn"
                                                    data-filename="{{ $backup['filename'] }}" title="Delete">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-2x mb-2 d-block text-light"></i>
                                    No backup files found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($backups->hasPages())
                    <div class="px-4 py-3 border-top">
                        {{ $backups->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

@push('style')
<style>
    /* Fix pagination active page color to match maroon theme */
    .pagination .page-item.active .page-link {
        background-color: #800000 !important;
        border-color: #800000 !important;
        color: #fff !important;
    }
    .pagination .page-link {
        color: #800000;
    }
    .pagination .page-link:hover {
        color: #600000;
    }
    .border-radius-15 { border-radius: 15px; }
</style>
@endpush

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', () => {

        // --- Browse Button ---
        const btnBrowse = document.getElementById('btn-browse-dir');
        const inputPath = document.getElementById('backup_path_input');

        btnBrowse.addEventListener('click', async () => {
            if (window.electron && window.electron.openDirectory) {
                try {
                    const path = await window.electron.openDirectory();
                    if (path) inputPath.value = path;
                } catch (e) {
                    Swal.fire('Error', 'Could not open directory picker.', 'error');
                }
            } else {
                Swal.fire({
                    title: 'Desktop Only',
                    text: 'The folder browser is only available in the desktop app. Please type the server path manually.',
                    icon: 'info',
                    confirmButtonColor: '#800000'
                });
            }
        });

        // --- Restore: Swal confirm ---
        document.querySelectorAll('.restore-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const filename = this.dataset.filename;
                const form = this.closest('.restore-form');
                Swal.fire({
                    title: 'Restore Database?',
                    html: `<p>This will overwrite <strong>all current data</strong> with:</p>
                           <code class="text-danger">${filename}</code>
                           <p class="mt-2 text-muted small">Your current tables will be renamed and removed after a successful restore.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-undo-alt mr-1"></i> Yes, Restore',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#c0392b',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    allowOutsideClick: false
                }).then(result => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });

        // --- Delete: Swal confirm ---
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const filename = this.dataset.filename;
                const form = this.closest('.delete-form');
                Swal.fire({
                    title: 'Delete Backup?',
                    html: `<code>${filename}</code><p class="mt-2 text-muted small">This cannot be undone.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-trash mr-1"></i> Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#c0392b',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true
                }).then(result => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });

    });
</script>
@endpush
@endsection
