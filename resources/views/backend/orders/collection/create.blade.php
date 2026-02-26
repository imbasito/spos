@extends('backend.master')

@section('title', 'Due Collection — Sale #' . $order->id)

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-md-8 mx-auto">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">

      {{-- Header --}}
      <div class="card-header bg-gradient-maroon py-3 d-flex align-items-center">
        <h5 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-receipt mr-2"></i> Due Collection — Sale #{{ $order->id }}
        </h5>
        <a href="{{ route('backend.admin.orders.index') }}" class="btn btn-light btn-sm ml-auto font-weight-bold text-maroon">
          <i class="fas fa-arrow-left mr-1"></i> Back to Sales
        </a>
      </div>

      {{-- Order Summary Strip --}}
      <div class="px-4 pt-4 pb-0">
        <div class="row text-center mb-3">
          <div class="col-md-3">
            <div class="p-3 border-radius-15 bg-light">
              <p class="text-muted small mb-1 font-weight-bold text-uppercase">Customer</p>
              <h6 class="font-weight-bold text-dark mb-0">{{ $order->customer->name ?? '—' }}</h6>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border-radius-15 bg-light">
              <p class="text-muted small mb-1 font-weight-bold text-uppercase">Order Total</p>
              <h6 class="font-weight-bold text-dark mb-0">{{ currency()->symbol }} {{ number_format($order->total, 2) }}</h6>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border-radius-15 bg-light">
              <p class="text-muted small mb-1 font-weight-bold text-uppercase">Already Paid</p>
              <h6 class="font-weight-bold text-success mb-0">{{ currency()->symbol }} {{ number_format($order->paid, 2) }}</h6>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border-radius-15" style="background: #fff0f0;">
              <p class="text-muted small mb-1 font-weight-bold text-uppercase">Remaining Due</p>
              <h6 class="font-weight-bold mb-0 text-danger">{{ currency()->symbol }} {{ number_format($order->due, 2) }}</h6>
            </div>
          </div>
        </div>
      </div>

      {{-- Collection Form --}}
      <form action="{{ route('backend.admin.due.collection', $order->id) }}" method="post">
        @csrf
        <div class="card-body px-4 pb-0 pt-3">

          @if($errors->any())
            <div class="alert alert-danger border-0 border-radius-10">
              @foreach($errors->all() as $error)<p class="mb-0">{{ $error }}</p>@endforeach
            </div>
          @endif

          <div class="row">
            {{-- Amount --}}
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-bold">Collection Amount <span class="text-danger">*</span></label>
                <div class="input-group input-group-lg">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-white font-weight-bold text-success border-right-0" style="border-radius: 10px 0 0 10px;">{{ currency()->symbol }}</span>
                  </div>
                  <input type="number" step="0.01" name="amount" id="collectionAmount"
                         class="form-control border-left-0 font-weight-bold apple-input"
                         placeholder="0.00"
                         value="{{ old('amount', $order->due) }}"
                         min="0.01" max="{{ $order->due }}" required
                         oninput="calcRemaining()" style="border-radius: 0 10px 10px 0; font-size: 1.4rem;">
                </div>
                <small class="text-muted">Maximum collectible: {{ currency()->symbol }} {{ number_format($order->due, 2) }}</small>
              </div>
            </div>

            {{-- Payment Method --}}
            <div class="col-md-6">
              <div class="form-group">
                <label class="font-weight-bold">Payment Method</label>
                <div class="d-flex gap-2 mt-1" style="gap: 0.5rem;">
                  @foreach(['cash' => 'Cash', 'card' => 'Card', 'online' => 'Online'] as $val => $label)
                  <label class="flex-fill text-center border rounded p-2 payment-method-opt" style="cursor:pointer; border-radius: 10px !important;"
                         data-val="{{ $val }}">
                    <input type="radio" name="payment_method" value="{{ $val }}" class="d-none" {{ $val === 'cash' ? 'checked' : '' }}>
                    <span class="font-weight-bold">{{ $label }}</span>
                  </label>
                  @endforeach
                </div>
              </div>
            </div>
          </div>

          {{-- Live Balance Display --}}
          <div class="p-3 border-radius-15 mb-3" id="balancePanel"
               style="background: rgba(0,0,0,0.03); transition: background 0.3s ease;">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted font-weight-bold">Remaining After Collection:</span>
              <span id="remainingDisplay" class="h4 font-weight-bold mb-0 text-dark">
                {{ currency()->symbol }} {{ number_format($order->due, 2) }}
              </span>
            </div>
          </div>

        </div>
        <div class="card-footer bg-white border-top px-4 py-3 d-flex justify-content-between align-items-center">
          <a href="{{ route('backend.admin.orders.index') }}" class="btn btn-secondary font-weight-bold" style="border-radius: 8px;">Cancel</a>
          <button type="submit" class="btn bg-gradient-primary font-weight-bold" style="border-radius: 8px; color: white; padding: 10px 30px;">
            <i class="fas fa-check-circle mr-2"></i> Confirm Collection
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  .payment-method-opt { border-color: #dee2e6 !important; }
  .payment-method-opt.selected { background: #800000; color: white; border-color: #800000 !important; }
  .bg-gradient-maroon { background: linear-gradient(45deg, #800000, #A01010) !important; }
</style>

@push('script')
<script>
  const orderDue = {{ $order->due }};
  const currencySymbol = "{{ currency()->symbol }}";

  function calcRemaining() {
    const amount = parseFloat(document.getElementById('collectionAmount').value) || 0;
    const remaining = Math.max(0, orderDue - amount);
    const display = document.getElementById('remainingDisplay');
    const panel = document.getElementById('balancePanel');

    display.textContent = currencySymbol + ' ' + remaining.toFixed(2);

    if (remaining <= 0) {
      display.style.color = '#27ae60';
      panel.style.background = 'rgba(39,174,96,0.07)';
    } else if (amount > 0) {
      display.style.color = '#e67e22';
      panel.style.background = 'rgba(230,126,34,0.07)';
    } else {
      display.style.color = '#2d3748';
      panel.style.background = 'rgba(0,0,0,0.03)';
    }
  }

  // Payment method selector
  document.querySelectorAll('.payment-method-opt').forEach(el => {
    el.addEventListener('click', function () {
      document.querySelectorAll('.payment-method-opt').forEach(e => e.classList.remove('selected'));
      this.classList.add('selected');
      this.querySelector('input[type=radio]').checked = true;
    });
    if (el.querySelector('input[type=radio]:checked')) el.classList.add('selected');
  });
</script>
@endpush
@endsection