@extends('backend.master')

@section('title', 'Purchase')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">
      <div class="card-header bg-white py-3 d-flex align-items-center border-bottom-0">
        <h3 class="card-title font-weight-bold text-dark mb-0">
          <i class="fas fa-shopping-cart mr-2 text-primary"></i> Purchase Records
        </h3>
        @can('purchase_create')
        <a href="{{ route('backend.admin.purchase.create') }}" class="btn bg-gradient-primary btn-md px-4 ml-auto shadow-sm hover-lift font-weight-bold text-white" style="color: #fff !important;">
          <i class="fas fa-plus-circle mr-1 text-white"></i> Add New Purchase
        </a>
        @endcan
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table id="datatables" class="table table-hover mb-0 custom-premium-table">
            <thead class="bg-dark text-white text-uppercase font-weight-bold small">
              <tr>
                <th width="50" class="pl-4 text-white" style="background-color: #004D40 !important;">#</th>
                <th class="text-white" style="background-color: #004D40 !important;">Supplier</th>
                <th class="text-white" style="background-color: #004D40 !important;">Purchase ID</th>
                <th class="text-white" style="background-color: #004D40 !important;">Total ({{currency()->symbol??''}})</th>
                <th class="text-white" style="background-color: #004D40 !important;">Date</th>
                <th width="120" class="text-right pr-4 text-white" style="background-color: #004D40 !important;">Action</th>
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
</style>
@endsection


@push('script')
<script type="text/javascript">
  $(function() {
    let table = $('#datatables').DataTable({
      processing: true,
      serverSide: true,
      ordering: true,
      order: [[4, 'desc']],
      ajax: {
        url: "{{ route('backend.admin.purchase.index') }}"
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'pl-4' },
        { data: 'supplier', name: 'supplier', className: 'font-weight-bold' },
        { data: 'id', name: 'id' },
        { data: 'total', name: 'total' },
        { data: 'created_at', name: 'created_at' },
        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right pr-4' },
      ],
      dom: '<"p-3 d-flex justify-content-between align-items-center"lf>t<"p-3 d-flex justify-content-between align-items-center"ip>',
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search Purchases...",
        lengthMenu: "_MENU_ per page",
        paginate: {
          previous: '<i class="fas fa-chevron-left"></i>',
          next: '<i class="fas fa-chevron-right"></i>'
        }
      }
    });

    $('.dataTables_filter input').addClass('form-control form-control-sm border-0 bg-light px-3').css('border-radius', '20px');
    $('.dataTables_length select').addClass('form-control form-control-sm border-0 bg-light').css('border-radius', '10px');
  });
</script>
@endpush