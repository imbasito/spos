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
</script>

<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content" id="refundModalContent">
      <!-- Content loaded via AJAX -->
    </div>
  </div>
</div>
@endpush