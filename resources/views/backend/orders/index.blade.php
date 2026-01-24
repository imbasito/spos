@extends('backend.master')

@section('title', 'Sale')

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden" style="min-height: 70vh;">
      <div class="card-header bg-gradient-maroon py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-shopping-cart mr-2"></i> Sales List
        </h3>
      </div>

      <div class="card-body p-4">
        <!-- Barcode Scanner Input -->
        <div class="row mb-3">
          <div class="col-md-5">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white border-right-0 pl-3" style="border-radius: 25px 0 0 25px; border-color: #b0bec5; border-width: 1px;">
                  <i class="fas fa-barcode text-maroon"></i>
                </span>
              </div>
              <input 
                type="text" 
                id="order-barcode-input" 
                class="form-control border-left-0 h-auto py-2" 
                placeholder="Scan receipt barcode (ORD-00000123)"
                autofocus
                style="border-radius: 0 25px 25px 0; border-color: #b0bec5; border-width: 1px; font-size: 1rem;"
              >
              <div class="input-group-append ml-2">
                <button class="btn btn-white text-maroon font-weight-bold shadow-sm border px-4 hover-lift" type="button" onclick="searchOrderByBarcode()" style="border-radius: 25px; border-color: #b0bec5 !important; border-width: 1px;">
                  <i class="fas fa-search mr-1"></i> Find
                </button>
              </div>
            </div>
            <small class="text-muted pl-3 mt-1 d-block"><i class="fas fa-info-circle mr-1"></i> Scan or type order barcode to view invoice</small>
          </div>
        </div>
        
        <div class="row g-4">
          <div class="col-md-12">
            <div class="table-responsive">
              <table id="datatables" class="table table-hover mb-0 custom-premium-table">
                <thead class="bg-dark text-white text-uppercase font-weight-bold small">
                  <tr>
                    <th data-orderable="false" style="color: #ffffff !important; background-color: #4E342E !important;">#</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">SaleId</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Customer</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Item</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Sub Total {{currency()->symbol??''}}</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Discount {{currency()->symbol??''}}</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Total {{currency()->symbol??''}}</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Paid {{currency()->symbol??''}}</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Due {{currency()->symbol??''}}</th>
                    <th style="color: #ffffff !important; background-color: #4E342E !important;">Status</th>
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
  .text-maroon {
    color: #800000 !important;
  }
  .bg-gradient-maroon {
    background: linear-gradient(45deg, #800000, #A01010);
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
      order: [
        [1, 'desc']
      ],
      ajax: {
        url: "{{ route('backend.admin.orders.index') }}"
      },

      columns: [{
          data: 'DT_RowIndex',
          name: 'DT_RowIndex',
          className: 'pl-4'
        },
        {
          data: 'saleId',
          name: 'saleId',
          className: 'font-weight-bold'
        },
        {
          data: 'customer',
          name: 'customer'
        },
        {
          data: 'item',
          name: 'item'
        },
        {
          data: 'sub_total',
          name: 'sub_total'
        },
        {
          data: 'discount',
          name: 'discount'
        },
        {
          data: 'total',
          name: 'total',
          className: 'font-weight-bold text-maroon'
        }, 
         {
          data: 'paid',
          name: 'paid'
        },
         {
          data: 'due',
          name: 'due'
        },
        {
          data: 'status',
          name: 'status'
        },
        {
          data: 'action',
          name: 'action',
          className: 'text-right pr-4'
        },
      ],
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search sales...",
        lengthMenu: "_MENU_ per page",
        paginate: {
          previous: '<i class="fas fa-chevron-left"></i>',
          next: '<i class="fas fa-chevron-right"></i>'
        }
      }
    });

    $('.dataTables_filter input').addClass('form-control form-control-sm border bg-light px-3').css('border-radius', '20px');
    $('.dataTables_length select').addClass('form-control form-control-sm border bg-light').css('border-radius', '10px');
  });
  
  // Barcode scanner function
  function searchOrderByBarcode() {
    const input = document.getElementById('order-barcode-input').value.trim();
    if (!input) {
      alert('Please scan or enter a barcode');
      return;
    }
    
    // Extract order ID from barcode format: ORD-00000123 or just the number
    let orderId;
    if (input.toUpperCase().startsWith('ORD-')) {
      orderId = parseInt(input.substring(4), 10);
    } else if (!isNaN(parseInt(input, 10))) {
      orderId = parseInt(input, 10);
    } else {
      alert('Invalid barcode format. Expected: ORD-00000123');
      return;
    }
    
    if (orderId && orderId > 0) {
      // Redirect to the order invoice
      window.location.href = '/admin/orders/pos-invoice/' + orderId;
    } else {
      alert('Invalid order ID');
    }
  }
  
  // Listen for Enter key on barcode input
  document.getElementById('order-barcode-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      searchOrderByBarcode();
    }
  });
  
  // Refund Modal Functions
  function openRefundModal(orderId) {
    $.get('/admin/refunds/create/' + orderId, function(html) {
      $('#refundModalContent').html(html);
      $('#refundModal').modal('show');
    }).fail(function(xhr) {
      alert('Error: ' + (xhr.responseJSON?.error || 'Failed to load refund form'));
    });
  }
  // POS Invoice Modal Function
  function openPosInvoice(orderId) {
      const url = "{{ url('admin/orders/pos-invoice') }}/" + orderId;
      openReceiptModal(url);
  }

  function openReceiptModal(url) {
      const frame = document.getElementById('receiptFrame');
      const loader = document.getElementById('receiptLoader');
      
      loader.style.display = 'flex';
      frame.style.visibility = 'hidden';
      frame.src = 'about:blank';
      
      $('#receiptModal').modal('show');
      
      frame.onload = function() {
          if (frame.contentWindow.location.href !== "about:blank") {
              setTimeout(finalizeReceiptLoad, 100);
          }
      };

      const cacheBuster = url.indexOf('?') !== -1 ? '&_v=' : '?_v=';
      frame.src = url + cacheBuster + Date.now();
      setTimeout(finalizeReceiptLoad, 2500);
  }

  function finalizeReceiptLoad() {
      const loader = document.getElementById('receiptLoader');
      const frame = document.getElementById('receiptFrame');
      if (loader) loader.style.setProperty('display', 'none', 'important');
      if (frame) {
          frame.style.visibility = 'visible';
          frame.style.display = 'block';
      }
  }

  function printReceiptFrame() {
      const frame = document.getElementById('receiptFrame');
      if (frame && frame.contentWindow) {
          frame.contentWindow.focus();
          frame.contentWindow.print();
      }
  }

  window.addEventListener('message', function(e) {
      if(e.data === 'receipt-loaded') finalizeReceiptLoad();
      if(e.data === 'close-modal') $('#receiptModal').modal('hide');
  });
</script>

<!-- POS Receipt Modal: Premium Maroon Theme -->
<div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg" style="border-radius: 12px; overflow: hidden; border: none;">
            <div class="modal-header text-white p-2 d-flex justify-content-between align-items-center" style="background: #800000;">
                <h5 class="modal-title m-0 ml-2" style="font-size: 1.1rem; font-weight: 600;"><i class="fas fa-receipt mr-2"></i> Invoice Preview</h5>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-light mr-2 font-weight-bold shadow-sm px-3" onclick="printReceiptFrame()">
                        <i class="fas fa-print mr-1"></i> Print
                    </button>
                    <button type="button" class="btn btn-sm btn-danger font-weight-bold shadow-sm px-3" style="background: #dc3545;" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Close
                    </button>
                </div>
            </div>
            <div class="modal-body p-0 position-relative" style="height: 650px; background: #fff;">
                 <div id="receiptLoader" class="d-flex flex-column justify-content-center align-items-center w-100 h-100 position-absolute" 
                      style="top:0; left:0; z-index:999; background:#fff;">
                      <div class="spinner-border text-maroon" role="status" style="color: #800000; width: 3rem; height: 3rem; border-width: 0.25em;"></div>
                      <span class="mt-3 font-weight-bold text-dark" style="font-size: 1.1rem;">Generating Receipt...</span>
                 </div>
                 <iframe id="receiptFrame" name="receiptFrame" src="about:blank" 
                         style="width:100%; height:100%; border:none; display:block; visibility: hidden;" 
                         scrolling="yes"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content" id="refundModalContent">
      <!-- Content loaded via AJAX -->
    </div>
  </div>
</div>
@endpush