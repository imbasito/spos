import React, { useEffect, useRef, useState } from 'react';
import Swal from 'sweetalert2';

const PaymentModal = ({ show, total, onConfirm, onCancel, defaultMethod = 'cash' }) => {
    if (!show) return null;

    const [paidAmount, setPaidAmount] = useState('');
    const [paymentMethod, setPaymentMethod] = useState(defaultMethod);
    const [transactionId, setTransactionId] = useState('');
    const inputRef = useRef(null);

    const [isReady, setIsReady] = useState(false);

    // Auto-focus input when modal opens and pre-fill total
    useEffect(() => {
        if (show) {
            setTimeout(() => inputRef.current?.focus(), 100);
            setPaidAmount(total); // Pre-fill with exact amount
            
            // Safety Buffer: Prevent "Double Enter" from checkout screen bypassing the modal
            setIsReady(false);
            const timer = setTimeout(() => setIsReady(true), 600); 
            return () => clearTimeout(timer);
        }
    }, [show, total]);

    // Handle Keyboard Shortcuts
    useEffect(() => {
        if (!show) return;

        const handleKeyDown = (e) => {
            if (!show) return; // DOUBLE SAFETY: Ensure modal is truly open
            if (e.key === 'Escape') onCancel();
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                
                // Only allow Confirm if the modal has been open for > 600ms
                if (isReady) {
                    handleConfirm();
                }
            }
            if (e.key === 'F1') setPaymentMethod('cash');
            if (e.key === 'F2') setPaymentMethod('card');
            if (e.key === 'F3') setPaymentMethod('online');
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [show, paidAmount, paymentMethod, transactionId, isReady]); 

    const [confirming, setConfirming] = useState(false);

    const handleConfirm = () => {
        if (confirming) return;

        // Validation: Check if input is empty string
        if (paidAmount === '' || paidAmount === null || paidAmount === undefined) {
             const input = document.querySelector('input[type="number"]'); 
             if(input) input.focus();
             
             Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: 'Please enter received amount',
                showConfirmButton: false,
                timer: 1500
             });
             return;
        }

        setConfirming(true);
        const cleanPaid = parseFloat(paidAmount || 0);
        onConfirm({ 
            paid: cleanPaid, 
            method: paymentMethod, 
            trxId: transactionId 
        });
    };

    const change = (parseFloat(paidAmount || 0) - total).toFixed(2);
    const due = (total - parseFloat(paidAmount || 0)).toFixed(2);
    const showChange = parseFloat(paidAmount) > total;
    // Show Due if:
    // 1. Explicitly calculated Due is positive
    // 2. OR Input is empty/zero (as requested: "agar khali bhi hoga tab bhi... due main jaega")
    const showDue = (parseFloat(paidAmount || 0) < total) || !paidAmount;

    return (
        <div className="modal-backdrop d-flex justify-content-center align-items-center" style={{ zIndex: 1050, position: 'fixed', top: 0, left: 0, width: '100%', height: '100%', backgroundColor: 'rgba(0,0,0,0.4)' }}>
            <div className="professional-modal-content p-0 shadow-deep" style={{ width: '600px', maxWidth: '95%', borderRadius: 'var(--radius-md)', overflow: 'hidden', border: 'var(--apple-border)' }}>
                
                {/* Header - Brand Maroon */}
                <div className="bg-gradient-maroon text-white p-3 d-flex justify-content-between align-items-center shadow-sm">
                    <h4 className="m-0 font-weight-bold" style={{ letterSpacing: '0.5px' }}><i className="fas fa-cash-register mr-2"></i> Finalize Payment</h4>
                    <button onClick={onCancel} className="btn btn-sm btn-outline-light border-0"><i className="fas fa-times fa-lg"></i></button>
                </div>


                <div className="p-4 bg-white">
                    {/* Big Total Display */}
                    <div className="text-center mb-4">
                        <small className="text-muted text-uppercase font-weight-bold">Total Payable Amount</small>
                        <h1 className="display-4 font-weight-bolder text-dark m-0">
                            {parseInt(total) === total ? parseInt(total) : total}
                            <small className="text-muted" style={{ fontSize: '0.4em', verticalAlign: 'top' }}>{window.posSettings?.currencySymbol ?? 'PKR'}</small>
                        </h1>
                    </div>

                    <div className="row">
                        <div className="col-12 mb-3">
                            <label className="font-weight-bold">Payment Method</label>
                            <div className="btn-group w-100" role="group">
                                <button type="button" className={`btn ${paymentMethod === 'cash' ? 'btn-success' : 'btn-outline-secondary'}`} onClick={() => setPaymentMethod('cash')}>
                                    <i className="fas fa-money-bill-wave mr-2"></i> Cash (F1)
                                </button>
                                <button type="button" className={`btn ${paymentMethod === 'card' ? 'btn-info' : 'btn-outline-secondary'}`} onClick={() => {
                                    setPaymentMethod('card');
                                    setPaidAmount(total); // Force Full Payment for Card
                                }}>
                                    <i className="fas fa-credit-card mr-2"></i> Card (F2)
                                </button>
                                <button type="button" className={`btn ${paymentMethod === 'online' ? 'btn-warning' : 'btn-outline-secondary'}`} onClick={() => {
                                    setPaymentMethod('online');
                                    setPaidAmount(total); // Force Full Payment for Online
                                }}>
                                    <i className="fas fa-mobile-alt mr-2"></i> Online (F3)
                                </button>
                            </div>
                        </div>

                        {/* Amount Input */}
                        <div className="col-12 mb-3">
                            <label className="font-weight-bold">Received Amount</label>
                            <div className="input-group input-group-lg">
                                <div className="input-group-prepend">
                                    <span className="input-group-text bg-white border-right-0"><i className="fas fa-coins text-warning"></i></span>
                                </div>
                                <input 
                                    ref={inputRef}
                                    type="number" 
                                    className="form-control border-left-0 pl-0 font-weight-bold" 
                                    style={{ fontSize: '1.5rem' }}
                                    placeholder="0.00"
                                    value={paidAmount}
                                    onFocus={(e) => e.target.select()}
                                    onChange={(e) => setPaidAmount(e.target.value)}
                                />
                            </div>
                        </div>

                        {/* Transaction ID (if not Cash) */}
                        {paymentMethod !== 'cash' && (
                            <div className="col-12 mb-3 animated fadeIn">
                                <label className="text-muted font-weight-bold">Transaction / Ref ID</label>
                                <input 
                                    type="text" 
                                    className="form-control form-control-lg text-center font-weight-bold" 
                                    placeholder="e.g. TRX-12345 (Optional)" 
                                    value={transactionId}
                                    onChange={(e) => setTransactionId(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') handleConfirm();
                                    }}
                                />
                            </div>
                        )}

                    </div>

                    {/* Normalized Size and Solid Colors */}
                    {showChange && paymentMethod === 'cash' && (
                        <div className="alert alert-success text-center mt-3 mb-0 animated fadeInUp p-3" style={{ opacity: 1, border: '2px solid #28a745', backgroundColor: '#d4edda' }}>
                            <span className="text-uppercase font-weight-bold d-block text-muted small mb-1">Return Change</span>
                            <h4 className="m-0 font-weight-bold" style={{ color: '#155724', fontSize: '1.5rem' }}>{change}</h4>
                        </div>
                    )}

                    {/* Due Display */}
                    {showDue && (
                        <div className="alert alert-danger text-center mt-3 mb-0 animated fadeInUp p-3" style={{ opacity: 1, border: '2px solid #dc3545', backgroundColor: '#f8d7da' }}>
                            <span className="text-uppercase font-weight-bold d-block text-muted small mb-1">Balance Due</span>
                            <h4 className="m-0 font-weight-bold" style={{ color: '#721c24', fontSize: '1.5rem' }}>{parseFloat(paidAmount || 0) > 0 ? due : total}</h4>
                        </div>
                    )}
                </div>

                {/* Footer Actions */}
                <div className="modal-footer bg-light p-3">
                    <button type="button" className="btn btn-lg btn-secondary px-4" onClick={onCancel}>Cancel (Esc)</button>
                    <button type="button" className="btn btn-lg bg-gradient-maroon text-white px-5 font-weight-bold shadow" onClick={handleConfirm}>
                        <i className="fas fa-check-circle mr-2"></i> CONFIRM & PRINT (Enter)
                    </button>
                </div>
            </div>
        </div>
    );
};

export default PaymentModal;
