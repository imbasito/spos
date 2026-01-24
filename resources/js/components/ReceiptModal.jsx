import React, { useEffect, useRef, useState } from 'react';
import toast from 'react-hot-toast';

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
                    const response = await window.electron.printSilent(targetUrl);
                    if (response.success) {
                        toast.success("Receipt Saved to Documents", { id: toastId });
                    } else {
                        toast.error("Print Failed", { id: toastId });
                    }
                } catch (e) {
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
        <div className="professional-blur modal-backdrop d-flex justify-content-center align-items-center" style={{ zIndex: 1060, position: 'fixed', top: 0, left: 0, width: '100%', height: '100%' }}>
            <div className="professional-modal-content" style={{ width: '450px', height: '90vh', display: 'flex', flexDirection: 'column' }}>
                
                {/* Header */}
                <div className="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                    <h5 className="m-0"><i className="fas fa-receipt mr-2"></i> Receipt</h5>
                    <div className="btn-group">
                        <button onClick={handlePrint} className="btn btn-sm btn-info mr-2">
                            <i className="fas fa-print"></i> Print
                        </button>
                        <button onClick={onClose} className="btn btn-sm btn-danger">
                            <i className="fas fa-times"></i> Close (Esc)
                        </button>
                    </div>
                </div>

                {/* Body - Iframe */}
                <div className="flex-grow-1 bg-white position-relative" style={{ overflow: 'hidden' }}>
                    {loading && (
                        <div className="position-absolute w-100 h-100 d-flex justify-content-center align-items-center text-muted">
                            <div className="text-center">
                                <div className="spinner-border text-primary mb-2" role="status"></div>
                                <div>Loading Receipt...</div>
                            </div>
                        </div>
                    )}
                    <iframe 
                        ref={iframeRef}
                        src={url} 
                        className="receipt-iframe w-100 h-100 border-0" 
                        scrolling="yes"
                        style={{ overflow: 'auto' }}                        onLoad={() => {
                            setLoading(false);
                            // Auto-focus the iframe so its internal keyboard shortcuts (Enter=Print) work immediately
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
    );
};

export default ReceiptModal;
