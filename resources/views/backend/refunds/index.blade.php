@extends('backend.master')

@section('title', 'Refunds')

@section('content')
<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-undo"></i> Refund History</h3>
  </div>
  <div class="card-body p-2 p-md-4 pt-0">
    <div class="row g-4">
      <div class="col-md-12">
        <div class="card-body table-responsive p-0" id="table_data">
          <table id="datatables" class="table table-hover">
            <thead>
              <tr>
                <th data-orderable="false">#</th>
                <th>Return #</th>
                <th>Order #</th>
                <th>Refund Amount</th>
                <th>Processed By</th>
                <th>Date</th>
                <th data-orderable="false">Action</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('script')
<script type="text/javascript">
  $(function() {
    let table = $('#datatables').DataTable({
      processing: true,
      serverSide: true,
      ordering: true,
      order: [[1, 'desc']],
      ajax: {
        url: "{{ route('backend.admin.refunds.index') }}"
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex' },
        { data: 'return_number', name: 'return_number' },
        { data: 'order_id', name: 'order_id' },
        { data: 'total_refund', name: 'total_refund' },
        { data: 'processed_by', name: 'processed_by' },
        { data: 'created_at', name: 'created_at' },
        { data: 'action', name: 'action' },
      ],
      drawCallback: function() {
          // Re-bind events if needed (delegation handles it usually)
      }
    });

  });
</script>
@endpush
