@extends('backend.master')

@section('title', 'Sale Report')

@section('content')
<div class="card">
  <div class="mt-n5 mb-3 d-flex justify-content-end">
    <div class="form-group">
      <div class="input-group">
        <button type="button" class="btn btn-default float-right" id="daterange-btn">
          <i class="far fa-calendar-alt"></i> Filter by date
          <i class="fas fa-caret-down"></i>
        </button>
      </div>
    </div>
  </div>
  <div class="card-body p-2 p-md-4 pt-0">
    <div class="row g-4">
      <div class="col-md-12">
        <div class="card-body p-0">
          <section class="invoice">
            <!-- info row -->
            <div class="row invoice-info">
              <div class="col-sm-4">
              </div>
              <!-- /.col -->
              <div class="col-sm-4">
                <address>
                  <strong>Sale Summery ({{$start_date}} - {{$end_date}})</strong><br>
                </address>
              </div>
              <!-- /.col -->
              <div class="col-sm-2">
              </div>
              <!-- /.col -->
            </div>
            <!-- /.row -->

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
            <div class="row no-print">
              <div class="col-12">
                <button type="button" onclick="window.print()" class="btn btn-success float-right"><i class="fas fa-print"></i> Print</a>
                </button>
              </div>
            </div>
            <!-- /.row -->
          </section>
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
</style>
@endpush
@push('script')
<script>
  $(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const startDate = urlParams.get('start_date') || moment().subtract(29, 'days').format('YYYY-MM-DD'); // Default to last 30 days if not present
    const endDate = urlParams.get('end_date') || moment().format('YYYY-MM-DD'); // Default to today if not present

    //Date range as a button
    $('#daterange-btn').daterangepicker({
        ranges: {
          'Today': [moment(), moment()],
          'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days': [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month': [moment().startOf('month'), moment().endOf('month')],
          'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        startDate: moment(startDate, "YYYY-MM-DD"),
        endDate: moment(endDate, "YYYY-MM-DD")
      },
      function(start, end) {
        $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'))
        window.location.href = '{{ route("backend.admin.sale.summery") }}?start_date=' + start.format('YYYY-MM-DD') + '&end_date=' + end.format('YYYY-MM-DD');
      }
    )
  })
</script>
@endpush