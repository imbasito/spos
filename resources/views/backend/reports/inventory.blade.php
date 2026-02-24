@extends('backend.master')

@section('title', 'Inventory Report')

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden" style="min-height: 70vh;">
      <div class="card-header bg-gradient-maroon py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-boxes mr-2"></i> Inventory Report
        </h3>
        <div id="export_buttons" class="d-flex justify-content-end ml-auto">
            <!-- Buttons will be appended here -->
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
              <input type="text" id="quickSearchInput" class="form-control border-0 py-4 apple-input" placeholder="Search inventory by name or SKU..." autofocus style="font-size: 1rem; box-shadow: none;">
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table id="datatables" class="table table-hover mb-0 custom-premium-table">
            <thead class="bg-dark text-white text-uppercase font-weight-bold small">
              <tr>
                <th width="50" class="pl-4 text-white" style="color: #ffffff !important; background-color: #4E342E !important;">#</th>
                <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Name</th>
                <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">SKU</th>
                <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Price</th>
                <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Stock</th>
              </tr>
            </thead>
          </table>
          <!-- Pagination Links -->
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
@push('style')
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
  .dt-buttons .btn {
      background: #f8f9fa;
      color: #333;
      border: 1px solid #ddd;
      margin-left: 5px;
      border-radius: 20px;
      font-size: 0.95rem;
      font-weight: 600;
      padding: 0.5rem 1rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .dt-buttons .btn:hover {
      background: #d1d5db !important;
      border-color: #adb5bd !important;
      color: #000 !important;
      transform: translateY(-2px);
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  }
  .btn-group>.btn:not(:first-child), .btn-group>.btn-group:not(:first-child) {
      margin-left: 5px;
  }
</style>
@endpush
@push('script')
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>

<script type="text/javascript">
  $(function() {
    let table = $('#datatables').DataTable({
      processing: true,
      serverSide: true,
      ordering: true,
      order: [
        [1, 'desc']
      ],
      lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, "All"]
      ],
      ajax: {
        url: "{{ route('backend.admin.inventory.report') }}"
      },
      lengthChange: true,
      columns: [{
          data: 'DT_RowIndex',
          name: 'DT_RowIndex'
        },
        {
          data: 'name',
          name: 'name'
        },
        {
          data: 'sku',
          name: 'sku'
        }, {
          data: 'price',
          name: 'price'
        },
        {
          data: 'quantity',
          name: 'quantity'
        },
      ],
      dom: 't<"p-3 d-flex justify-content-between align-items-center"ip>', 
      buttons: [
          { extend: 'excel', text: '<i class="fas fa-file-excel mr-2 text-success"></i> Excel', className: 'btn btn-light btn-md shadow-sm border-0 font-weight-bold text-maroon' },
          { extend: 'pdf', text: '<i class="fas fa-file-pdf mr-2 text-danger"></i> PDF', className: 'btn btn-light btn-md shadow-sm border-0 font-weight-bold text-maroon' },
          { extend: 'print', text: '<i class="fas fa-print mr-2 text-primary"></i> Print', className: 'btn btn-light btn-md shadow-sm border-0 font-weight-bold text-maroon' }
      ],
      initComplete: function() {
        // Move buttons to the header container
        table.buttons().container().appendTo('#export_buttons');
      }
    });

    $('#quickSearchInput').on('keyup input', function() {
        table.search(this.value).draw();
    });
  });
</script>
@endpush