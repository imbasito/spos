@extends('backend.master')

@section('title', 'Dashboard')

@section('content')
<section class="content animate__animated animate__fadeIn">
    @can('dashboard_view')
    <div class="container-fluid">
        
        <!-- Header: Apple-style greeting and date -->
        <div class="d-flex justify-content-between align-items-end mb-4 pt-3">
            <div>
                <h1 class="font-weight-bold apple-h1 mb-1">Dashboard</h1>
                <p class="text-apple-sub m-0">Performance insights for {{ now()->format('F d, Y') }}</p>
            </div>

            <div class="input-group w-auto bg-white rounded-pill px-3 shadow-soft border align-items-center" style="height: 38px; border: 1px solid rgba(0,0,0,0.05) !important;">
                <i class="far fa-calendar-alt text-muted mr-2" style="font-size: 0.8rem;"></i>
                <input type="text" class="form-control border-0 bg-transparent p-0 font-weight-500" id="reservation" style="width: 170px; font-size: 0.8rem; color: #444;" placeholder="Filter dates...">
            </div>

        </div>

        <!-- Dashboard Stats: Precision Tiles -->
        <div class="row mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card shadow-soft apple-card-refinement h-100 border-0">
                    <div class="card-body p-4 text-center">
                        <small class="text-uppercase text-apple-tiny font-weight-bold mb-2 d-block">Total Revenue</small>
                        <h2 class="font-weight-bold apple-h2 m-0 text-dark">
                            <small class="text-muted" style="font-size: 0.6em; vertical-align: middle;">{{currency()->symbol??''}}</small> {{number_format($total,0)}}
                        </h2>
                        <div class="mt-2 text-success small font-weight-bold">
                            <i class="fas fa-arrow-up mr-1"></i> Global Scale Active
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card shadow-soft apple-card-refinement h-100 border-0">
                    <div class="card-body p-4 text-center">
                        <small class="text-uppercase text-apple-tiny font-weight-bold mb-2 d-block">Net Profit</small>
                        <h2 class="font-weight-bold apple-h2 m-0 text-maroon">
                            <small class="text-muted" style="font-size: 0.6em; vertical-align: middle;">{{currency()->symbol??''}}</small> {{number_format($total_profit,0)}}
                        </h2>
                        <span class="badge badge-pill bg-light text-maroon mt-2 px-3 border">Performance Index</span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="card shadow-soft apple-card-refinement h-100 border-0">
                    <div class="card-body p-4 text-center">
                        <small class="text-uppercase text-apple-tiny font-weight-bold mb-2 d-block">Total Sales</small>
                        <h2 class="font-weight-bold apple-h2 m-0 text-dark">{{number_format($total_order)}}</h2>
                        <div class="mt-2 text-apple-sub small">Completed Invoices</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <div class="card shadow-soft apple-card-refinement h-100 border-0">
                    <div class="card-body p-4 text-center">
                        <small class="text-uppercase text-apple-tiny font-weight-bold mb-2 d-block">Customer Base</small>
                        <h2 class="font-weight-bold apple-h2 m-0 text-dark">{{$total_customer}}</h2>
                        <a href="{{route('backend.admin.customers.index')}}" class="mt-2 text-primary small font-weight-bold d-block">View Details <i class="fas fa-chevron-right ml-1" style="font-size: 0.7em;"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-soft apple-card-refinement border-0 h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="font-weight-bold apple-h3 mb-0">Sales Analytics</h5>
                        <p class="text-apple-sub small">Transaction volume and trends over time</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div id="salesChart" style="min-height: 350px;"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-soft apple-card-refinement border-0 h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="font-weight-bold apple-h3 mb-0 text-maroon">Inventory Alert</h5>
                        <p class="text-apple-sub small">Products nearing zero stock</p>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <tbody>
                                    @forelse($low_stock_products->take(5) as $lsp)
                                    <tr>
                                        <td class="pl-4 py-3">
                                            <div class="font-weight-bold text-dark">{{ $lsp->name }}</div>
                                            <code class="small text-muted">{{ $lsp->sku }}</code>
                                        </td>
                                        <td class="text-right pr-4 py-3">
                                            <span class="badge badge-pill {{ $lsp->quantity <= 5 ? 'bg-danger' : 'bg-warning' }} px-3">
                                                {{ $lsp->quantity }} Left
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-5 text-apple-sub italic">Stock levels are healthy.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($low_stock_products->count() > 5)
                    <div class="card-footer bg-transparent border-0 text-center pb-4">
                        <a href="{{ route('backend.admin.products.index') }}" class="text-maroon font-weight-bold small">Restock Remaining ({{$low_stock_products->count() - 5}}) <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Secondary Charts & Top Products -->
        <div class="row mt-4 mb-5">
            <div class="col-lg-4">
                 <div class="card shadow-soft apple-card-refinement border-0 h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="font-weight-bold apple-h3 mb-0">Top Categories</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach($top_products->take(4) as $tp)
                                <div class="list-group-item bg-transparent border-0 d-flex align-items-center px-4 py-3">
                                    <div class="bg-light rounded mr-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border: var(--apple-border)">
                                        <i class="fas fa-box text-apple-sub"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="font-weight-bold text-dark small text-truncate" style="max-width: 150px;">{{ $tp->name }}</div>
                                        <div class="text-apple-sub x-small">{{ $tp->sold_qty ?? 0 }} Sold</div>
                                    </div>
                                    <div class="text-right font-weight-bold text-success">{{currency()->symbol??''}}{{ number_format($tp->discounted_price, 0) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card shadow-soft apple-card-refinement border-0 h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="font-weight-bold apple-h3 mb-0">Monthly Revenue</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div id="yearlyChart" style="min-height: 250px;"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    @endcan
</section>

@endsection

@push('style')
<style>
    .info-box { border-radius: 15px; border: none; }
    .small-box { border-radius: 15px; }
    .bg-gradient-navy { background: linear-gradient(135deg, #001f3f, #004080); color: white; }
    .bg-gradient-maroon { background: linear-gradient(135deg, #800000, #b30000); color: white; }
    .bg-gradient-success { background: linear-gradient(135deg, #28a745, #4cd137); color: white; }
    .bg-gradient-warning { background: linear-gradient(135deg, #f39c12, #f1c40f); color: white; }
</style>
@endpush

@push('script')
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // Sales performance chart (Interactive Line/Area)
    var salesOptions = {
        series: [{
            name: 'Total Sales',
            data: @json($totalAmounts)
        }],
        chart: {
            height: 350,
            type: 'area',
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: 'inherit'
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#007bff'],
        xaxis: {
            categories: @json($dates),
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                formatter: function (value) { return "Rs." + value; }
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.3,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        grid: {
            borderColor: '#f1f1f1',
            strokeDashArray: 4
        }
    };

    var salesChart = new ApexCharts(document.querySelector("#salesChart"), salesOptions);
    salesChart.render();

    // Yearly Monthly Growth Chart (Bar)
    var yearlyOptions = {
        series: [{
            name: 'Monthly Revenue',
            data: @json($totalAmountMonth)
        }],
        chart: {
            type: 'bar',
            height: 250,
            toolbar: { show: false },
            fontFamily: 'inherit'
        },
        colors: ['#28a745'],
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: false,
                columnWidth: '55%',
            }
        },
        dataLabels: { enabled: false },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        grid: {
            borderColor: '#f1f1f1',
            strokeDashArray: 4
        }
    };

    var yearlyChart = new ApexCharts(document.querySelector("#yearlyChart"), yearlyOptions);
    yearlyChart.render();

    $(function() {
        //Date range picker
        $('#reservation').daterangepicker().on('apply.daterangepicker', function(e, picker) {
            let selectedDateRange = picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD');
            let url = new URL(window.location.href);
            url.searchParams.set('daterange', selectedDateRange);
            window.location.href = url.toString();
        });
    });
</script>
@endpush