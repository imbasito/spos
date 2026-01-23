@extends('backend.master')

@section('title', 'Suppliers')

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden" style="min-height: 70vh;">
      <div class="card-header bg-gradient-maroon py-3 d-flex align-items-center">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-truck-moving mr-2"></i> Supplier Directory
        </h3>
        @can('supplier_create')
        <a href="{{ route('backend.admin.suppliers.create') }}" class="btn btn-light btn-md px-4 ml-auto shadow-sm hover-lift font-weight-bold text-maroon">
          <i class="fas fa-plus-circle mr-1"></i> Add New Supplier
        </a>
        @endcan
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table id="datatables" class="table table-hover mb-0 custom-premium-table">
            <thead class="bg-dark text-white text-uppercase font-weight-bold small">
              <tr>
                <th width="50" class="pl-4 text-white" style="color: #ffffff !important; background-color: #4E342E !important;">#</th>
                <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Supplier Name</th>
                <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Phone Number</th>
                <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Address</th>
                <th class="text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Registration Date</th>
                <th width="100" class="text-right pr-4 text-white" style="color: #ffffff !important; background-color: #4E342E !important;">Action</th>
              </tr>
            </thead>
            <tbody>
              {{-- Loaded via AJAX --}}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

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
    padding-top: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #edf2f9;
  }
  .custom-premium-table tr:last-child td {
    border-bottom: none;
  }
  .custom-premium-table tbody tr:hover {
    background-color: #f8fafc;
  }
</style>
@endsection

@push('script')
<script type="text/javascript">
  $(function() {
    let table = $('#datatables').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('backend.admin.suppliers.index') }}",
      order: [[1, 'asc']],
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'pl-4' },
        { data: 'name', name: 'name', className: 'font-weight-bold' },
        { data: 'phone', name: 'phone' },
        { data: 'address', name: 'address' },
        { data: 'created_at', name: 'created_at' },
        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right pr-4' }
      ],
      dom: '<"p-3 d-flex justify-content-between align-items-center"lf>t<"p-3 d-flex justify-content-between align-items-center"ip>',
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search Suppliers...",
        lengthMenu: "_MENU_ per page",
        paginate: {
          previous: '<i class="fas fa-chevron-left"></i>',
          next: '<i class="fas fa-chevron-right"></i>'
        }
      }
    });

    // Custom filtering for the search input to look premium
    $('.dataTables_filter input').addClass('form-control form-control-sm border bg-light px-3').css('border-radius', '20px');
    $('.dataTables_length select').addClass('form-control form-control-sm border-0 bg-light').css('border-radius', '10px');
  });
</script>
@endpush