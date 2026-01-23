@extends('backend.master')

@section('title', 'Products')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">
      <div class="card-header bg-gradient-maroon py-3 d-flex align-items-center">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-boxes mr-2"></i> Product Catalog
        </h3>
        @can('product_create')
        <a href="{{ route('backend.admin.products.create') }}" class="btn btn-light btn-md px-4 ml-auto shadow-sm hover-lift font-weight-bold text-maroon">
          <i class="fas fa-plus-circle mr-1"></i> Add New Product
        </a>
        @endcan
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table id="datatables" class="table table-hover mb-0 custom-premium-table">
            <thead class="bg-dark text-white text-uppercase font-weight-bold small">
              <tr>
                <th width="50" class="pl-4 text-white" style="background-color: #4E342E !important;">#</th>
                <th width="80" class="text-white" style="background-color: #4E342E !important;">Image</th>
                <th class="text-white" style="background-color: #4E342E !important;">Product Name</th>
                <th class="text-white" style="background-color: #4E342E !important;">Price ({{currency()->symbol??''}})</th>
                <th class="text-white" style="background-color: #4E342E !important;">Stock</th>
                <th class="text-white" style="background-color: #4E342E !important;">Created</th>
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
  .img-thumb {
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #eee;
  }
</style>
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
      order: [[5, 'desc']],
      ajax: {
        url: "{{ route('backend.admin.products.index') }}"
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'pl-4' },
        { data: 'image', name: 'image', orderable: false, searchable: false },
        { data: 'name', name: 'name', className: 'font-weight-bold' },
        { data: 'price', name: 'price' },
        { data: 'quantity', name: 'quantity' },
        { data: 'created_at', name: 'created_at' },
        { data: 'status', name: 'status' },
        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right pr-4' },
      ],
      dom: '<"p-3 d-flex justify-content-between align-items-center"lf>t<"p-3 d-flex justify-content-between align-items-center"ip>',
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search Products...",
        lengthMenu: "_MENU_ per page",
        paginate: {
          previous: '<i class="fas fa-chevron-left"></i>',
          next: '<i class="fas fa-chevron-right"></i>'
        }
      }
    });

    $('.dataTables_filter input').addClass('form-control form-control-sm border-0 bg-light px-3').css('border-radius', '20px');
    $('.dataTables_length select').addClass('form-control form-control-sm border-0 bg-light').css('border-radius', '10px');

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