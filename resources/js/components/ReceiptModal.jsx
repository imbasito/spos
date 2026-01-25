import React, { useEffect, useRef, useState } from 'react';
import toast from 'react-hot-toast';
import axios from 'axios';

const ReceiptModal = ({ show, url, onClose }) => {
    if (!show || !url) return null;

    const iframeRef = useRef(null);
    const [loading, setLoading] = useState(true);

    // Handle Keyboard (Esc to close)
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') onClose();
        };

        const handleMessage = (e) => {
            if (e.data === 'close-modal') {
                onClose();
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        window.addEventListener('message', handleMessage);
        
        return () => {
            window.removeEventListener('keydown', handleKeyDown);
            window.removeEventListener('message', handleMessage);
        };
    }, []);

    // Printing Logic (via iframe content window or Electron)
    const handlePrint = async () => {
        if (iframeRef.current) {
             // 1. Electron Silent Print (PDF Save / Thermal)
            if (window.electron && window.electron.printSilent) {
                // Determine absolute URL (url prop might be relative)
                let targetUrl = url;
                if (!url.startsWith('http')) {
                    const { protocol, host } = window.location;
                    targetUrl = `${protocol}//${host}${url}`;
                }

                // Show feedback
                const toastId = toast.loading("Processing Receipt...");
                
                try {
                    // NEW STRATEGY: HEADLESS JSON DATA
                    // Convert "pos-invoice/123" url to "receipt-details/123"
                    const invoiceMatch = targetUrl.match(/pos-invoice\/(\d+)/);
                    if (invoiceMatch && invoiceMatch[1]) {
                        const orderId = invoiceMatch[1];
                        const apiUrl = `/admin/orders/receipt-details/${orderId}`;
                        
                        const jsonResponse = await axios.get(apiUrl);
                        const jsonData = jsonResponse.data?.data; // { success: true, data: {...} }

                        if (!jsonData) throw new Error("Empty receipt data received");

                        // Send JSON to Electron Headless Renderer
                        const response = await window.electron.printSilent(
                            targetUrl, 
                            window.posSettings?.receiptPrinter, 
                            null, // htmlContent
                            jsonData // jsonData
                        );
                        
                        if (response.success) {
                            toast.success("Receipt Sent (Headless Mode)", { id: toastId });
                        } else {
                            toast.error("Print Failed", { id: toastId });
                        }
                    } else {
                        // Fallback: Use HTML Injection if URL doesn't match expected pattern
                        // ... (Legacy code if needed, or error out)
                        throw new Error("Invalid Invoice URL format for Headless Print");
                    }

                } catch (e) {
                    console.error(e);
                    toast.error("Error: " + e.message, { id: toastId });
                }
            } else {
                // 2. Browser Fallback
                if (iframeRef.current.contentWindow) {
                    iframeRef.current.contentWindow.print();
                }
            }
        }
    };

    return (
        <div className="modal fade show d-block" tabIndex="-1" role="dialog" aria-hidden="true" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
            <div className="modal-dialog modal-dialog-centered" role="document">
                <div className="modal-content shadow-lg" style={{ borderRadius: '12px', overflow: 'hidden', border: 'none' }}>
                    
                    {/* Header */}
                    <div className="modal-header text-white p-2 d-flex justify-content-between align-items-center" style={{ background: '#800000' }}>
                        <h5 className="modal-title m-0 ml-2" style={{ fontSize: '1.1rem', fontWeight: 600 }}>
                            <i className="fas fa-receipt mr-2"></i> Invoice Preview
                        </h5>
                        <div className="d-flex align-items-center">
                            <button 
                                type="button" 
                                className="btn btn-sm btn-light mr-2 font-weight-bold shadow-sm px-3" 
                                onClick={handlePrint}
                            >
                                <i className="fas fa-print mr-1"></i> Print
                            </button>
                            <button 
                                type="button" 
                                className="btn btn-sm btn-danger font-weight-bold shadow-sm px-3" 
                                style={{ background: '#dc3545' }} 
                                onClick={(e) => {
                                    e.preventDefault();
                                    onClose();
                                }}
                            >
                                <i className="fas fa-times mr-1"></i> Close
                            </button>
                        </div>
                    </div>

                    {/* Body */}
                    <div className="modal-body p-0 position-relative" style={{ height: '650px', background: '#fff' }}>
                        {loading && (
                             <div className="d-flex flex-column justify-content-center align-items-center w-100 h-100 position-absolute" 
                                  style={{ top: 0, left: 0, zIndex: 999, background: '#fff' }}>
                                  <div className="spinner-border" role="status" style={{ color: '#800000', width: '3rem', height: '3rem', borderWidth: '0.25em' }}></div>
                                  <span className="mt-3 font-weight-bold text-dark" style={{ fontSize: '1.1rem' }}>Generating Receipt...</span>
                             </div>
                        )}
                        <iframe 
                            ref={iframeRef}
                            src={url} 
                            style={{ width: '100%', height: '100%', border: 'none', display: 'block', visibility: loading ? 'hidden' : 'visible' }}
                            scrolling="yes"
                            onLoad={() => {
                                setLoading(false);
                                if(iframeRef.current) {
                                    iframeRef.current.focus();
                                    iframeRef.current.contentWindow.focus();
                                }
                            }}
                            title="Receipt Preview"
                        />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ReceiptModal;
