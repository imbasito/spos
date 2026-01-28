@extends('backend.master')

@section('title', 'Refunds')

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden" style="min-height: 70vh;">
      <div class="card-header bg-gradient-maroon py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-undo mr-2"></i> Refund List
        </h3>
      </div>

      <div class="card-body p-4">
        <div class="row g-4">
          <div class="col-md-12">
            <div class="table-responsive">
              <table id="refundDatatables" class="table table-hover mb-0 custom-premium-table">
                <thead class="bg-dark text-white text-uppercase font-weight-bold small">
                  <tr>
                    <th data-orderable="false" style="color: #ffffff !important; background-color: #4E342E !important;">#</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Return #</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Order ID</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Total Refund {{currency()->symbol??''}}</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Processed By</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Date</th>
                    <th data-orderable="false" style="color: #ffffff !important; background-color: #4E342E !important;">Action</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
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
@endsection

@push('script')
<script type="text/javascript">
  $(function() {
    let table = $('#refundDatatables').DataTable({
      processing: true,
      serverSide: true,
      ordering: true,
      order: [[0, 'desc']], // Default sort by ID desc
      ajax: {
        url: "{{ route('backend.admin.refunds.index') }}"
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'pl-4' },
        { data: 'return_number', name: 'return_number', className: 'font-weight-bold' },
        { data: 'order_id', name: 'order_id' },
        { data: 'total_refund', name: 'total_refund', className: 'font-weight-bold text-danger' },
        { data: 'processed_by', name: 'processed_by' },
        { data: 'created_at', name: 'created_at' },
        { data: 'action', name: 'action', className: 'text-right pr-4' },
      ],
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search refunds...",
        lengthMenu: "_MENU_ per page",
        paginate: {
            previous: '<i class="fas fa-chevron-left"></i>',
            next: '<i class="fas fa-chevron-right"></i>'
        }
      }
    });
    
    // Style Inputs
    $('.dataTables_filter input').addClass('form-control form-control-sm border bg-light px-3').css('border-radius', '20px');
    $('.dataTables_length select').addClass('form-control form-control-sm border bg-light').css('border-radius', '10px');
  });

  // Global Helper to Hide Spinner
  window.finalizeRefundReceiptLoad = function() {
      const loader = document.getElementById('refundReceiptLoader');
      const frame = document.getElementById('refundReceiptFrame');
      if (loader) loader.style.display = 'none';
      if (frame) frame.style.visibility = 'visible';
  }

  // Listener for Child Frame
  window.addEventListener('message', function(e) {
      if (e.data === 'receipt-loaded' || e.data === 'refund-receipt-loaded') {
          window.finalizeRefundReceiptLoad();
      }
      if (e.data === 'close-modal') {
          $('#refundReceiptModal').modal('hide');
      }
  });

  // Open Refund Receipt Modal (Matches Sales List Logic)
  window.openRefundReceiptV3 = function(url) {
      const frame = document.getElementById('refundReceiptFrame');
      const loader = document.getElementById('refundReceiptLoader');
      
      loader.style.display = 'flex';
      frame.style.visibility = 'hidden';
      frame.src = 'about:blank';
      
      $('#refundReceiptModal').modal('show');
      
      // Load Iframe
      frame.onload = function() {
          if (frame.contentWindow.location.href !== "about:blank") {
              setTimeout(() => {
                  loader.style.display = 'none';
                  frame.style.visibility = 'visible';
              }, 100);
          }
      };

      const cacheBuster = url.indexOf('?') !== -1 ? '&_v=' : '?_v=';
      frame.src = url + cacheBuster + Date.now();
      
      // Safety Timeout
      setTimeout(() => {
          loader.style.display = 'none';
          frame.style.visibility = 'visible';
      }, 3000);
  };

  // Print Logic (Raw / Headless)
  async function printRefundReceipt() {
      const frame = document.getElementById('refundReceiptFrame');
      const currentUrl = frame.src;
      
      // Extract Refund ID
      const match = currentUrl.match(/refunds\/(\d+)/);
      if (!match || !match[1]) {
           // Fallback Browser Print
           frame.contentWindow.focus();
           frame.contentWindow.print();
           return;
      }
      
      const refundId = match[1];
      const apiUrl = `/admin/refunds/${refundId}/details`;

      // Toast Loading
      Swal.fire({
          title: 'Printing...',
          didOpen: () => Swal.showLoading(),
          toast: true, 
          position: 'top-end', 
          showConfirmButton: false
      });

      try {
           if (window.electron && window.electron.printSilent) {
               const res = await fetch(apiUrl);
               const jsonRes = await res.json();
               
               if(jsonRes.success && jsonRes.data) {
                   const printData = jsonRes.data;
                   printData.type = 'refund'; // Force Type

                   await window.electron.printSilent(null, "POS80", null, printData);
                   
                   Swal.fire({ 
                        icon: 'success', title: 'Printed Successfully', 
                        toast: true, position: 'top-end', timer: 2000, showConfirmButton: false 
                   });
               } else {
                   throw new Error("Invalid API Data");
               }
           } else {
               // Fallback
               frame.contentWindow.focus();
               frame.contentWindow.print();
               Swal.close();
           }
      } catch (e) {
          console.error("Print Error", e);
          Swal.fire({ icon: 'warning', title: 'Raw Print Failed', text: 'Falling back to simple print', toast: true, position: 'top-end', timer: 2000 });
          frame.contentWindow.focus();
          frame.contentWindow.print();
      }
  }

</script>

<!-- REFUND RECEIPT MODAL -->
<div class="modal fade" id="refundReceiptModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 420px;">
        <div class="modal-content shadow-lg" style="border: none; border-radius: 8px;">
            
            <!-- Title Bar -->
            <div class="modal-header bg-dark text-white p-2 d-flex justify-content-between align-items-center" style="border-bottom: 3px solid #800000; border-radius: 8px 8px 0 0;">
                <h6 class="modal-title font-weight-bold m-0 pl-2">
                    <i class="fas fa-receipt mr-1"></i> Refund Receipt
                </h6>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-light font-weight-bold shadow-none" onclick="printRefundReceipt()" title="Print Receipt">
                        <i class="fas fa-print text-dark"></i> Print
                    </button>
                    <button type="button" class="btn btn-danger font-weight-bold shadow-none" data-dismiss="modal" title="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body (Iframe) -->
            <div class="modal-body p-0 position-relative" style="height: 600px; background: #525659;">
                <!-- Loader -->
                <div id="refundReceiptLoader" class="position-absolute w-100 h-100 d-flex flex-column justify-content-center align-items-center" style="background: white; z-index: 10;">
                    <div class="spinner-border text-maroon" role="status"></div>
                    <div class="mt-2 font-weight-bold small text-muted">Loading Receipt...</div>
                </div>

                <!-- Preview Frame -->
                <iframe id="refundReceiptFrame" name="refundReceiptFrame" src="about:blank" 
                        style="width: 100%; height: 100%; border: none; display: block; background: white;"></iframe>
            </div>
        </div>
    </div>
</div>
@endpush
