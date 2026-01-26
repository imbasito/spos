@extends('backend.master')

@section('title', 'Closing History')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Register Closing History</h3>
                <div class="card-tools">
                    <a href="{{ route('backend.admin.report.daily.closing') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Close Today
                    </a>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Date Time</th>
                                <th>User</th>
                                <th class="text-right">Total Sales</th>
                                <th class="text-right">Returns</th>
                                <th class="text-right">System Cash</th>
                                <th class="text-right">In Hand</th>
                                <th class="text-right">Difference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($closings as $close)
                            <tr>
                                <td>{{ date('d M Y, h:i A', strtotime($close->closed_at)) }}</td>
                                <td>{{ optional(\App\Models\User::find($close->user_id))->name ?? 'Unknown' }}</td>
                                <td class="text-right">{{ number_format($close->total_sales, 2) }}</td>
                                <td class="text-right text-danger">{{ number_format($close->total_returns, 2) }}</td>
                                <td class="text-right font-weight-bold">{{ number_format($close->system_cash, 2) }}</td>
                                <td class="text-right">{{ number_format($close->cash_in_hand, 2) }}</td>
                                <td class="text-right">
                                    @if($close->difference == 0)
                                        <span class="badge badge-success">Balanced</span>
                                    @elseif($close->difference > 0)
                                        <span class="text-success">+{{ number_format($close->difference, 2) }}</span>
                                    @else
                                        <span class="text-danger">{{ number_format($close->difference, 2) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No reports found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer">
                {{ $closings->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
