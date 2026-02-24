@extends('backend.master')

@section('title', 'Refund Report')

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden" style="min-height: 70vh;">
      <div class="card-header bg-gradient-maroon py-3 d-flex align-items-center">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-undo mr-2"></i> Refund Report
        </h3>
        <div class="ml-auto d-flex align-items-center">
            <span class="badge bg-white text-maroon shadow-sm px-3 py-2 font-weight-bold mr-2">{{ $start_date }} - {{ $end_date }}</span>
            <button type="button" onclick="window.print()" class="btn btn-light btn-md px-4 shadow-sm hover-lift font-weight-bold text-maroon" style="border-radius: 10px;">
              <i class="fas fa-print mr-1"></i> Print Report
            </button>
        </div>
      </div>
      <div class="card-body p-4">
        <!-- Spotlight Search -->
        <div class="row mb-4">
          <div class="col-md-12">
            <div class="input-group shadow-sm spotlight-search-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white border-0 pl-3">
                  <i class="fas fa-search text-maroon"></i>
                </span>
              </div>
              <input type="text" id="refundSearchInput" class="form-control border-0 py-4 apple-input" placeholder="Search refunds by return number, order ID or customer..." autofocus style="font-size: 1rem; box-shadow: none;">
            </div>
          </div>
        </div>
        <!-- Date Filter Form -->
        <form method="GET" class="mb-4">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="font-weight-bold">Start Date</label>
                    <input type="date" name="start_date" class="form-control border-radius-10" value="{{ request('start_date', now()->subDays(29)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold">End Date</label>
                    <input type="date" name="end_date" class="form-control border-radius-10" value="{{ request('end_date', now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-maroon btn-block font-weight-bold shadow-sm" style="border-radius: 20px;">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                </div>
            </div>
        </form>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="small-box bg-white shadow-sm border-radius-15 p-3">
                    <div class="inner">
                        <h3 class="text-danger">{{ number_format($total_refund, 2) }}</h3>
                        <p class="text-muted font-weight-bold">Total Refunded</p>
                    </div>
                    <div class="icon text-danger-light"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-white shadow-sm border-radius-15 p-3">
                    <div class="inner">
                        <h3 class="text-warning">{{ $total_count }}</h3>
                        <p class="text-muted font-weight-bold">Total Returns</p>
                    </div>
                    <div class="icon text-warning-light"><i class="fas fa-undo"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-white shadow-sm border-radius-15 p-3">
                    <div class="inner">
                        <h3 class="text-info">{{ $total_count > 0 ? number_format($total_refund / $total_count, 2) : '0.00' }}</h3>
                        <p class="text-muted font-weight-bold">Average Refund</p>
                    </div>
                    <div class="icon text-info-light"><i class="fas fa-calculator"></i></div>
                </div>
            </div>
        </div>

        <!-- Refunds Table -->
        <div class="table-responsive">
            <table class="table table-hover mb-0 custom-premium-table">
                <thead class="bg-dark text-white text-uppercase font-weight-bold small">
                    <tr>
                        <th class="pl-4 text-white" style="color: #ffffff !important; background-color: #4E342E !important;">#</th>
                        <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Return #</th>
                        <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Order #</th>
                        <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Customer</th>
                        <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Refund Amount</th>
                        <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Processed By</th>
                        <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Date</th>
                        <th class="text-right pr-4 text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($refunds as $index => $refund)
                    <tr>
                        <td class="pl-4">{{ $index + 1 }}</td>
                        <td><strong>{{ $refund->return_number }}</strong></td>
                        <td>#{{ $refund->order_id }}</td>
                        <td>{{ $refund->order->customer->name ?? 'N/A' }}</td>
                        <td class="text-danger font-weight-bold">{{ number_format($refund->total_refund, 2) }}</td>
                        <td>{{ $refund->processedBy->name ?? 'N/A' }}</td>
                        <td>{{ $refund->created_at->format('d M, Y h:i A') }}</td>
                        <td class="text-right pr-4">
                            <a href="{{ route('backend.admin.refunds.receipt', $refund->id) }}" class="btn btn-sm btn-light text-info font-weight-bold shadow-sm border" target="_blank" style="border-radius: 20px;">
                                <i class="fas fa-receipt mr-1"></i> Receipt
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fas fa-box-open fa-3x mb-3 text-gray-300"></i><br>
                            No refunds found in this period
                        </td>
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
</div>
@push('script')
<script>
  $(function() {
    $('#refundSearchInput').on('keyup input', function() {
        var value = $(this).val().toLowerCase();
        $(".custom-premium-table tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
  });
</script>
@endpush
@endsection

<style>
  .custom-premium-table thead th {
    border: none;
    color: #ffffff !important;
    letter-spacing: 0.05em;
    padding-top: 15px;
    padding-bottom: 15px;
  }
  .custom-premium-table tbody td {
    vertical-align: middle;
    color: #2d3748;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #edf2f9;
  }
  .custom-premium-table tr:last-child td {
    border-bottom: none;
  }
  .custom-premium-table tbody tr:hover {
    background-color: #f8fafc;
  }
  .text-maroon {
    color: #800000 !important;
  }
  .bg-gradient-maroon {
    background: linear-gradient(45deg, #800000, #A01010) !important;
  }
  .btn-maroon {
    background-color: #800000;
    color: white;
  }
  .btn-maroon:hover {
    background-color: #600000;
    color: white;
  }
  .border-radius-10 { border-radius: 10px; }
  .border-radius-15 { border-radius: 15px; }
  .small-box { position: relative; display: block; overflow: hidden; }
  .small-box .icon { position: absolute; right: 10px; top: 10px; font-size: 60px; opacity: 0.2; }
  .text-danger-light { color: #dc3545; }
  .text-warning-light { color: #ffc107; }
  .text-info-light { color: #17a2b8; }
</style>
