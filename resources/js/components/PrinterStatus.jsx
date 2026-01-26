import React, { useState, useEffect } from 'react';

const PrinterStatus = () => {
    const [status, setStatus] = useState('checking'); // checking, online, offline
    const [message, setMessage] = useState('Checking hardware...');
    
    const targetPrinterName = window.posSettings?.receiptPrinter;

    useEffect(() => {
        let isMounted = true;
        const checkPrinter = async () => {
            if (!window.electron?.getPrinters) return;
            try {
                const printers = await window.electron.getPrinters();
                const printer = printers.find(p => p.name === targetPrinterName) || printers.find(p => p.isDefault);
                
                if (isMounted) {
                    if (printer) {
                        // Status 0 is success on most Windows systems
                        if (printer.status === 0) {
                            setStatus('online');
                            setMessage(`Ready: ${printer.name}`);
                        } else {
                            setStatus('offline');
                            setMessage(`Offline: ${printer.name}`);
                        }
                    } else {
                        setStatus('offline');
                        setMessage('Printer Not Found');
                    }
                }
            } catch (e) {
                if (isMounted) setStatus('offline');
            }
        };

        checkPrinter();
        const interval = setInterval(checkPrinter, 3000); // 3 seconds = Professional real-time feel
        return () => { isMounted = false; clearInterval(interval); };
    }, [targetPrinterName]);

    const getBulbClass = () => {
        if (status === 'online') return 'bulb-green pulse-glow';
        if (status === 'offline') return 'bulb-red blink-status';
        return 'bulb-amber';
    };

    return (
        <div className="d-flex align-items-center bg-white px-3 py-2 rounded-lg shadow-sm border printer-status-container" title={message}>
            <div className={`status-bulb ${getBulbClass()} mr-2`}></div>
            <div className="d-flex flex-column">
                <small className="font-weight-bold text-dark mb-0" style={{ fontSize: '0.65rem', lineHeight: 1 }}>
                    PRINTER STATUS
                </small>
                <span className={`small font-weight-bold ${status === 'online' ? 'text-success' : 'text-danger'}`} style={{ fontSize: '0.65rem' }}>
                    {status === 'online' ? 'CONNECTED' : 'OFFLINE'}
                </span>
            </div>

            <style>{`
                .printer-status-container { 
                    min-width: 140px; 
                    height: 48px; 
                    border-color: #eee !important;
                }
                .status-bulb {
                    width: 10px;
                    height: 10px;
                    border-radius: 50%;
                    border: 1px solid rgba(0,0,0,0.1);
                }
                .bulb-green { background-color: #28a745; box-shadow: 0 0 5px #28a745; }
                .bulb-red { background-color: #dc3545; box-shadow: 0 0 5px #dc3545; }
                .bulb-amber { background-color: #ffc107; }

                .pulse-glow {
                    animation: pulse-green 3s infinite ease-in-out;
                }
                .blink-status {
                    animation: blink-red 1.5s infinite ease-in-out;
                }

                @keyframes pulse-green {
                    0% { box-shadow: 0 0 2px #28a745; opacity: 0.8; }
                    50% { box-shadow: 0 0 10px #28a745; opacity: 1; }
                    100% { box-shadow: 0 0 2px #28a745; opacity: 0.8; }
                }
                @keyframes blink-red {
                    0% { opacity: 1; box-shadow: 0 0 10px #dc3545; }
                    50% { opacity: 0.2; box-shadow: 0 0 0px #dc3545; }
                    100% { opacity: 1; box-shadow: 0 0 10px #dc3545; }
                }
                
                /* POS Drawer Button Fix */
                .pos-drawer-btn:hover {
                    background-color: #343a40 !important;
                    color: white !important;
                }
                .pos-drawer-btn:hover .drawer-icon, 
                .pos-drawer-btn:hover .drawer-text {
                    color: white !important;
                }
            `}</style>
        </div>
    );
};

export default PrinterStatus;
