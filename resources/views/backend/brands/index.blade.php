@extends('backend.master')

@section('title', 'Brands')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">
      <div class="card-header bg-gradient-maroon py-3 d-flex align-items-center">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-certificate mr-2"></i> Brand Management
        </h3>
        @can('brand_create')
        <a href="{{ route('backend.admin.brands.create') }}" class="btn btn-light btn-md px-4 ml-auto shadow-sm hover-lift font-weight-bold text-maroon">
          <i class="fas fa-plus-circle mr-1"></i> Add New Brand
        </a>
        @endcan
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table id="datatables" class="table table-hover mb-0 custom-premium-table">
            <thead class="bg-dark text-white text-uppercase font-weight-bold small">
              <tr>
                <th width="50" class="pl-4 text-white" style="background-color: #4E342E !important;">#</th>
                <th width="80" class="text-white" style="background-color: #4E342E !important;">Image</th>
                <th class="text-white" style="background-color: #4E342E !important;">Brand Name</th>
                <th width="100" class="text-white" style="background-color: #4E342E !important;">Status</th>
                <th width="120" class="text-right pr-4 text-white" style="background-color: #4E342E !important;">Action</th>
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
  .img-thumb {
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #eee;
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
      order: [[2, 'asc']],
      ajax: {
        url: "{{ route('backend.admin.brands.index') }}"
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'pl-4' },
        { data: 'image', name: 'image', orderable: false, searchable: false },
        { data: 'name', name: 'name', className: 'font-weight-bold' },
        { data: 'status', name: 'status' },
        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right pr-4' },
      ],
      dom: '<"p-3 d-flex justify-content-between align-items-center"lf>t<"p-3 d-flex justify-content-between align-items-center"ip>',
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search Brands...",
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