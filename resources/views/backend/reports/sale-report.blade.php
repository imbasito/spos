@extends('backend.master')

@section('title', 'Sale Report')

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden" style="min-height: 70vh;">
      <div class="card-header bg-gradient-maroon py-3 d-flex align-items-center">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-file-invoice-dollar mr-2"></i> Sales Report
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
        <!-- Spotlight Search -->
        <div class="row mb-4">
          <div class="col-md-12">
            <div class="input-group shadow-sm spotlight-search-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white border-0 pl-3">
                  <i class="fas fa-search text-maroon"></i>
                </span>
              </div>
              <input type="text" id="quickSearchInput" class="form-control border-0 py-4 apple-input" placeholder="Search report by Sale ID or customer..." autofocus style="font-size: 1rem; box-shadow: none;">
            </div>
          </div>
        </div>

    <div class="row g-4">
      <div class="col-md-12">
        <div class="card-body p-0">
          <section class="invoice">
            <!-- Summary Row -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="callout callout-info">
                        <small class="text-muted">Gross Sales</small><br>
                        <strong class="h5">{{number_format($total, 2)}}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="callout callout-danger">
                        <small class="text-muted">Refunds</small><br>
                        <strong class="h5 text-danger">- {{number_format($total_refunds, 2)}}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="callout callout-success">
                        <small class="text-muted">Net Revenue</small><br>
                        <strong class="h5 text-success">{{number_format($net_revenue, 2)}}</strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="callout callout-warning">
                        <small class="text-muted">Due Amount</small><br>
                        <strong class="h5">{{number_format($due, 2)}}</strong>
                    </div>
                </div>
            </div>

            <!-- Table row -->
            <div class="row justify-content-center">
              <div class="col-12 table-responsive">
                <table id="datatables" class="table table-hover mb-0 custom-premium-table">
                  <thead class="bg-dark text-white text-uppercase font-weight-bold small">
                    <tr>
                      <th data-orderable="false" width="40" class="pl-4 text-white" style="color: #ffffff !important; background-color: #4E342E !important;">#</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">SaleId</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Customer</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Date</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Item</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Sub Total {{currency()->symbol??''}}</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Discount {{currency()->symbol??''}}</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Total {{currency()->symbol??''}}</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Paid {{currency()->symbol??''}}</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Due {{currency()->symbol??''}}</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                  <tbody>
                    {{-- Loaded via AJAX --}}
                  </tbody>
                </table>
              </div>
              <!-- /.col -->
            </div>
            <!-- /.row -->
            <!-- /.row -->
          </section>
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
</style>
@endpush
@push('script')
<script>
  $(function() {
    // Extract start and end dates from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const startDate = urlParams.get('start_date') || moment().subtract(29, 'days').format('YYYY-MM-DD'); 
    const endDate = urlParams.get('end_date') || moment().format('YYYY-MM-DD');

    // Initialize DataTable using server-side processing
    let table = $('#datatables').DataTable({
      processing: true,
      serverSide: true,
      ordering: true,
      ajax: {
        url: "{{ route('backend.admin.sale.report') }}",
        data: function(d) {
           d.start_date = startDate;
           d.end_date = endDate;
        }
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'pl-4' },
        { data: 'saleId', name: 'id' }, // use 'id' for DB column name search
        { data: 'customer', name: 'customers.name' },
        { data: 'date', name: 'created_at' },
        { data: 'item', name: 'item', orderable: false },
        { data: 'sub_total', name: 'sub_total' },
        { data: 'discount', name: 'discount' },
        { data: 'total', name: 'total', className: 'font-weight-bold text-maroon' },
        { data: 'paid', name: 'paid' },
        { data: 'due', name: 'due' },
        { data: 'status', name: 'status' }
      ],
      order: [[3, 'desc']], 
      dom: 't<"p-3 d-flex justify-content-between align-items-center"ip>',
      language: {
        paginate: {
            previous: '<i class="fas fa-chevron-left"></i>',
            next: '<i class="fas fa-chevron-right"></i>'
        }
      }
    });

    $('#quickSearchInput').on('keyup input', function() {
        table.search(this.value).draw();
    });
    });

    // Initialize the date range picker
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
        endDate: moment(endDate, "YYYY-MM-DD"),
        autoApply: true // Auto apply nicely closes the picker or applies immediately
      },
      function(start, end, label) {
        // Update the button text with the selected range label if available, otherwise dates
        if (label && label !== 'Custom Range') {
            $('#daterange-btn span').html(label);
        } else {
            $('#daterange-btn span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        }
        
        // Redirect with selected start and end dates
        window.location.href = '{{ route("backend.admin.sale.report") }}?start_date=' + start.format('YYYY-MM-DD') + '&end_date=' + end.format('YYYY-MM-DD');
      }
    );

    // Set the initial display text
    // Check if current range matches any predefined range
    const ranges = {
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    };
    
    let label = 'Custom Range';
    const startObj = moment(startDate, "YYYY-MM-DD");
    const endObj = moment(endDate, "YYYY-MM-DD");

    for (const [key, value] of Object.entries(ranges)) {
        // Compare formatted dates
        if (startObj.format('YYYY-MM-DD') === value[0].format('YYYY-MM-DD') && 
            endObj.format('YYYY-MM-DD') === value[1].format('YYYY-MM-DD')) {
             label = key;
             break;
        }
    }
    
    if (label !== 'Custom Range') {
         $('#daterange-btn span').html(label);
    } else {
         $('#daterange-btn span').html(moment(startDate, "YYYY-MM-DD").format('MMMM D, YYYY') + ' - ' + moment(endDate, "YYYY-MM-DD").format('MMMM D, YYYY'));
    }
  });
</script>
@endpush