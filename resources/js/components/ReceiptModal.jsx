import React, { useEffect, useRef, useState } from 'react';

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
    const handlePrint = () => {
        if (iframeRef.current && iframeRef.current.contentWindow) {
            // Check if we are in Electron env with our custom printer
            if (window.electron && window.electron.printSilent) {
                // For silent print, we might just re-trigger the URL or let the User use the iframe's print
                // But typically the Receipt URL page itself might have auto-print JS.
                // Let's rely on the user clicking "Print" inside the iframe or using this button to trigger browser print
                iframeRef.current.contentWindow.print();
            } else {
                iframeRef.current.contentWindow.print();
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
