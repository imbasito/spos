@extends('backend.master')

@section('title', 'Closing History')

@section('content')
<div class="row animate__animated animate__fadeIn">
    <div class="col-12">
        <!-- Summary Stats Strip -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 border-radius-15 bg-white mb-0">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="rounded-circle bg-light-maroon p-3 mr-3"><i class="fas fa-shopping-basket text-maroon"></i></div>
                        <div>
                            <p class="text-muted small mb-0">Current Orders</p>
                            <h4 class="font-weight-bold mb-0 text-maroon">{{ $totalOrders }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 border-radius-15 bg-white mb-0">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="rounded-circle bg-light-green p-3 mr-3"><i class="fas fa-money-bill-wave text-success"></i></div>
                        <div>
                            <p class="text-muted small mb-0">Today's Sales</p>
                            <h4 class="font-weight-bold mb-0 text-success">{{ currency()->symbol }} {{ number_format($totalSales, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 border-radius-15 bg-white mb-0">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="rounded-circle bg-light-red p-3 mr-3"><i class="fas fa-undo text-danger"></i></div>
                        <div>
                            <p class="text-muted small mb-0">Returns</p>
                            <h4 class="font-weight-bold mb-0 text-danger">{{ currency()->symbol }} {{ number_format($totalReturns, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 text-right">
                <button type="button" class="btn btn-apple-primary btn-apple px-4 py-3 shadow-sm h-100 w-100" data-toggle="modal" data-target="#newClosingModal">
                    <i class="fas fa-cash-register mr-2"></i> Close Register Now
                </button>
            </div>
        </div>

        <!-- History Table -->
        <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-history mr-2 text-maroon"></i> Register Closing History
                </h3>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 custom-premium-table">
                        <thead class="bg-light text-uppercase font-weight-bold small">
                            <tr>
                                <th class="pl-4">Date Time</th>
                                <th>Closed By</th>
                                <th class="text-right">Sales</th>
                                <th class="text-right">Returns</th>
                                <th class="text-right">System Cash</th>
                                <th class="text-right">In Hand</th>
                                <th class="text-right pr-4">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($closings as $close)
                            <tr>
                                <td class="pl-4 font-weight-bold">{{ date('d M Y, h:i A', strtotime($close->closed_at)) }}</td>
                                <td>{{ optional($close->user)->name ?? 'System' }}</td>
                                <td class="text-right font-weight-medium">{{ number_format($close->total_sales, 2) }}</td>
                                <td class="text-right text-danger font-weight-medium">{{ number_format($close->total_returns, 2) }}</td>
                                <td class="text-right font-weight-bold text-dark">{{ number_format($close->system_cash, 2) }}</td>
                                <td class="text-right font-weight-bold text-success">{{ number_format($close->cash_in_hand, 2) }}</td>
                                <td class="text-right pr-4">
                                    @if($close->difference == 0)
                                        <span class="badge bg-success-light text-success px-3 py-1" style="border-radius: 20px;">Balanced</span>
                                    @elseif($close->difference > 0)
                                        <span class="badge bg-success-light text-success px-3 py-1" style="border-radius: 20px;">+{{ number_format($close->difference, 2) }} Surplus</span>
                                    @else
                                        <span class="badge bg-danger-light text-danger px-3 py-1" style="border-radius: 20px;">{{ number_format($close->difference, 2) }} Short</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-ghost fa-3x text-light mb-3"></i>
                                    <p class="text-muted">No closing history found.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-center mt-2">
                    {{ $closings->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- NEW CLOSING MODAL -->
<div class="modal fade animate__animated animate__fadeInUp" id="newClosingModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 border-radius-20 overflow-hidden shadow-2xl">
            <div class="modal-header bg-maroon text-white border-0 py-4">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-cash-register mr-3"></i> Finalize Register Shift</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('backend.admin.report.daily.closing.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4 bg-light">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="alert bg-white shadow-sm border-0 mb-4 p-3 border-radius-15">
                                <h6 class="text-muted small text-uppercase font-weight-bold mb-3">Expected Calculation</h6>
                                <div class="d-flex justify-content-between mb-2 small">
                                    <span>Session Sales:</span>
                                    <span class="font-weight-bold">+{{ number_format($totalSales, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 small">
                                    <span>Refunds/Returns:</span>
                                    <span class="font-weight-bold text-danger">-{{ number_format($totalReturns, 2) }}</span>
                                </div>
                                <div class="border-top pt-2 d-flex justify-content-between">
                                    <span class="font-weight-bold">System Balance:</span>
                                    <span class="font-weight-bold h5 mb-0 text-maroon" id="modal_system_cash_val">{{ number_format($systemCash, 2, '.', '') }}</span>
                                </div>
                            </div>
                            
                            <div class="callout callout-maroon bg-white border-radius-15 shadow-sm small">
                                <p class="mb-0"><i class="fas fa-info-circle text-maroon mr-2"></i> Please provide the exact cash amount found in the drawer. The system will record any discrepancies automatically.</p>
                            </div>
                        </div>
                        
                        <div class="col-md-7">
                            <div class="form-group mb-4">
                                <label class="h6 font-weight-bold mb-2">💵 Physical Cash Count</label>
                                <div class="input-group input-group-lg">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0 text-success font-weight-bold">{{ currency()->symbol }}</span>
                                    </div>
                                    <input type="number" step="0.01" name="cash_in_hand" id="modal_cash_in_hand" class="form-control border-left-0 font-weight-bold text-success h-auto py-3" style="font-size: 2rem;" placeholder="0.00" required autofocus oninput="calculateModalDifference()">
                                </div>
                            </div>
                            
                            <div class="p-3 border-radius-15 shadow-inner" id="modal_diff_bg" style="background: rgba(0,0,0,0.03); transition: all 0.3s ease;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted font-weight-medium">Difference Calculation:</span>
                                    <span id="modal_diff_display" class="font-weight-bold h3 mb-0">0.00</span>
                                </div>
                                <p id="modal_diff_label" class="mb-0 small text-center font-weight-bold">Balanced</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0 p-4">
                    <button type="button" class="btn btn-secondary btn-apple px-4" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon btn-apple px-4 shadow-sm" onclick="return confirm('Complete register closing and log out session?')">
                        <i class="fas fa-check-circle mr-2"></i> Confirm Closing
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-light-maroon { background: rgba(128, 0, 0, 0.05); }
    .bg-light-green { background: rgba(40, 167, 69, 0.05); }
    .bg-light-red { background: rgba(220, 53, 69, 0.05); }
    .text-maroon { color: #800000 !important; }
    .bg-maroon { background-color: #800000 !important; }
    .bg-success-light { background-color: rgba(40, 167, 69, 0.1); }
    .bg-danger-light { background-color: rgba(220, 53, 69, 0.1); }
    .shadow-2xl { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
    .border-radius-20 { border-radius: 20px; }
</style>

@push('script')
<script>
    function calculateModalDifference() {
        let system = parseFloat($('#modal_system_cash_val').text());
        let actual = parseFloat($('#modal_cash_in_hand').val()) || 0;
        let diff = actual - system;
        
        let display = $('#modal_diff_display');
        let label = $('#modal_diff_label');
        let bg = $('#modal_diff_bg');
        
        display.text(diff.toFixed(2));
        
        if (diff < 0) {
            display.attr('class', 'font-weight-bold h3 mb-0 text-danger');
            label.text("SHORTAGE DETECTED").attr('class', 'mb-0 small text-center text-danger font-weight-bold');
            bg.css('background', 'rgba(220, 53, 69, 0.05)');
        } else if (diff > 0) {
            display.attr('class', 'font-weight-bold h3 mb-0 text-success');
            label.text("SURPLUS DETECTED").attr('class', 'mb-0 small text-center text-success font-weight-bold');
            bg.css('background', 'rgba(40, 167, 69, 0.05)');
        } else {
            display.attr('class', 'font-weight-bold h3 mb-0 text-dark');
            label.text("BALANCED").attr('class', 'mb-0 small text-center text-muted font-weight-bold');
            bg.css('background', 'rgba(0,0,0,0.03)');
        }
    }
</script>
@endpush
@endsection
