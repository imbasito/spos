@extends('backend.master')

@section('title', 'Purchase #' . $id . ' — Items')

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">

      {{-- Header --}}
      <div class="card-header bg-gradient-maroon py-3 d-flex align-items-center">
        <h5 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-shopping-bag mr-2"></i> Purchase #{{ $id }} — Items
        </h5>
        <a href="{{ route('backend.admin.purchase.index') }}" class="btn btn-light btn-sm ml-auto font-weight-bold text-maroon">
          <i class="fas fa-arrow-left mr-1"></i> Back to Purchases
        </a>
      </div>

      <div class="card-body p-4">

        {{-- Purchase Context Strip --}}
        <div class="row mb-4">
          <div class="col-md-3">
            <div class="p-3 border-radius-15 bg-light text-center">
              <p class="text-muted small mb-1 font-weight-bold text-uppercase">Supplier</p>
              <h6 class="font-weight-bold text-dark mb-0">{{ $purchase->supplier->name }}</h6>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border-radius-15 bg-light text-center">
              <p class="text-muted small mb-1 font-weight-bold text-uppercase">Purchase Date</p>
              <h6 class="font-weight-bold text-dark mb-0">{{ \Carbon\Carbon::parse($purchase->date)->format('d M, Y') }}</h6>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border-radius-15 bg-light text-center">
              <p class="text-muted small mb-1 font-weight-bold text-uppercase">Grand Total</p>
              <h6 class="font-weight-bold text-dark mb-0">{{ currency()->symbol }} {{ number_format($purchase->grand_total, 2) }}</h6>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border-radius-15 text-center" style="background: {{ $purchase->due_amount > 0 ? '#fff0f0' : '#eafaf1' }};">
              <p class="text-muted small mb-1 font-weight-bold text-uppercase">Payment Status</p>
              <h6 class="font-weight-bold mb-0 {{ $purchase->due_amount > 0 ? 'text-danger' : 'text-success' }}">
                {{ ucfirst($purchase->payment_status) }}
                @if($purchase->due_amount > 0)
                  <small class="d-block font-weight-normal">Due: {{ currency()->symbol }} {{ number_format($purchase->due_amount, 2) }}</small>
                @endif
              </h6>
            </div>
          </div>
        </div>

        {{-- Items Table --}}
        <div class="table-responsive">
          <table class="table table-hover mb-0 custom-premium-table">
            <thead>
              <tr>
                <th class="pl-4 text-white" style="background-color: #4E342E !important;">#</th>
                <th class="text-white" style="background-color: #4E342E !important;">Product</th>
                <th class="text-white" style="background-color: #4E342E !important;">Purchase Price</th>
                <th class="text-white" style="background-color: #4E342E !important;">Quantity</th>
                <th class="text-right pr-4 text-white" style="background-color: #4E342E !important;">Sub Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach($purchase->items as $key => $item)
              <tr>
                <td class="pl-4">{{ $key + 1 }}</td>
                <td class="font-weight-bold">{{ $item->product->name }}</td>
                <td>{{ currency()->symbol }} {{ number_format($item->purchase_price, 2) }}</td>
                <td>{{ $item->quantity }} {{ optional($item->product->unit)->short_name }}</td>
                <td class="text-right pr-4 font-weight-bold">
                  {{ currency()->symbol }} {{ number_format($item->purchase_price * $item->quantity, 2) }}
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Totals Summary --}}
        <div class="row mt-4">
          <div class="col-md-5 ml-auto">
            <table class="table table-sm mb-0">
              <tr><th width="50%">Sub Total:</th><td class="text-right">{{ currency()->symbol }} {{ number_format($purchase->sub_total, 2) }}</td></tr>
              @if($purchase->tax > 0)
              <tr><th>Tax:</th><td class="text-right">{{ currency()->symbol }} {{ number_format($purchase->tax, 2) }}</td></tr>
              @endif
              @if($purchase->discount_value > 0)
              <tr><th>Discount:</th><td class="text-right text-danger">- {{ currency()->symbol }} {{ number_format($purchase->discount_value, 2) }}</td></tr>
              @endif
              @if($purchase->shipping > 0)
              <tr><th>Shipping:</th><td class="text-right">{{ currency()->symbol }} {{ number_format($purchase->shipping, 2) }}</td></tr>
              @endif
              <tr class="border-top">
                <th class="font-weight-bold text-maroon">Grand Total:</th>
                <td class="text-right font-weight-bold text-maroon">{{ currency()->symbol }} {{ number_format($purchase->grand_total, 2) }}</td>
              </tr>
              <tr>
                <th>Paid Amount:</th>
                <td class="text-right text-success font-weight-bold">{{ currency()->symbol }} {{ number_format($purchase->paid_amount, 2) }}</td>
              </tr>
              @if($purchase->due_amount > 0)
              <tr>
                <th class="text-danger">Remaining Due:</th>
                <td class="text-right text-danger font-weight-bold">{{ currency()->symbol }} {{ number_format($purchase->due_amount, 2) }}</td>
              </tr>
              @endif
            </table>
          </div>
        </div>

        @if($purchase->note)
        <div class="mt-3 p-3 bg-light border-radius-10">
          <p class="text-muted small font-weight-bold mb-1 text-uppercase">Note</p>
          <p class="mb-0">{{ $purchase->note }}</p>
        </div>
        @endif

      </div>
    </div>
  </div>
</div>

<style>
  .bg-gradient-maroon { background: linear-gradient(45deg, #800000, #A01010) !important; }
  .text-maroon { color: #800000 !important; }
  .custom-premium-table thead th { border: none; letter-spacing: 0.05em; padding-top: 14px; padding-bottom: 14px; }
  .custom-premium-table tbody td { vertical-align: middle; padding: 0.85rem 0.75rem; border-bottom: 1px solid #edf2f9; color: #2d3748; }
  .custom-premium-table tr:last-child td { border-bottom: none; }
  .custom-premium-table tbody tr:hover { background: #f8fafc; }
</style>
@endsection