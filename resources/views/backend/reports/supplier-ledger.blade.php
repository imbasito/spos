@extends('backend.master')

@section('title', 'Supplier Ledger (Khata)')

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden" style="min-height: 70vh;">
      <div class="card-header bg-gradient-maroon py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-file-invoice-dollar mr-2"></i> Supplier Ledger (Khata)
        </h3>
      </div>
      <div class="card-body p-4">
    <div class="row g-4">
      <div class="col-md-12">
        <div class="card-body p-0">
          <section class="invoice">
            <!-- Summary Row -->
            <div class="row mb-3 justify-content-center">
                <div class="col-md-4">
                    <div class="callout callout-danger shadow-sm">
                        <small class="text-muted text-uppercase font-weight-bold">Total Outstanding Debt</small><br>
                        <strong class="h3 text-danger">{{ number_format($totalDebt, 2) }}</strong>
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
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Supplier Name</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Phone</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Total Purchase</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Total Paid</th>
                      <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Balance Due</th>
                    </tr>
                  </thead>
                  <tbody>
                    {{-- Loaded via AJAX --}}
                  </tbody>
                </table>
              </div>
              <!-- /.col -->
            </div>
            <!-- /.row -->
            <div class="row no-print mt-4">
              <div class="col-12">
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary float-right"><i class="fas fa-print"></i> Print Report</a>
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
  .bg-gradient-maroon {
    background: linear-gradient(45deg, #800000, #A01010) !important;
  }
</style>
@endpush

@push('script')
<script>
  $(function() {
    // Initialize DataTable using server-side processing
    let table = $('#datatables').DataTable({
      processing: true,
      serverSide: true,
      ordering: true,
      ajax: {
        url: "{{ route('backend.admin.supplier.ledger') }}",
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'pl-4' },
        { data: 'name', name: 'name' },
        { data: 'phone', name: 'phone' },
        { data: 'total_purchase', name: 'total_purchase', searchable: false },
        { data: 'total_paid', name: 'total_paid', searchable: false },
        { data: 'balance_due', name: 'balance_due', searchable: false },
      ],
      order: [[1, 'asc']], // Order by Name
      dom: 'lfrtip',
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search supplier...",
        lengthMenu: "_MENU_ per page",
        paginate: {
            previous: '<i class="fas fa-chevron-left"></i>',
            next: '<i class="fas fa-chevron-right"></i>'
        }
      },
      initComplete: function() {
          $('.dataTables_filter input').addClass('form-control form-control-sm border bg-light px-3').css('border-radius', '20px');
          $('.dataTables_length select').addClass('form-control form-control-sm border bg-light').css('border-radius', '10px');
      }
    });
  });
</script>
@endpush
