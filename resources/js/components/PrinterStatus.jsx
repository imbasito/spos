import React, { useState, useEffect } from 'react';

const PrinterStatus = () => {
    const [printerStatus, setPrinterStatus] = useState('offline'); // online, offline
    const [drawerStatus, setDrawerStatus] = useState('closed'); // open, closed
    const [message, setMessage] = useState('Scanning hardware...');
    
    const targetPrinterName = window.posSettings?.receiptPrinter;

    useEffect(() => {
        let isMounted = true;
        const checkHardware = async () => {
            if (!window.electron?.pollStatus) return;
            try {
                const res = await window.electron.pollStatus(targetPrinterName);
                if (isMounted) {
                    setPrinterStatus(res.printer);
                    setDrawerStatus(res.drawer);
                    setMessage(res.message);
                }
            } catch (e) {
                if (isMounted) setPrinterStatus('offline');
            }
        };

        checkHardware();
        const interval = setInterval(checkHardware, 2000); // 2 seconds for ultra-responsive feel
        return () => { isMounted = false; clearInterval(interval); };
    }, [targetPrinterName]);

    const handleManualDrawerClose = async () => {
        if (window.electron?.closeDrawerManually) {
            await window.electron.closeDrawerManually();
            setDrawerStatus('closed');
        }
    };

    const getBulbClass = (type, state) => {
        if (type === 'printer') {
            return state === 'online' ? 'bulb-green pulse-glow' : 'bulb-red blink-status';
        }
        if (type === 'drawer') {
            if (state === 'open') return 'bulb-amber-bright pulse-warning';
            return 'bulb-green-dim';
        }
        return 'bulb-grey';
    };

    return (
        <div className="d-flex align-items-center bg-white px-3 py-1 rounded-lg shadow-sm border hardware-status-dashboard" title={message}>
            
            {/* Printer Section */}
            <div className="d-flex align-items-center mr-3 pr-3 border-right hardware-cell">
                <div className={`status-bulb ${getBulbClass('printer', printerStatus)} mr-2`}></div>
                <div className="d-flex flex-column">
                    <small className="label-text">PRINTER</small>
                    <span className={`status-text ${printerStatus === 'online' ? 'text-success' : 'text-danger'}`}>
                        {printerStatus === 'online' ? 'READY' : 'OFFLINE'}
                    </span>
                </div>
            </div>

            {/* Drawer Section */}
            <div className="d-flex align-items-center hardware-cell" style={{ cursor: drawerStatus === 'open' ? 'pointer' : 'default' }} onClick={drawerStatus === 'open' ? handleManualDrawerClose : null}>
                <div className={`status-bulb ${getBulbClass('drawer', drawerStatus)} mr-2`}></div>
                <div className="d-flex flex-column">
                    <small className="label-text">DRAWER</small>
                    <span className={`status-text ${drawerStatus === 'open' ? 'text-warning font-weight-bold' : 'text-muted'}`}>
                        {drawerStatus === 'open' ? 'OPEN' : 'CLOSED'}
                    </span>
                </div>
                {drawerStatus === 'open' && (
                    <i className="fas fa-times-circle ml-2 text-danger animate__animated animate__fadeIn" title="Click if manually closed"></i>
                )}
            </div>

            <style>{`
                .hardware-status-dashboard { 
                    min-width: 260px; 
                    height: 44px; 
                    border-color: #f0f0f0 !important;
                    user-select: none;
                }
                .hardware-cell { min-width: 90px; }
                .label-text { font-size: 0.6rem; color: #999; font-weight: 800; letter-spacing: 0.05em; line-height: 1; margin-bottom: 2px; }
                .status-text { font-size: 0.7rem; font-weight: 700; line-height: 1; }
                
                .status-bulb {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                }
                .bulb-green { background-color: #28a745; box-shadow: 0 0 6px #28a745; }
                .bulb-green-dim { background-color: #28a745; opacity: 0.4; }
                .bulb-red { background-color: #dc3545; box-shadow: 0 0 6px #dc3545; }
                .bulb-amber-bright { background-color: #ffc107; box-shadow: 0 0 8px #ffc107; }
                .bulb-grey { background-color: #ccc; }

                .pulse-glow { animation: hardware-pulse 2s infinite ease-in-out; }
                .pulse-warning { animation: drawer-warning 0.8s infinite ease-in-out; }
                .blink-status { animation: hardware-blink 1.5s infinite ease-in-out; }

                @keyframes hardware-pulse {
                    0% { opacity: 1; transform: scale(1); }
                    50% { opacity: 0.7; transform: scale(1.1); }
                    100% { opacity: 1; transform: scale(1); }
                }
                @keyframes hardware-blink {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.3; }
                }
                @keyframes drawer-warning {
                    0%, 100% { transform: scale(1); box-shadow: 0 0 4px #ffc107; }
                    50% { transform: scale(1.2); box-shadow: 0 0 12px #ffc107; }
                }
            `}</style>
        </div>
    );
};

export default PrinterStatus;

