<div class="modal-header text-white" style="background: #800000; border-bottom: 2px solid rgba(0,0,0,0.1);">
    <h5 class="modal-title font-weight-bold"><i class="fas fa-undo mr-2"></i> Process Refund - Order #{{ $order->id }}</h5>
    <button type="button" class="close text-white opacity-10" data-dismiss="modal" style="text-shadow: none;">&times;</button>
</div>
<div class="modal-body p-4" style="background: #f8f9fa;">
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white shadow-sm" style="border-radius: 8px; border-left: 5px solid #800000;">
        <div>
            <div class="text-muted small text-uppercase font-weight-bold">Customer</div>
            <div class="font-weight-bold text-dark" style="font-size: 1.1rem;">{{ $order->customer->name ?? 'Walking Customer' }}</div>
        </div>
        <div class="text-right">
            <div class="text-muted small text-uppercase font-weight-bold">Order Total</div>
            <div class="font-weight-bold text-maroon" style="font-size: 1.2rem; color: #800000;">{{ number_format($order->total, 2) }}</div>
        </div>
    </div>

    <form id="refund-form">
        @csrf
        <input type="hidden" name="order_id" value="{{ $order->id }}">
        
        <div class="table-responsive bg-white shadow-sm" style="border-radius: 8px; overflow: hidden;">
            <table class="table table-sm table-hover mb-0">
                <thead style="background: #f1f1f1;">
                    <tr>
                        <th class="py-3 px-3" style="width: 40px;"><input type="checkbox" id="select-all" style="transform: scale(1.2);"></th>
                        <th class="py-3">Product Description</th>
                        <th class="py-3 text-center" style="width: 80px;">Sold</th>
                        <th class="py-3 text-center" style="width: 80px;">Ret.</th>
                        <th class="py-3 text-center" style="width: 140px;">Return Qty</th>
                        <th class="py-3 text-right pr-3" style="width: 120px;">Refund Amt</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->products as $item)
                    @php 
                        $isAvailable = $item->available_qty > 0;
                        // Calculate unit price with proper precision (considering order discount)
                        $adjustmentFactor = ($order->sub_total > 0) ? ($order->total / $order->sub_total) : 1;
                        $unitPrice = round(($item->total / $item->quantity) * $adjustmentFactor, 2);
                    @endphp
                    <tr data-item-id="{{ $item->id }}" class="{{ !$isAvailable ? 'bg-light text-muted' : '' }}" style="transition: all 0.2s;">
                        <td class="align-middle px-3">
                            <input type="checkbox" class="item-checkbox" 
                                   data-order-product-id="{{ $item->id }}"
                                   data-max-qty="{{ number_format($item->available_qty, 3, '.', '') }}"
                                   data-unit-price="{{ number_format($unitPrice, 2, '.', '') }}"
                                   {{ !$isAvailable ? 'disabled' : '' }}
                                   style="transform: scale(1.2);">
                        </td>
                        <td class="align-middle">
                            <div class="font-weight-bold text-dark">{{ $item->product->name ?? 'Deleted Product' }}</div>
                            <small class="text-muted">Unit Price: {{ number_format($unitPrice, 2) }}</small>
                        </td>
                        <td class="text-center align-middle font-weight-bold">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-center align-middle text-danger">{{ number_format($item->returned_qty, 2) }}</td>
                        <td class="align-middle">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control form-control-sm return-qty text-center font-weight-bold"
                                       min="0" max="{{ number_format($item->available_qty, 3, '.', '') }}" step="0.001" value="0"
                                       disabled data-order-product-id="{{ $item->id }}"
                                       style="border-radius: 4px; border: 1px solid #ced4da;">
                            </div>
                        </td>
                        <td class="text-right align-middle pr-3">
                            <span class="refund-amount font-weight-bold text-dark">0.00</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot style="background: #fdfdfd; border-top: 2px solid #eee;">
                    <tr>
                        <td colspan="5" class="text-right py-3 pr-3 font-weight-bold text-muted">Estimated Total Refund:</td>
                        <td class="text-right py-3 pr-3">
                            <div class="h5 mb-0 font-weight-bold" id="total-refund" style="color: #d9534f;">0.00</div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </form>
</div>
<div class="modal-footer bg-white py-3 pr-4" style="border-top: 1px solid #eee;">
    <button type="button" class="btn btn-outline-secondary px-4 font-weight-bold" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-danger px-4 font-weight-bold shadow-sm" style="background: #800000; border: none; min-width: 180px;" id="confirm-refund-btn" onclick="submitRefund()">
        <i class="fas fa-check-circle mr-2"></i> Confirm Refund
    </button>
</div>

<style>
    .item-checkbox:hover { cursor: pointer; }
    .tr-active { background-color: #fff9f9 !important; }
</style>

<script>
$(function() {
    // Select All
    $('#select-all').on('change', function() {
        $('.item-checkbox:not(:disabled)').prop('checked', this.checked).trigger('change');
    });

    // Item checkbox change
    $('.item-checkbox').on('change', function() {
        const row = $(this).closest('tr');
        const qtyInput = row.find('.return-qty');
        const maxQty = parseFloat($(this).data('max-qty'));
        
        if (this.checked) {
            qtyInput.prop('disabled', false).val(maxQty);
            row.addClass('tr-active');
        } else {
            qtyInput.prop('disabled', true).val(0);
            row.removeClass('tr-active');
        }
        updateRefundAmount(row);
        updateTotalRefund();
    });

    // Quantity change
    $('.return-qty').on('input change', function() {
        const row = $(this).closest('tr');
        const max = parseFloat($(this).attr('max'));
        const val = parseFloat($(this).val());
        
        if(val > max) $(this).val(max);
        
        updateRefundAmount(row);
        updateTotalRefund();
    });

    function updateRefundAmount(row) {
        const checkbox = row.find('.item-checkbox');
        const qtyInput = row.find('.return-qty');
        const unitPrice = parseFloat(checkbox.data('unit-price'));
        const qty = parseFloat(qtyInput.val()) || 0;
        const refund = Math.round(qty * unitPrice * 100) / 100; // Precise 2-decimal rounding
        row.find('.refund-amount').text(refund.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }

    function updateTotalRefund() {
        let total = 0;
        $('.item-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const unitPrice = parseFloat($(this).data('unit-price'));
            const qty = parseFloat(row.find('.return-qty').val()) || 0;
            total += Math.round(qty * unitPrice * 100) / 100;
        });
        $('#total-refund').text(total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }

    window.submitRefund = function() {
        const items = [];
        let totalRefundAmount = 0;
        
        $('.item-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const orderProductId = $(this).data('order-product-id');
            const qty = parseFloat(row.find('.return-qty').val()) || 0;
            const maxQty = parseFloat($(this).data('max-qty'));
            
            if (qty > 0) {
                if (qty > maxQty) {
                    Swal.fire({ icon: 'error', title: 'Invalid Quantity', text: 'Return quantity exceeds available quantity for: ' + row.find('.font-weight-bold').text() });
                    return false;
                }
                items.push({ order_product_id: orderProductId, quantity: qty });
                const unitPrice = parseFloat($(this).data('unit-price'));
                totalRefundAmount += Math.round(qty * unitPrice * 100) / 100;
            }
        });

        if (items.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Selection Empty', text: 'Please select at least one item to refund.' });
            return;
        }
        
        // Calculate cash back vs debt clearance
        const orderDue = {{ $order->due }};
        let cashBackAmount = 0;
        let debtClearance = 0;
        
        if (orderDue > 0) {
            if (totalRefundAmount <= orderDue) {
                debtClearance = totalRefundAmount;
                cashBackAmount = 0;
            } else {
                debtClearance = orderDue;
                cashBackAmount = totalRefundAmount - orderDue;
            }
        } else {
            cashBackAmount = totalRefundAmount;
        }
        
        // Show confirmation with cash back details
        let confirmMsg = 'Total Refund: {{ currency()->symbol }}' + totalRefundAmount.toFixed(2);
        if (debtClearance > 0) {
            confirmMsg += '\n\nDebt Cleared: {{ currency()->symbol }}' + debtClearance.toFixed(2);
        }
        if (cashBackAmount > 0) {
            confirmMsg += '\nCash to Return: {{ currency()->symbol }}' + cashBackAmount.toFixed(2);
        }
        
        Swal.fire({
            icon: 'question',
            title: 'Confirm Refund',
            html: confirmMsg.replace(/\n/g, '<br>'),
            showCancelButton: true,
            confirmButtonText: 'Yes, Process Refund',
            confirmButtonColor: '#800000'
        }).then((result) => {
            if (!result.isConfirmed) return;

            const formData = {
                _token: '{{ csrf_token() }}',
                order_id: {{ $order->id }},
                items: items
            };

            // UI Loading State
            const btn = $('#confirm-refund-btn');
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        $.ajax({
            url: '{{ route("backend.admin.refunds.store") }}',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function(response) {
                $('#refundModal').modal('hide');
                
                // Show success and open receipt
                Swal.fire({
                    icon: 'success',
                    title: 'Refund Processed',
                    text: 'The refund has been recorded successfully.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    const url = '{{ url("admin/refunds") }}/' + response.return_id + '/receipt';
                    openRefundReceipt(url);
                    if (window.table) table.ajax.reload();
                    else if ($('#datatables').length) $('#datatables').DataTable().ajax.reload();
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                const errorMsg = xhr.responseJSON?.error || 'Failed to process refund.';
                Swal.fire({ icon: 'error', title: 'Error', text: errorMsg });
            }
        });
        });
    }
});
</script>
