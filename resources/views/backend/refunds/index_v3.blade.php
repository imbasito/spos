@extends('backend.master')

@section('title', 'Refunds V3')

@section('content')
<div class="card">
  <div class="card-header bg-danger text-white">
    <h3 class="card-title"><i class="fas fa-undo"></i> Refund History (V3 DUPLICATE)</h3>
  </div>
  <div class="card-body p-2 p-md-4 pt-0">
    <div class="row g-4">
      <div class="col-md-12">
        <div class="card-body table-responsive p-0" id="table_data">
          <!-- Debug Info -->
          <div class="alert alert-info">
              <strong>Debug Mode:</strong> This is the V3 Duplicate Tab. If you see this, the code update IS ACTIVE.
          </div>

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

<!-- FRESH REFUND PREVIEW MODAL V3 FIXED -->
<div class="modal fade" id="refundPreviewModalV3_FIXED" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 400px;">
        <div class="modal-content shadow-lg border-0" style="border-radius: 8px;">
            
            <!-- Header: Title + Actions -->
            <div class="modal-header bg-dark text-white p-2 d-flex justify-content-between align-items-center" style="border-bottom: 3px solid #800000;">
                <h6 class="modal-title font-weight-bold ml-2">
                    <i class="fas fa-receipt mr-1"></i> Receipt Preview (V3 FIXED)
                </h6>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-light font-weight-bold shadow-none" id="btnPrintRefundV3" title="Print Receipt">
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
                <div id="refundLoaderV3" class="position-absolute w-100 h-100 d-flex flex-column justify-content-center align-items-center" style="background: white; z-index: 10;">
                    <div class="spinner-border text-maroon" role="status"></div>
                    <div class="mt-2 text-muted small font-weight-bold">Loading V3...</div>
                </div>
                
                <!-- Iframe -->
                <iframe id="refundFrameV3" name="refundFrameV3" src="about:blank" 
                        style="width: 100%; height: 100%; border: none; display: block; background: white;"></iframe>
            </div>
        </div>
    </div>
</div>

<style>
    /* Force Modal on Top - SUPER HIGH Z-INDEX */
    #refundPreviewModalV3_FIXED { z-index: 100000 !important; }
    .modal-backdrop { z-index: 99999 !important; }
</style>

@endsection

@push('script')
<script type="text/javascript">
  
  // 1. OPEN MODAL V3
  window.openRefundReceiptV3 = function(url) {
      console.log("V3 FIXED MODAL TRIGGERED:", url);

      // Fix Stacking Context: Move UNIQUE modal to body
      const modal = $('#refundPreviewModalV3_FIXED');
      if (modal.parent()[0] !== document.body) {
          modal.appendTo("body");
      }

      // Show Modal & Loader
      $('#refundLoaderV3').show();
      $('#refundFrameV3').attr('src', 'about:blank');
      modal.modal('show');

      // Set Iframe Source with robust loading logic
      setTimeout(() => {
          const frame = document.getElementById('refundFrameV3');
          
          // Safety Timeout: Force hide loader after 5 seconds if onload fails
          const safetyTimeout = setTimeout(() => {
               console.warn("Loader Safety Timeout triggered");
               $('#refundLoaderV3').fadeOut(200);
          }, 5000);

          frame.onload = function() {
              console.log("Iframe Loaded");
              clearTimeout(safetyTimeout); 
              $('#refundLoaderV3').fadeOut(200);
          };
          
          frame.src = url; 
          
          // Store ID for Printing
          const match = url.match(/\/refunds\/(\d+)/);
          if(match && match[1]) {
              $('#btnPrintRefundV3').data('refund-id', match[1]);
          }
      }, 300);
  };

  // 2. PRINT LOGIC (Raw / Silent)
  $('#btnPrintRefundV3').click(async function() {
      const refundId = $(this).data('refund-id');
      const frame = document.getElementById('refundFrameV3');
      const btn = $(this);
      const originalIcon = btn.html();
      
      btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Printing...');

      try {
          // Attempt Raw Printing via Electron
          if (window.electron && window.electron.printSilent) {
              const res = await fetch(`/admin/refunds/${refundId}/details`);
              const json = await res.json();
              
              if (json.success && json.data) {
                  json.data.type = 'refund'; 
                  await window.electron.printSilent(null, "POS80", null, json.data);
                  Swal.fire({ 
                      icon: 'success', 
                      title: 'Printed Successfully', 
                      toast: true, 
                      position: 'top-end', 
                      showConfirmButton: false, 
                      timer: 2000 
                  });
              } else {
                  throw new Error("Invalid Data Received");
              }
          } else {
              // Fallback: Browser Print
              if(frame.contentWindow) { 
                  frame.contentWindow.focus(); 
                  frame.contentWindow.print(); 
              }
          }
      } catch (e) {
          console.error("Print Error:", e);
          Swal.fire({ icon: 'error', title: 'Print Failed', text: e.message, toast: true, position: 'top-end', timer: 3000 });
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
