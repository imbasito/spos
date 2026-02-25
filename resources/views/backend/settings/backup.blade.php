@extends('backend.master')

@section('title', 'Backup Manager')

@section('content')
<div class="row animate__animated animate__fadeIn">
    <div class="col-md-12">
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-4">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
            </div>
        @endif
    </div>

    <!-- Actions & Settings -->
    <div class="col-md-5 d-flex flex-column">
        {{-- Manual Backup Card --}}
        <div class="card shadow-sm border-0 border-radius-15 mb-4">
            <div class="card-header bg-gradient-maroon py-3">
                <h5 class="card-title text-white font-weight-bold mb-0">
                    Actions
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <h5 class="font-weight-bold mb-1">Backup Database</h5>
                    <p class="text-muted small mb-0">Create a safe snapshot of your current data.</p>
                </div>
                
                <form action="{{ route('backend.admin.settings.backup.create') }}" method="post">
                    @csrf
                    <button type="submit" class="btn bg-gradient-primary btn-block py-2 shadow-sm font-weight-bold border-radius-10">
                        Create New Backup
                    </button>
                </form>
            </div>
        </div>

        {{-- Settings Card --}}
        <div class="card shadow-sm border-0 border-radius-15 flex-grow-1">
            <div class="card-header bg-gradient-maroon py-3">
                <h5 class="card-title text-white font-weight-bold mb-0">
                    Configuration
                </h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('backend.admin.settings.backup.save') }}" method="post" class="h-100 d-flex flex-column">
                    @csrf
                    <div class="form-group mb-4">
                        <label class="font-weight-bold small text-uppercase">Backup Storage Path</label>
                        <div class="input-group">
                            <input type="text" name="backup_path" id="backup_path_input" class="form-control apple-input" value="{{ $backupPath }}" placeholder="e.g. D:/Backups/SPOS">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-secondary" id="btn-browse-dir">
                                    Browse
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Direct server path for backup storage.</small>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold small text-uppercase">Auto Backup Frequency</label>
                        <select name="auto_backup" class="form-control custom-select apple-input">
                            <option value="off" {{ $autoBackup == 'off' ? 'selected' : '' }}>Disabled</option>
                            <option value="daily" {{ $autoBackup == 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ $autoBackup == 'weekly' ? 'selected' : '' }}>Weekly</option>
                        </select>
                    </div>

                    <div class="mt-auto">
                        <button type="submit" class="btn bg-gradient-primary btn-block shadow-sm font-weight-bold">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Backup List Section -->
    <div class="col-md-7">
        <div class="card shadow-sm border-0 border-radius-15 h-100 d-flex flex-column overflow-hidden">
             <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                <h5 class="card-title text-dark font-weight-bold mb-0">
                    Backup History
                </h5>
                <span class="badge badge-light border ml-auto">{{ $backups->total() }} Files</span>
            </div>
            <div class="card-body p-0 table-responsive flex-grow-1">
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
                                    {{ $backup['date'] }} <span class="mx-1">•</span> {{ $backup['size'] }}
                                </div>
                            </td>
                            <td class="text-right pr-4 align-middle">
                                <div class="d-flex justify-content-end gap-1">
                                    <form action="{{ route('backend.admin.settings.backup.restore', $backup['filename']) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm bg-gradient-warning font-weight-bold" 
                                                onclick="return confirm('WARNING: Current database will be overwritten. Continue?')">
                                            Restore
                                        </button>
                                    </form>

                                    <a href="{{ route('backend.admin.settings.backup.download', $backup['filename']) }}" class="btn btn-sm btn-light border shadow-sm">
                                        <i class="fas fa-download text-primary"></i>
                                    </a>

                                    <form action="{{ route('backend.admin.settings.backup.delete', $backup['filename']) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border shadow-sm" onclick="return confirm('Delete this backup file?')">
                                            <i class="fas fa-trash text-danger"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center py-5 text-muted">
                                No backup files found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($backups->hasPages())
                <div class="card-footer bg-white border-top py-3">
                    <div class="d-flex justify-content-center">
                        {{ $backups->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .bg-gradient-maroon {
        background: linear-gradient(45deg, #800000, #A01010) !important;
    }
    .border-radius-15 { border-radius: 15px; }
    
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
        padding-top: 12px;
        padding-bottom: 12px;
    }
    .custom-premium-table tr:last-child td { border-bottom: none; }
    .custom-premium-table tbody tr:hover { background: #f8fafc; }
    
    .gap-1 { gap: 0.25rem; }

    /* Fix symmetry for the cards */
    .col-md-5, .col-md-7 {
        display: flex;
        flex-direction: column;
    }
</style>

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnBrowse = document.getElementById('btn-browse-dir');
        const inputPath = document.getElementById('backup_path_input');
        
        if (window.electron && window.electron.openDirectory) {
            btnBrowse.addEventListener('click', async () => {
                try {
                    const path = await window.electron.openDirectory();
                    if (path) inputPath.value = path;
                } catch (e) {
                    console.error('Directory select failed', e);
                }
            });
        } else {
            btnBrowse.style.display = 'none';
        }
    });
</script>
@endpush
@endsection
