@extends('backend.master')

@section('title', 'Refunds')

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
              <input type="text" id="quickSearchInput" class="form-control border-left-0 apple-input" placeholder="Search refund ID, order ID, or amount..." autofocus>
            </div>
          </div>
          <div class="col-md-6 text-right">
              <!-- Actions could go here -->
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">
      <div class="card-header bg-gradient-maroon py-3">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-undo mr-2"></i> Refund List
        </h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table id="datatables" class="table table-hover mb-0 custom-premium-table">
            <thead class="bg-dark text-white text-uppercase font-weight-bold small">
              <tr>
                <th width="50" class="pl-4 text-white" style="background-color: #4E342E !important;">#</th>
                <th class="text-white" style="background-color: #4E342E !important;">Return #</th>
                <th class="text-white" style="background-color: #4E342E !important;">Order #</th>
                <th class="text-white" style="background-color: #4E342E !important;">Refund Amount</th>
                <th class="text-white" style="background-color: #4E342E !important;">Processed By</th>
                <th class="text-white" style="background-color: #4E342E !important;">Date</th>
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
</style>

<!-- FRESH REFUND PREVIEW MODAL -->
<div class="modal fade" id="refundPreviewModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 400px;">
        <div class="modal-content shadow-lg border-0" style="border-radius: 8px;">
            <div class="modal-header bg-dark text-white p-2 d-flex justify-content-between align-items-center" style="border-bottom: 3px solid #800000;">
                <h6 class="modal-title font-weight-bold ml-2">
                    <i class="fas fa-receipt mr-1"></i> Receipt Preview
                </h6>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-light font-weight-bold shadow-none" id="btnPrintRefund" title="Print Receipt">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button type="button" class="btn btn-danger font-weight-bold shadow-none" data-dismiss="modal" title="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body p-0 position-relative" style="height: 550px; background: #e9ecef;">
                <div id="refundLoader" class="position-absolute w-100 h-100 d-flex flex-column justify-content-center align-items-center" style="background: white; z-index: 10;">
                    <div class="spinner-border text-maroon" role="status"></div>
                    <div class="mt-2 text-muted small font-weight-bold">Loading Preview...</div>
                </div>
                <iframe id="refundFrame" name="refundFrame" src="about:blank" style="width: 100%; height: 100%; border: none; display: block; background: white;"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script type="text/javascript">
  window.openRevisedRefundReceipt = function(url) {
      $('#refundLoader').show();
      $('#refundFrame').attr('src', 'about:blank');
      $('#refundPreviewModal').modal('show');
      setTimeout(() => {
          const frame = document.getElementById('refundFrame');
          frame.onload = function() { $('#refundLoader').fadeOut(200); };
          frame.src = url; 
          const match = url.match(/\/refunds\/(\d+)/);
          if(match && match[1]) { $('#btnPrintRefund').data('refund-id', match[1]); }
      }, 300);
  };

  $('#btnPrintRefund').click(async function() {
      const refundId = $(this).data('refund-id');
      const frame = document.getElementById('refundFrame');
      if (!refundId) { if(frame.contentWindow) frame.contentWindow.print(); return; }
      const btn = $(this);
      const originalIcon = btn.html();
      btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
      try {
          if (window.electron && window.electron.printSilent) {
              const res = await fetch(`/admin/refunds/${refundId}/details`);
              const json = await res.json();
              if (json.success && json.data) {
                  json.data.type = 'refund';
                  await window.electron.printSilent(null, "POS80", null, json.data);
                  Swal.fire({ icon: 'success', title: 'Printed', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
              } else { throw new Error("Invalid Data"); }
          } else { if(frame.contentWindow) { frame.contentWindow.focus(); frame.contentWindow.print(); } }
      } catch (e) { if(frame.contentWindow) frame.contentWindow.print(); } finally { btn.prop('disabled', false).html(originalIcon); }
  });

  $(function() {
    let table = $('#datatables').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('backend.admin.refunds.index') }}",
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'return_number', name: 'return_number' },
        { data: 'order_id', name: 'order_id' },
        { data: 'total_refund', name: 'total_refund' },
        { data: 'processed_by', name: 'processed_by' },
        { data: 'created_at', name: 'created_at' },
        { data: 'action', name: 'action', orderable: false, searchable: false },
      ],
      order: [[1, 'desc']],
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
