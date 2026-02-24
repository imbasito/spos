@extends('backend.master')

@section('title', 'Sale Report')

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden" style="min-height: 70vh;">
      <div class="card-header bg-gradient-maroon py-3 d-flex align-items-center">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-chart-pie mr-2"></i> Sale Summary
        </h3>
        <div class="ml-auto d-flex align-items-center">
            <button type="button" class="btn btn-light btn-md px-4 shadow-sm hover-lift font-weight-bold text-maroon mr-2" id="daterange-btn" style="border-radius: 10px;">
              <i class="far fa-calendar-alt mr-2"></i> <span>Filter by date</span>
              <i class="fas fa-caret-down ml-2"></i>
            </button>
            <button type="button" onclick="window.print()" class="btn btn-light btn-md px-4 shadow-sm hover-lift font-weight-bold text-maroon" style="border-radius: 10px;">
              <i class="fas fa-print mr-1"></i> Print
            </button>
        </div>
      </div>
      <div class="card-body p-4">
    <div class="row g-4">
      <div class="col-md-12">
        <div class="card-body p-0">
          <section class="invoice" style="border: none;">
            <!-- Table row -->
            <div class="row justify-content-center">
              <div class="col-10">
                <div class="table-responsive">
                  <table class="table">
                    <tr>
                      <th style="width:50%">Subtotal:</th>
                      <td class="text-right">{{currency()->symbol??''}} {{number_format($sub_total,2)}}</td>
                    </tr>
                    <tr>
                      <th>Total Discount:</th>
                      <td class="text-right">{{currency()->symbol??''}} {{number_format($discount,2)}}</td>
                    </tr>
                    <tr>
                      <th>Gross Sales:</th>
                      <td class="text-right">{{currency()->symbol??''}} {{number_format($total,2)}}</td>
                    </tr>
                    <tr>
                      <th>Total Refunds:</th>
                      <td class="text-right text-danger">- {{currency()->symbol??''}} {{number_format($total_refunds,2)}}</td>
                    </tr>
                    <tr class="bg-light">
                      <th>Net Revenue:</th>
                      <td class="text-right text-success font-weight-bold">{{currency()->symbol??''}} {{number_format($net_revenue,2)}}</td>
                    </tr>
                    <tr>
                      <th>Customer Paid:</th>
                      <td class="text-right">{{currency()->symbol??''}} {{number_format($paid,2)}}</td>
                    </tr>
                    <tr>
                      <th>Customer Due:</th>
                      <td class="text-right">{{currency()->symbol??''}} {{number_format($due,2)}}</td>
                    </tr>
                    <!-- Payment Breakdown -->
                    <tr class="bg-light">
                        <th colspan="2" class="text-center"><em>Payment Breakdown</em></th>
                    </tr>
                    <tr>
                        <th>Cash Collected:</th>
                        <td class="text-right text-success">{{currency()->symbol??''}} {{number_format($total_cash, 2)}}</td>
                    </tr>
                    <tr>
                        <th>Card Payments:</th>
                        <td class="text-right text-info">{{currency()->symbol??''}} {{number_format($total_card, 2)}}</td>
                    </tr>
                    <tr>
                        <th>Online Payments:</th>
                        <td class="text-right text-primary">{{currency()->symbol??''}} {{number_format($total_online, 2)}}</td>
                    </tr>
                  </table>
                </div>
              </div>
              <!-- /.col -->
            </div>
            <!-- /.row -->
          </section>
        </div>
      </div>
      </div>
    </div>
  </div>
</div>
</div>
@endsection

@push('style')
<style>
  .invoice {
    border: none !important;
  }
  .bg-gradient-maroon {
    background: linear-gradient(45deg, #800000, #A01010) !important;
  }
  .text-maroon {
    color: #800000 !important;
  }
</style>
@endpush
@push('script')
<script>
  $(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const startDateParam = urlParams.get('start_date');
    const endDateParam = urlParams.get('end_date');

    const startDate = startDateParam ? moment(startDateParam, 'YYYY-MM-DD') : moment().subtract(29, 'days');
    const endDate = endDateParam ? moment(endDateParam, 'YYYY-MM-DD') : moment();

    $('#daterange-btn').daterangepicker({
        ranges: {
          'Today': [moment(), moment()],
          'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days': [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month': [moment().startOf('month'), moment().endOf('month')],
          'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        startDate: startDate,
        endDate: endDate
      },
      function(start, end) {
        $('#daterange-btn span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        window.location.href = '{{ route("backend.admin.sale.summery") }}?start_date=' + start.format('YYYY-MM-DD') + '&end_date=' + end.format('YYYY-MM-DD');
      }
    );

    // Initial check to set label if it matches a predefined range
    var ranges = {
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    };
    
    // Default to custom
    var label = startDate.format('MMMM D, YYYY') + ' - ' + endDate.format('MMMM D, YYYY');

    // Check if matches any range
    $.each(ranges, function(key, range) {
        if (startDate.isSame(range[0], 'day') && endDate.isSame(range[1], 'day')) {
            label = key;
            return false; // Break the loop
        }
    });

    $('#daterange-btn span').html(label);
  })
</script>
@endpush