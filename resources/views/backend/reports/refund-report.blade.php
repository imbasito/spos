@extends('backend.master')

@section('title', 'Refund Report')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-undo"></i> Refund Report</h3>
        <span class="badge bg-secondary">{{ $start_date }} - {{ $end_date }}</span>
    </div>
    <div class="card-body">
        <!-- Date Filter Form -->
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->subDays(29)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ number_format($total_refund, 2) }}</h3>
                        <p>Total Refunded</p>
                    </div>
                    <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $total_count }}</h3>
                        <p>Total Returns</p>
                    </div>
                    <div class="icon"><i class="fas fa-undo"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $total_count > 0 ? number_format($total_refund / $total_count, 2) : '0.00' }}</h3>
                        <p>Average Refund</p>
                    </div>
                    <div class="icon"><i class="fas fa-calculator"></i></div>
                </div>
            </div>
        </div>

        <!-- Refunds Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Return #</th>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Refund Amount</th>
                        <th>Processed By</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($refunds as $index => $refund)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $refund->return_number }}</strong></td>
                        <td>#{{ $refund->order_id }}</td>
                        <td>{{ $refund->order->customer->name ?? 'N/A' }}</td>
                        <td class="text-danger">{{ number_format($refund->total_refund, 2) }}</td>
                        <td>{{ $refund->processedBy->name ?? 'N/A' }}</td>
                        <td>{{ $refund->created_at->format('d M, Y h:i A') }}</td>
                        <td>
                            <a href="{{ route('backend.admin.refunds.receipt', $refund->id) }}" class="btn btn-sm btn-info" target="_blank">
                                <i class="fas fa-receipt"></i> Receipt
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No refunds found in this period</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($refunds->count() > 0)
                <tfoot class="bg-light">
                    <tr>
                        <td colspan="4" class="text-right"><strong>Total:</strong></td>
                        <td class="text-danger"><strong>{{ number_format($total_refund, 2) }}</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
