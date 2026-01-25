import React, { useState, useEffect } from 'react';

const PrinterStatus = () => {
    const [status, setStatus] = useState('checking'); // checking, online, offline, error
    const [message, setMessage] = useState('Checking printer...');
    
    // Get config from global settings (injected via Blade)
    const targetPrinterName = window.posSettings?.receiptPrinter;

    useEffect(() => {
        let isMounted = true;

        const checkPrinter = async () => {
            if (!window.electron || !window.electron.getPrinters) {
                if (isMounted) {
                    setStatus('error');
                    setMessage('Desktop App Required');
                }
                return;
            }

            try {
                const printers = await window.electron.getPrinters();
                
                if (printers.length === 0) {
                    if (isMounted) {
                        setStatus('error');
                        setMessage('No Printers Found');
                    }
                    return;
                }

                if (!targetPrinterName) {
                    // No specific printer set, just check if ANY default is online
                    const defaultPrinter = printers.find(p => p.isDefault) || printers[0];
                    if (isMounted) {
                        if (defaultPrinter.status === 0) {
                             setStatus('online');
                             setMessage('Printer Online (Default)');
                        } else {
                             setStatus('offline');
                             setMessage('Printer Offline');
                        }
                    }
                } else {
                    // Check specific target printer
                    const printer = printers.find(p => p.name === targetPrinterName);
                    
                    if (isMounted) {
                        if (printer) {
                            // Status 0 is usually 'Good/Idle'. Non-zero often means error/offline/busy on Windows.
                            // Note: Electron status codes vary by OS. On Windows, 0 is often OK.
                            if (printer.status === 0) {
                                setStatus('online');
                                setMessage('Printer Online');
                            } else {
                                setStatus('offline');
                                setMessage('Printer Check Cable');
                            }
                        } else {
                            setStatus('offline');
                            setMessage('Printer Not Found');
                        }
                    }
                }

            } catch (error) {
                console.error("Printer Check Failed", error);
                if (isMounted) {
                    setStatus('error');
                    setMessage('Service Error');
                }
            }
        };

        // Initial check
        checkPrinter();

        // Poll every 10 seconds
        const intervalId = setInterval(checkPrinter, 10000);

        return () => {
            isMounted = false;
            clearInterval(intervalId);
        };
    }, [targetPrinterName]);

    // Render Logic
    if (status === 'error') return null; // Don't show anything if not desktop app

    const colorMap = {
        'checking': '#ffc107', // Amber
        'online': '#28a745',   // Green
        'offline': '#dc3545',  // Red
        'error': '#6c757d'     // Grey
    };
    
    const iconMap = {
        'checking': 'fa-circle-notch fa-spin',
        'online': 'fa-check-circle',
        'offline': 'fa-exclamation-circle',
        'error': 'fa-times-circle'
    };

    return (
        <div 
            className="d-flex align-items-center bg-white px-3 py-1 rounded shadow-sm mr-3" 
            title={message}
            style={{ 
                border: `1px solid ${status === 'offline' ? '#ffcccc' : '#e9ecef'}`,
                transition: 'all 0.3s ease'
            }}
        >
            <i 
                className={`fas ${iconMap[status]} mr-2`} 
                style={{ color: colorMap[status], fontSize: '0.8rem' }}
            ></i>
            <small className="font-weight-bold text-muted" style={{ fontSize: '0.75rem', letterSpacing: '0.5px' }}>
                {status === 'offline' ? 'PRINTER OFFLINE' : 'PRINTER READY'}
            </small>
        </div>
    );
};

export default PrinterStatus;
