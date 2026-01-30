@extends('backend.master')

@section('title', 'Brands')

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <!-- Spotlight Search -->
    <div class="card shadow-sm border-0 border-radius-15 mb-4 overflow-hidden">
      <div class="card-body p-3">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="input-group spotlight-search-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-maroon"></i></span>
              </div>
              <input type="text" id="quickSearchInput" class="form-control border-left-0 apple-input" placeholder="Search brand name..." autofocus>
            </div>
          </div>
          <div class="col-md-6 text-right">
            @can('brand_create')
            <a href="{{ route('backend.admin.brands.create') }}" class="btn btn-apple-primary btn-apple px-4 shadow-sm font-weight-bold text-white">
              <i class="fas fa-plus-circle mr-1"></i> Add New Brand
            </a>
            @endcan
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">
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
  .text-maroon {
    color: #800000 !important;
  }
  .bg-gradient-maroon {
    background: linear-gradient(45deg, #800000, #A01010) !important;
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
        { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'pl-4', orderable: false, searchable: false },
        { data: 'image', name: 'image', orderable: false, searchable: false },
        { data: 'name', name: 'name', className: 'font-weight-bold' },
        { data: 'status', name: 'status' },
        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right pr-4' },
      ],
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
</script>
@endpush