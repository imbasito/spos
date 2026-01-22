<div class="modal-header bg-danger text-white">
    <h5 class="modal-title"><i class="fas fa-undo"></i> Process Refund - Order #{{ $order->id }}</h5>
    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <div class="alert alert-info mb-3">
        <strong>Customer:</strong> {{ $order->customer->name ?? 'Walking Customer' }} |
        <strong>Order Total:</strong> {{ number_format($order->total, 2) }}
    </div>

    <form id="refund-form">
        @csrf
        <input type="hidden" name="order_id" value="{{ $order->id }}">
        
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 30px;"><input type="checkbox" id="select-all"></th>
                        <th>Product</th>
                        <th style="width: 100px;">Purchased</th>
                        <th style="width: 120px;">Return Qty</th>
                        <th style="width: 100px;">Unit Price</th>
                        <th style="width: 100px;">Refund</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->products as $item)
                    <tr data-item-id="{{ $item->id }}">
                        <td>
                            <input type="checkbox" class="item-checkbox" 
                                   data-order-product-id="{{ $item->id }}"
                                   data-max-qty="{{ $item->quantity }}"
                                   data-unit-price="{{ $item->total / $item->quantity }}">
                        </td>
                        <td>
                            <strong>{{ $item->product->name ?? 'Deleted Product' }}</strong>
                            <br><small class="text-muted">SKU: {{ $item->product->sku ?? 'N/A' }}</small>
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm return-qty"
                                   min="0" max="{{ $item->quantity }}" step="0.001" value="0"
                                   disabled data-order-product-id="{{ $item->id }}">
                        </td>
                        <td class="text-right">{{ number_format($item->total / $item->quantity, 2) }}</td>
                        <td class="text-right refund-amount">0.00</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-light">
                        <td colspan="5" class="text-right"><strong>Total Refund:</strong></td>
                        <td class="text-right"><strong id="total-refund">0.00</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="form-group mt-3">
            <label>Reason (Optional)</label>
            <textarea name="reason" class="form-control" rows="2" placeholder="Reason for return..."></textarea>
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-danger" onclick="submitRefund()">
        <i class="fas fa-undo"></i> Process Refund
    </button>
</div>

<script>
// Select All
$('#select-all').on('change', function() {
    $('.item-checkbox').prop('checked', this.checked).trigger('change');
});

// Item checkbox change
$('.item-checkbox').on('change', function() {
    const row = $(this).closest('tr');
    const qtyInput = row.find('.return-qty');
    const maxQty = parseFloat($(this).data('max-qty'));
    
    if (this.checked) {
        qtyInput.prop('disabled', false).val(maxQty);
    } else {
        qtyInput.prop('disabled', true).val(0);
    }
    updateRefundAmount(row);
    updateTotalRefund();
});

// Quantity change
$('.return-qty').on('input', function() {
    const row = $(this).closest('tr');
    updateRefundAmount(row);
    updateTotalRefund();
});

function updateRefundAmount(row) {
    const checkbox = row.find('.item-checkbox');
    const qtyInput = row.find('.return-qty');
    const unitPrice = parseFloat(checkbox.data('unit-price'));
    const qty = parseFloat(qtyInput.val()) || 0;
    const refund = qty * unitPrice;
    row.find('.refund-amount').text(refund.toFixed(2));
}

function updateTotalRefund() {
    let total = 0;
    $('.refund-amount').each(function() {
        total += parseFloat($(this).text()) || 0;
    });
    $('#total-refund').text(total.toFixed(2));
}

function submitRefund() {
    const items = [];
    $('.item-checkbox:checked').each(function() {
        const orderProductId = $(this).data('order-product-id');
        const qty = parseFloat($(this).closest('tr').find('.return-qty').val()) || 0;
        if (qty > 0) {
            items.push({ order_product_id: orderProductId, quantity: qty });
        }
    });

    if (items.length === 0) {
        alert('Please select at least one item to refund');
        return;
    }

    const formData = {
        _token: '{{ csrf_token() }}',
        order_id: {{ $order->id }},
        items: items,
        reason: $('textarea[name="reason"]').val()
    };

    $.ajax({
        url: '{{ route("backend.admin.refunds.store") }}',
        type: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            alert('Refund processed successfully!');
            $('#refundModal').modal('hide');
            // Redirect to receipt
            window.location.href = '/admin/refunds/' + response.return_id + '/receipt';
            // Refresh the orders table
            if (typeof table !== 'undefined') {
                table.ajax.reload();
            } else {
                location.reload();
            }
        },
        error: function(xhr) {
            alert('Error: ' + (xhr.responseJSON?.error || 'Failed to process refund'));
        }
    });
}
</script>
