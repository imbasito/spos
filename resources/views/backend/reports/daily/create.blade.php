@extends('backend.master')

@section('title', 'Daily Closing (Z-Report)')

@section('content')
<div class="row animate__animated animate__fadeIn justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-gradient-maroon text-white">
                <h3 class="card-title font-weight-bold"><i class="fas fa-cash-register mr-2"></i> Close Register</h3>
                <div class="card-tools">
                    <span class="badge badge-light p-2">{{ date('l, d F Y') }}</span>
                </div>
            </div>
            
            <form action="{{ route('backend.admin.report.daily.closing.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-shopping-cart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Orders</span>
                                    <span class="info-box-number">{{ $totalOrders }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-money-bill-wave"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Sales</span>
                                    <span class="info-box-number text-success">{{ currency()->symbol }} {{ number_format($totalSales, 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-undo"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Returns</span>
                                    <span class="info-box-number text-danger">{{ currency()->symbol }} {{ number_format($totalReturns, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Helper Section -->
                        <div class="col-md-6">
                            <div class="callout callout-info">
                                <h5><i class="fas fa-info-circle"></i> Instructions</h5>
                                <ul class="pl-3">
                                    <li>Count all cash in the drawer.</li>
                                    <li>Enter the exact amount in the "Cash In Hand" box.</li>
                                    <li>The system will calculate overage/shortage.</li>
                                    <li>Once closed, you cannot edit this report.</li>
                                </ul>
                            </div>
                            <div class="alert alert-secondary text-center">
                                <h6 class="mb-1">Expected System Cash</h6>
                                <h2 class="font-weight-bold display-4 mb-0">{{ currency()->symbol }} <span id="system_cash">{{ number_format($systemCash, 2, '.', '') }}</span></h2>
                            </div>
                        </div>

                        <!-- Input Section -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cash_in_hand" class="h5">💵 Cash In Hand (Physical Count)</label>
                                <div class="input-group input-group-lg">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">{{ currency()->symbol }}</span>
                                    </div>
                                    <input type="number" step="0.01" name="cash_in_hand" id="cash_in_hand" class="form-control font-weight-bold text-success" style="font-size: 2rem; height: 60px;" placeholder="0.00" required autofocus oninput="calculateDifference()">
                                </div>
                            </div>

                            <div class="card bg-dark text-white p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Difference:</span>
                                    <span id="diff_display" class="font-weight-bold h3 mb-0">0.00</span>
                                </div>
                                <small id="diff_label" class="text-white-50">Balanced</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-lg btn-maroon font-weight-bold" onclick="return confirm('Are you sure you want to close the register?')">
                        <i class="fas fa-check-circle mr-2"></i> Submit Closing Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bg-gradient-maroon {
    background: linear-gradient(45deg, #800000, #A01010) !important;
}
.btn-maroon {
    background-color: #800000;
    color: white;
}
.btn-maroon:hover {
    background-color: #5d0000;
    color: white;
}
</style>

@push('script')
<script>
    function calculateDifference() {
        let system = parseFloat(document.getElementById('system_cash').innerText);
        let actual = parseFloat(document.getElementById('cash_in_hand').value) || 0;
        let diff = actual - system;
        
        let display = document.getElementById('diff_display');
        let label = document.getElementById('diff_label');
        
        display.innerText = diff.toFixed(2);
        
        if (diff < 0) {
            display.className = "font-weight-bold h3 mb-0 text-danger";
            label.innerText = "Shortage (Missing Cash)";
            label.className = "text-danger";
        } else if (diff > 0) {
            display.className = "font-weight-bold h3 mb-0 text-success";
            label.innerText = "Overage (Extra Cash)";
            label.className = "text-success";
        } else {
            display.className = "font-weight-bold h3 mb-0 text-white";
            label.innerText = "Balanced (Perfect)";
            label.className = "text-white-50";
        }
    }
</script>
@endpush
@endsection
