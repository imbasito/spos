@extends('backend.master')

@section('title', 'Refunds')

@section('content')
<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-undo"></i> Refund History (V2)</h3>
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
  </div> {{-- Close main card-body --}}
</div> {{-- Close main card --}}

<!-- FRESH REFUND PREVIEW MODAL -->
<div class="modal fade" id="refundPreviewModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 400px;">
        <div class="modal-content shadow-lg border-0" style="border-radius: 8px;">
            
            <!-- Header: Title + Actions -->
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

            <!-- Body: Iframe Container -->
            <div class="modal-body p-0 position-relative" style="height: 550px; background: #e9ecef;">
                <!-- Loader -->
                <div id="refundLoader" class="position-absolute w-100 h-100 d-flex flex-column justify-content-center align-items-center" style="background: white; z-index: 10;">
                    <div class="spinner-border text-maroon" role="status"></div>
                    <div class="mt-2 text-muted small font-weight-bold">Loading Preview...</div>
                </div>
                
                <!-- Iframe -->
                <iframe id="refundFrame" name="refundFrame" src="about:blank" 
                        style="width: 100%; height: 100%; border: none; display: block; background: white;"></iframe>
            </div>
        </div>
    </div>
</div>

<style>
    /* Force Modal on Top */
    #refundPreviewModal { z-index: 1050 !important; }
    .modal-backdrop { z-index: 1040 !important; }
</style>

@endsection

@push('script')
<script type="text/javascript">
  
  // 1. OPEN MODAL
  window.openRevisedRefundReceipt = function(url) {
      console.log("REVISED RECEIPT MODAL TRIGGERED:", url);

      // Show Modal & Loader
      $('#refundLoader').show();
      $('#refundFrame').attr('src', 'about:blank');
      $('#refundPreviewModal').modal('show');

      // Set Iframe Source (Blade View)
      setTimeout(() => {
          const frame = document.getElementById('refundFrame');
          frame.onload = function() {
              $('#refundLoader').fadeOut(200);
          };
          frame.src = url; 
          
          // Store ID for Printing
          const match = url.match(/\/refunds\/(\d+)/);
          if(match && match[1]) {
              $('#btnPrintRefund').data('refund-id', match[1]);
          }
      }, 300);
  };

  // 2. PRINT LOGIC (Raw / Silent)
  $('#btnPrintRefund').click(async function() {
      const refundId = $(this).data('refund-id');
      const frame = document.getElementById('refundFrame');

      if (!refundId) {
           // Fallback to Iframe Print if ID missing
           if(frame.contentWindow) frame.contentWindow.print();
           return;
      }

      // Visual Feedback
      const btn = $(this);
      const originalIcon = btn.html();
      btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

      try {
          // A. Fetch formatted JSON for Hardware
          if (window.electron && window.electron.printSilent) {
              const res = await fetch(`/admin/refunds/${refundId}/details`);
              const json = await res.json();
              
              if (json.success && json.data) {
                  json.data.type = 'refund'; // Enforce Type
                  await window.electron.printSilent(null, "POS80", null, json.data);
                  
                  // Success Toast
                  Swal.fire({
                      icon: 'success', 
                      title: 'Printed', 
                      toast: true, 
                      position: 'top-end', 
                      showConfirmButton: false, 
                      timer: 2000 
                  });
              } else {
                  throw new Error("Invalid Data");
              }
          } else {
              // B. Fallback to Browser Print
              if(frame.contentWindow) {
                  frame.contentWindow.focus();
                  frame.contentWindow.print();
              }
          }
      } catch (e) {
          console.error("Print Error:", e);
          // Last Resort
          if(frame.contentWindow) frame.contentWindow.print();
      } finally {
          btn.prop('disabled', false).html(originalIcon);
      }
  });

  $(function() {
    $('#datatables').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('backend.admin.refunds.index') }}",
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex' },
        { data: 'return_number', name: 'return_number' },
        { data: 'order_id', name: 'order_id' },
        { data: 'total_refund', name: 'total_refund' },
        { data: 'processed_by', name: 'processed_by' },
        { data: 'created_at', name: 'created_at' },
        { data: 'action', name: 'action' },
      ],
      order: [[1, 'desc']]
    });
  });
</script>
@endpush
