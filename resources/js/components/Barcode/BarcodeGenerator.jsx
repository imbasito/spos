import React, { useState, useEffect, useCallback } from "react";
import Barcode from "react-barcode";
import Swal from "sweetalert2";
import axios from "axios";

// Get CSRF token from meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

export default function BarcodeGenerator() {
    const [label, setLabel] = useState("");
    const [barcodeValue, setBarcodeValue] = useState("");
    const [mfgDate, setMfgDate] = useState("");
    const [expDate, setExpDate] = useState("");
    const [labelSize, setLabelSize] = useState("large");
    const [showPrice, setShowPrice] = useState(false);
    const [price, setPrice] = useState("");
    const [history, setHistory] = useState([]);
    const [loading, setLoading] = useState(false);
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);

    // Generate random 12-digit barcode
    const generateNewBarcode = useCallback(() => {
        const randomEan = Math.floor(Math.random() * 1000000000000)
            .toString()
            .padStart(12, "0");
        setBarcodeValue(randomEan);
    }, []);

    // Load history from API
    const loadHistory = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const res = await axios.get(`/admin/barcode/history?page=${page}`);
            setHistory(res.data.data || []);
            setCurrentPage(res.data.current_page || 1);
            setLastPage(res.data.last_page || 1);
        } catch (error) {
            // Silently fail - table might not exist yet
            console.log("History not available yet");
            setHistory([]);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        generateNewBarcode();
        loadHistory();
    }, []);

    // Print barcode (and save to history)
    const handlePrint = async () => {
        // PRO VALIDATION LOGIC
        if (!label || !label.trim()) {
             Swal.fire({ icon: 'warning', title: 'Missing Label', text: 'Please enter a product name.' });
             return;
        }
        if (!barcodeValue) {
             Swal.fire({ icon: 'warning', title: 'Missing Barcode', text: 'Please generate a barcode first.' });
             return;
        }
        
        // Strict Date Check for Large Labels
        if (labelSize === 'large') {
            if (!mfgDate || !expDate) {
                 Swal.fire({ 
                     icon: 'error', 
                     title: 'Dates Required', 
                     text: 'Professional Large Labels require both MFG and EXP dates.',
                     confirmButtonColor: '#800000'
                 });
                 return;
            }
        }

        const url = `/admin/barcode/print?label=${encodeURIComponent(label)}&barcode=${barcodeValue}&mfg=${mfgDate}&exp=${expDate}&size=${labelSize}&price=${showPrice ? price : 0}`;
        const tagPrinter = window.posSettings?.tagPrinter;

        // 1. Try Electron Silent Print
        if (window.electron && window.electron.printSilent) {
            const toastId = Swal.fire({
                title: 'Printing Label...',
                didOpen: () => Swal.showLoading(),
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });

            try {
                // Determine full URL if relative
                let targetUrl = url;
                if (!url.startsWith('http')) {
                    const { protocol, host } = window.location;
                    targetUrl = `${protocol}//${host}${url}`;
                }

                const res = await window.electron.printSilent(targetUrl, tagPrinter);
                
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Printed Successfully',
                        text: tagPrinter ? `Sent to: ${tagPrinter}` : 'Sent to Default Printer',
                        toast: true,
                        position: 'top-end',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(res.error || "Unknown Print Error");
                }
            } catch (err) {
                console.error("Silent Print Failed", err);
                Swal.fire({
                    icon: 'error',
                    title: 'Print Error',
                    text: 'Falling back to browser print...',
                    toast: true,
                    position: 'top-end',
                    timer: 2000
                });
                // Fallback below
                fallbackPrint(url);
            }
        } else {
            // 2. Standard Browser Fallback
            fallbackPrint(url);
        }

        // Save to history (silently in background)
        try {
            await axios.post("/admin/barcode/store", {
                barcode: barcodeValue,
                label: label || null,
            });
            loadHistory(); // Refresh history
        } catch (error) {
            console.log("Could not save to history:", error.response?.data?.message || error.message);
        }

        // Clear and generate new for next item
        setLabel("");
        generateNewBarcode();
    };

    const fallbackPrint = (url) => {
        let iframe = document.getElementById('print-frame');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'print-frame';
            document.body.appendChild(iframe);
        }
        iframe.src = url;
    };

    // Reprint from history
    const handleReprint = (item) => {
        const url = `/admin/barcode/print?label=${encodeURIComponent(item.label || '')}&barcode=${item.barcode}`;
        let iframe = document.getElementById('print-frame');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'print-frame';
            iframe.style.display = 'none';
            document.body.appendChild(iframe);
        }
        iframe.src = url;
    };

    // Delete from history
    const handleDelete = async (id) => {
        const result = await Swal.fire({
            title: 'Delete this barcode?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#800000',
            cancelButtonColor: '#6D4C41',
            confirmButtonText: 'Yes, delete'
        });

        if (result.isConfirmed) {
            try {
                await axios.delete(`/admin/barcode/${id}`);
                loadHistory(currentPage);
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Deleted',
                    showConfirmButton: false,
                    timer: 1500
                });
            } catch (error) {
                Swal.fire("Error", "Failed to delete", "error");
            }
        }
    };

    // Format date
    const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    };

    return (
        <div className="row">
            {/* Left Panel - Generator */}
            <div className="col-lg-5 col-md-6 mb-4">
                <div className="card shadow-sm border-0 border-radius-15 h-100">
                    <div className="card-header bg-gradient-maroon py-3">
                        <h5 className="card-title text-white font-weight-bold mb-0">
                            <i className="fas fa-barcode mr-2"></i> Generate Barcode
                        </h5>
                    </div>
                    <div className="card-body p-4">
                        {/* Preview */}
                        <div className="text-center mb-4 p-3 border rounded shadow-sm" style={{ backgroundColor: '#FFFDF9', minHeight: '160px', display: 'flex', flexDirection: 'column', justifyContent: 'center', alignItems: 'center' }}>
                            <div style={{ 
                                padding: '10px', 
                                background: 'white', 
                                display: 'inline-block', 
                                textAlign: 'center', 
                                borderRadius: '8px', 
                                boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
                                width: labelSize === 'large' ? '240px' : '180px', // Visual simulation of size diff
                                transition: 'all 0.3s ease',
                                border: '1px solid #f0f0f0'
                            }}>
                                <div style={{ fontSize: '13px', fontWeight: 'bold', marginBottom: '4px', color: '#3E2723', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                    {label || 'PRODUCT NAME'}
                                </div>
                                
                                {showPrice && price && (
                                    <div style={{ 
                                        fontWeight: 'bold', 
                                        fontSize: labelSize === 'large' ? '13px' : '11px', 
                                        marginBottom: '2px',
                                        color: '#000'
                                    }}>
                                        Rs. {parseFloat(price).toFixed(2)}
                                    </div>
                                )}

                                <div style={{ display: 'flex', justifyContent: 'center' }}>
                                    <Barcode
                                        value={barcodeValue || "000000000000"}
                                        format="EAN13"
                                        width={labelSize === 'large' ? 1.8 : 1.4}
                                        height={labelSize === 'large' ? 45 : 35}
                                        fontSize={11}
                                    />
                                </div>
                                
                                {labelSize === 'large' && (mfgDate || expDate) && (
                                    <div className="d-flex justify-content-between mt-1 px-1" style={{ fontSize: '10px', fontWeight: 'bold', borderTop: '1px dashed #eee', paddingTop: '4px' }}>
                                        {mfgDate && <span>MFG: {new Date(mfgDate).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: '2-digit' })}</span>}
                                        {expDate && <span>EXP: {new Date(expDate).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: '2-digit' })}</span>}
                                    </div>
                                )}
                            </div>
                            <small className="text-muted mt-2 font-italic">
                                <i className="fas fa-eye mr-1"></i>
                                {labelSize === 'large' ? 'Large Label Preview (50mm)' : 'Small Label Preview (38mm)'}
                            </small>
                        </div>

                        {/* Inputs */}
                        <div className="form-group">
                            <label className="font-weight-bold"><i className="fas fa-tag mr-1 text-maroon"></i> Product Label <span className="text-danger">*</span></label>
                            <input
                                type="text"
                                className="form-control border-radius-10"
                                placeholder="e.g. Gulab Jamun 500g"
                                value={label}
                                onChange={(e) => setLabel(e.target.value)}
                            />
                        </div>

                        <div className="form-group">
                            <label className="font-weight-bold"><i className="fas fa-hashtag mr-1 text-maroon"></i> Barcode</label>
                            <div className="input-group">
                                <input
                                    type="text"
                                    className="form-control border-radius-10"
                                    value={barcodeValue}
                                    readOnly
                                    style={{ backgroundColor: '#f8f9fa', fontFamily: 'monospace', letterSpacing: '1px' }}
                                />
                                <div className="input-group-append">
                                    <button className="btn btn-outline-secondary" onClick={generateNewBarcode} title="Generate New">
                                        <i className="fas fa-random"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Dates & Size inputs */}
                        <div className="form-row">
                            <div className="form-group col-md-6">
                                <label className="font-weight-bold">
                                    <i className="fas fa-calendar-alt mr-1 text-muted"></i> MFG Date 
                                    {labelSize === 'large' && <span className="text-danger">*</span>}
                                </label>
                                <input
                                    type="date"
                                    className="form-control border-radius-10"
                                    value={mfgDate}
                                    onChange={(e) => setMfgDate(e.target.value)}
                                />
                            </div>
                            <div className="form-group col-md-6">
                                <label className="font-weight-bold">
                                    <i className="fas fa-calendar-times mr-1 text-muted"></i> EXP Date
                                    {labelSize === 'large' && <span className="text-danger">*</span>}
                                </label>
                                <input
                                    type="date"
                                    className="form-control border-radius-10"
                                    value={expDate}
                                    onChange={(e) => setExpDate(e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="form-group">
                            <label className="font-weight-bold"><i className="fas fa-ruler-combined mr-1 text-muted"></i> Label Size</label>
                            <select 
                                className="form-control border-radius-10 custom-select"
                                value={labelSize} 
                                onChange={(e) => setLabelSize(e.target.value)}
                            >
                                <option value="large">Large (Included Dates)</option>
                                <option value="small">Small (Barcode Only)</option>
                            </select>
                        </div>

                        <div className="form-group">
                            <div className="custom-control custom-switch">
                                <input 
                                    type="checkbox" 
                                    className="custom-control-input" 
                                    id="showPriceCheck"
                                    checked={showPrice}
                                    onChange={(e) => setShowPrice(e.target.checked)}
                                />
                                <label className="custom-control-label font-weight-bold" htmlFor="showPriceCheck">Show Price on Label</label>
                            </div>
                        </div>
                        
                        {showPrice && (
                            <div className="form-group animate__animated animate__fadeIn">
                                <label className="font-weight-bold"><i className="fas fa-coins mr-1 text-maroon"></i> Price (Rs.)</label>
                                <input
                                    type="number"
                                    className="form-control border-radius-10"
                                    placeholder="Enter Price"
                                    value={price}
                                    onChange={(e) => setPrice(e.target.value)}
                                />
                            </div>
                        )}

                        {/* Buttons */}
                        <div className="row mt-4">
                            <div className="col-12">
                                <button
                                    className="btn btn-maroon btn-block py-3 font-weight-bold shadow-sm border-radius-10 transition-hover"
                                    onClick={handlePrint}
                                >
                                    <i className="fas fa-print mr-2"></i> Print Label
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Right Panel - History */}
            <div className="col-lg-7 col-md-6">
                <div className="card shadow-sm border-0 border-radius-15 h-100">
                    <div className="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 className="card-title text-dark font-weight-bold mb-0">
                            <i className="fas fa-history mr-2 text-secondary"></i> Print History
                        </h5>
                        <button className="btn btn-light btn-sm shadow-sm" onClick={() => loadHistory(currentPage)} title="Refresh List">
                            <i className="fas fa-sync-alt text-maroon"></i>
                        </button>
                    </div>
                    <div className="card-body p-0">
                        {loading ? (
                            <div className="text-center py-4">
                                <i className="fas fa-spinner fa-spin fa-2x"></i>
                            </div>
                        ) : history.length === 0 ? (
                            <div className="text-center py-5 text-muted">
                                <i className="fas fa-inbox fa-3x mb-3 text-light-gray"></i>
                                <p className="mb-0">No barcodes printed yet</p>
                                <small>Your recent print jobs will appear here.</small>
                            </div>
                        ) : (
                            <div className="table-responsive">
                                <table className="table table-hover mb-0">
                                    <thead className="bg-light">
                                        <tr>
                                            <th className="border-0 pl-4 py-3">Label</th>
                                            <th className="border-0 py-3">Barcode</th>
                                            <th className="border-0 py-3">Date</th>
                                            <th className="border-0 py-3 text-right pr-4" style={{ width: '120px' }}>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {history.map((item) => (
                                            <tr key={item.id} style={{ height: '60px' }}>
                                                <td className="pl-4 align-middle font-weight-bold">{item.label || <span className="text-muted">-</span>}</td>
                                                <td className="align-middle"><code className="p-1 rounded bg-light border">{item.barcode}</code></td>
                                                <td className="align-middle text-muted small">{formatDate(item.created_at)}</td>
                                                <td className="text-right pr-4 align-middle">
                                                    <button
                                                        className="btn btn-sm btn-info shadow-sm mr-2"
                                                        onClick={() => handleReprint(item)}
                                                        title="Reprint Label"
                                                        style={{ width: '32px', height: '32px', padding: 0, borderRadius: '50%' }}
                                                    >
                                                        <i className="fas fa-print" style={{ fontSize: '12px' }}></i>
                                                    </button>
                                                    <button
                                                        className="btn btn-sm btn-danger shadow-sm"
                                                        onClick={() => handleDelete(item.id)}
                                                        title="Delete Record"
                                                        style={{ width: '32px', height: '32px', padding: 0, borderRadius: '50%' }}
                                                    >
                                                        <i className="fas fa-trash" style={{ fontSize: '12px' }}></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                    {/* Pagination */}
                    {lastPage > 1 && (
                        <div className="card-footer bg-white border-top-0 py-3">
                            <ul className="pagination justify-content-center m-0">
                                <li className={`page-item ${currentPage === 1 ? 'disabled' : ''}`}>
                                    <button className="page-link border-radius-10 mr-1" onClick={() => loadHistory(currentPage - 1)}>«</button>
                                </li>
                                {[...Array(Math.min(lastPage, 5))].map((_, i) => (
                                    <li key={i} className={`page-item ${currentPage === i + 1 ? 'active' : ''}`}>
                                        <button className="page-link border-radius-10 mx-1" onClick={() => loadHistory(i + 1)}>{i + 1}</button>
                                    </li>
                                ))}
                                <li className={`page-item ${currentPage === lastPage ? 'disabled' : ''}`}>
                                    <button className="page-link border-radius-10 ml-1" onClick={() => loadHistory(currentPage + 1)}>»</button>
                                </li>
                            </ul>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
