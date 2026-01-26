@extends('backend.master')

@section('title', 'System Audit Logs')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">System Activity Logs</h3>
                <div class="card-tools">
                    <span class="badge badge-info">Total: {{ $logs->total() }}</span>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Module</th>
                                <th>Description</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>
                                    {{ date('d M Y', strtotime($log->created_at)) }}<br>
                                    <small class="text-muted">{{ date('h:i A', strtotime($log->created_at)) }}</small>
                                </td>
                                <td>
                                    @if($log->user)
                                        {{ $log->user->name }}
                                        <br><small class="text-muted">{{ $log->ip_address }}</small>
                                    @else
                                        System
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-secondary">{{ $log->action }}</span>
                                </td>
                                <td>{{ $log->module }}</td>
                                <td>{{ $log->description }}</td>
                                <td>
                                    @if($log->properties && $log->properties != '[]')
                                        <button class="btn btn-xs btn-info" type="button" data-toggle="collapse" data-target="#log_{{ $log->id }}">
                                            View
                                        </button>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @if($log->properties && $log->properties != '[]')
                            <tr class="collapse" id="log_{{ $log->id }}">
                                <td colspan="6">
                                    <pre style="max-height: 200px;">{{ json_encode(json_decode($log->properties), JSON_PRETTY_PRINT) }}</pre>
                                </td>
                            </tr>
                            @endif
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No logs found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
