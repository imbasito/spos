@extends('backend.master')

@section('title', 'Products')

@section('content')
<div class="card">

  @can('product_create')
  <div class="mt-n5 mb-3 d-flex justify-content-end">
    <a href="{{ route('backend.admin.products.create') }}" class="btn bg-gradient-primary">
      <i class="fas fa-plus-circle"></i>
      Add New
    </a>
  </div>
  @endcan
  <div class="card-body p-2 p-md-4 pt-0">
    <div class="row g-4">
      <div class="col-md-12">
        <div class="card-body table-responsive p-0" id="table_data">
          <table id="datatables" class="table table-hover">
            <thead>
              <tr>
                <th data-orderable="false">#</th>
                <th></th>
                <th>Name</th>
                <th>Price{{currency()->symbol??''}}</th>
                <th>Stock</th>
                <th>Created</th>
                <th>Status</th>
                <th data-orderable="false">Action</th>
              </tr>
            </thead>
          </table>
          <!-- Pagination Links -->
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

<!-- Barcode Options Modal -->
<div class="modal fade" id="barcodeModal" tabindex="-1" role="dialog" aria-labelledby="barcodeModalLabel" aria-hidden="true" style="display: none;">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="barcodeModalLabel">Print Barcode Options</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="barcodeForm">
            <input type="hidden" id="modalBarcode">
            <input type="hidden" id="modalLabel">
            
            <div class="form-group">
                <label>MFG Date (Optional)</label>
                <input type="date" class="form-control" id="mfgDate">
            </div>
            <div class="form-group">
                <label>EXP Date (Optional)</label>
                <input type="date" class="form-control" id="expDate">
            </div>
            <div class="form-group">
                <label>Label Size</label>
                <select class="form-control" id="labelSize">
                    <option value="large" selected>Large (50mm)</option>
                    <option value="small">Small (38mm)</option>
                </select>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" id="showPrice">
                <label class="form-check-label" for="showPrice">Show Price</label>
            </div>
            
            <div class="form-group" id="priceInputGroup" style="display: none;">
                <label>Price</label>
                <input type="number" class="form-control" id="visiblePrice">
            </div>
        </form>
    </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="printConfirmBtn">Print</button>
      </div>
    </div>
  </div>
</div>


@push('script')

<script type="text/javascript">
  $(function() {
    let table = $('#datatables').DataTable({
      processing: true,
      serverSide: true,
      ordering: true,
      ajax: {
        url: "{{ route('backend.admin.products.index') }}"
      },

      columns: [{
          data: 'DT_RowIndex',
          name: 'DT_RowIndex'
        },
        {
          data: 'image',
          name: 'image'
        },
        {
          data: 'name',
          name: 'name'
        },
        {
          data: 'price',
          name: 'price'
        },
        {
          data: 'quantity',
          name: 'quantity'
        },
        {
          data: 'created_at',
          name: 'created_at'
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

    // Handle Print Tag Click
    $(document).on('click', '.print-barcode-btn', function() {
        let name = $(this).data('name');
        let sku = $(this).data('sku');
        let price = $(this).data('price');
        
        $('#modalBarcode').val(sku);
        $('#modalLabel').val(name);
        
        // Populate modal
        $('#visiblePrice').val(price);
        $('#showPrice').prop('checked', false);
        $('#priceInputGroup').hide();
        
        $('#barcodeModal').modal('show');
    });

    // Toggle Price Input
    $('#showPrice').change(function() {
        if($(this).is(':checked')) {
            $('#priceInputGroup').slideDown();
        } else {
            $('#priceInputGroup').slideUp();
        }
    });

    // Handle Confirm Print
    $('#printConfirmBtn').click(function() {
        let barcode = $('#modalBarcode').val();
        let label = $('#modalLabel').val();
        let priceVal = $('#visiblePrice').val();
        
        let mfg = $('#mfgDate').val();
        let exp = $('#expDate').val();
        let size = $('#labelSize').val();
        let showPrice = $('#showPrice').is(':checked');
        
        // Only trigger print if validation passes
        
        let url = "{{ route('backend.admin.barcode.print') }}" + 
                  "?barcode=" + encodeURIComponent(barcode) + 
                  "&label=" + encodeURIComponent(label) + 
                  "&size=" + size;
                  
        if(mfg) url += "&mfg=" + encodeURIComponent(mfg);
        if(exp) url += "&exp=" + encodeURIComponent(exp);
        if(showPrice) url += "&price=" + encodeURIComponent(priceVal);
        
        // Professional Silent Print via Electron
        const btn = $(this);
        const originalText = btn.html();
        
        if (window.electron && window.electron.printSilent) {
            const printerName = window.posSettings && window.posSettings.tagPrinter ? window.posSettings.tagPrinter : '';
            
            // Show feedback
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Printing...');
            
            window.electron.printSilent(url, printerName)
                .then(res => {
                    if(res.success) {
                        toastr.success('Label sent to printer');
                        $('#barcodeModal').modal('hide');
                    } else {
                        toastr.error('Print failed: ' + res.error);
                    }
                })
                .catch(err => {
                    toastr.error('Print error: ' + err);
                })
                .finally(() => {
                    btn.prop('disabled', false).html(originalText);
                });
        } else {
            // Fallback for browser
            window.open(url, '_blank', 'width=500,height=400,toolbar=no,scrollbars=yes');
            $('#barcodeModal').modal('hide');
        }
    });
  });
</script>

@endpush