@extends('backend.master')

@section('title', 'Sale')

@section('content')
<div class="card">
  <div class="card-body p-2 p-md-4 pt-0">
    <!-- Barcode Scanner Input -->
    <div class="row mb-3">
      <div class="col-md-4">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text bg-primary text-white">
              <i class="fas fa-barcode"></i>
            </span>
          </div>
          <input 
            type="text" 
            id="order-barcode-input" 
            class="form-control" 
            placeholder="Scan receipt barcode (ORD-00000123)"
            autofocus
          >
          <div class="input-group-append">
            <button class="btn btn-primary" type="button" onclick="searchOrderByBarcode()">
              <i class="fas fa-search"></i> Find
            </button>
          </div>
        </div>
        <small class="text-muted">Scan or type order barcode to view invoice</small>
      </div>
    </div>
    
    <div class="row g-4">
      <div class="col-md-12">
        <div class="card-body table-responsive p-0" id="table_data">
          <table id="datatables" class="table table-hover">
            <thead>
              <tr>
                <th data-orderable="false">#</th>
                <th>SaleId</th>
                <th>Customer</th>
                <th>Item</th>
                <th>Sub Total {{currency()->symbol??''}}</th>
                <th>Discount {{currency()->symbol??''}}</th>
                <th>Total {{currency()->symbol??''}}</th>
                <th>Paid {{currency()->symbol??''}}</th>
                <th>Due {{currency()->symbol??''}}</th>
                <th>Status</th>
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
      order: [
        [1, 'desc']
      ],
      ajax: {
        url: "{{ route('backend.admin.orders.index') }}"
      },

      columns: [{
          data: 'DT_RowIndex',
          name: 'DT_RowIndex'
        },
        {
          data: 'saleId',
          name: 'saleId'
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
          name: 'total'
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
          name: 'action'
        },
      ]
    });
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