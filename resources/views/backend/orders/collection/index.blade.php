@extends('backend.master')

@section('title', 'Transactions — Sale #' . $order->id)

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden" style="min-height: 70vh;">

      {{-- Header --}}
      <div class="card-header bg-gradient-maroon py-3 d-flex align-items-center">
        <h5 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-exchange-alt mr-2"></i> Transactions — Sale #{{ $order->id }}
        </h5>
        <a href="{{ route('backend.admin.orders.index') }}" class="btn btn-light btn-sm ml-auto font-weight-bold text-maroon">
          <i class="fas fa-arrow-left mr-1"></i> Back to Sales
        </a>
      </div>

      <div class="card-body p-4">

        {{-- Order Context Strip --}}
        <div class="row mb-4">
          <div class="col-md-3">
            <div class="p-3 border-radius-15 bg-light text-center">
              <p class="text-muted small mb-1 font-weight-bold text-uppercase">Customer</p>
              <h6 class="font-weight-bold text-dark mb-0">{{ $order->customer->name ?? '—' }}</h6>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border-radius-15 bg-light text-center">
              <p class="text-muted small mb-1 font-weight-bold text-uppercase">Order Total</p>
              <h6 class="font-weight-bold text-dark mb-0">{{ currency()->symbol }} {{ number_format($order->total, 2) }}</h6>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border-radius-15 bg-light text-center">
              <p class="text-muted small mb-1 font-weight-bold text-uppercase">Total Paid</p>
              <h6 class="font-weight-bold text-success mb-0">{{ currency()->symbol }} {{ number_format($order->paid, 2) }}</h6>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border-radius-15 text-center {{ $order->due > 0 ? '' : '' }}" style="background: {{ $order->due > 0 ? '#fff0f0' : '#eafaf1' }};">
              <p class="text-muted small mb-1 font-weight-bold text-uppercase">Remaining Due</p>
              <h6 class="font-weight-bold mb-0 {{ $order->due > 0 ? 'text-danger' : 'text-success' }}">
                {{ $order->due > 0 ? currency()->symbol . ' ' . number_format($order->due, 2) : 'Fully Paid' }}
              </h6>
            </div>
          </div>
        </div>

        {{-- Transactions Table --}}
        <div class="table-responsive">
          <table class="table table-hover mb-0 custom-premium-table">
            <thead>
              <tr>
                <th class="pl-4 text-white" style="background-color: #4E342E !important;">#</th>
                <th class="text-white" style="background-color: #4E342E !important;">Transaction ID</th>
                <th class="text-white" style="background-color: #4E342E !important;">Amount</th>
                <th class="text-white" style="background-color: #4E342E !important;">Payment Method</th>
                <th class="text-white" style="background-color: #4E342E !important;">Date</th>
                <th class="text-right pr-4 text-white" style="background-color: #4E342E !important;">Invoice</th>
              </tr>
            </thead>
            <tbody>
              @forelse($order->transactions as $index => $transaction)
              <tr>
                <td class="pl-4">{{ $index + 1 }}</td>
                <td class="font-weight-bold">#{{ $transaction->id }}</td>
                <td class="font-weight-bold text-success">{{ currency()->symbol }} {{ number_format($transaction->amount, 2, '.', ',') }}</td>
                <td>
                  <span class="badge px-3 py-1" style="border-radius: 20px; background: #e2d9f3; color: #4a235a; font-size: .8rem;">
                    {{ ucfirst($transaction->paid_by ?? 'Cash') }}
                  </span>
                </td>
                <td class="text-muted">{{ $transaction->created_at->format('d M Y, h:i A') }}</td>
                <td class="text-right pr-4">
                  <a class="btn btn-light btn-sm font-weight-bold text-maroon shadow-sm"
                     href="{{ route('backend.admin.collectionInvoice', $transaction->id) }}"
                     style="border-radius: 8px;">
                    <i class="fas fa-file-invoice mr-1"></i> View
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="6" class="text-center py-5">
                  <i class="fas fa-exchange-alt fa-3x text-light mb-3 d-block"></i>
                  <p class="text-muted mb-0">No transactions recorded for this order.</p>
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .bg-gradient-maroon { background: linear-gradient(45deg, #800000, #A01010) !important; }
  .custom-premium-table thead th { border: none; letter-spacing: 0.05em; padding-top: 14px; padding-bottom: 14px; }
  .custom-premium-table tbody td { vertical-align: middle; padding: 0.85rem 0.75rem; border-bottom: 1px solid #edf2f9; color: #2d3748; }
  .custom-premium-table tr:last-child td { border-bottom: none; }
  .custom-premium-table tbody tr:hover { background: #f8fafc; }
</style>
@endsection