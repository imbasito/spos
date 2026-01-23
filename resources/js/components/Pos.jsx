import React, { useEffect, useState, useCallback, useMemo, useRef, memo } from "react";
import axios from "axios";
import Swal from "sweetalert2";
import Cart from "./Cart";
import toast, { Toaster } from "react-hot-toast";
import CustomerSelect from "./CutomerSelect";
import PaymentModal from "./PaymentModal";
import ReceiptModal from "./ReceiptModal";
import debounce from "lodash/debounce";
import throttle from "lodash/throttle";

import SuccessSound from "../sounds/beep-07a.mp3";
import WarningSound from "../sounds/beep-02.mp3";
import playSound from "../utils/playSound";

// Memoized ProductCard component
const ProductCard = memo(({ product, onClick, baseUrl }) => (
    <div
        onClick={() => onClick(product.id)}
        className="col-6 col-md-4 col-lg-3 mb-3 px-2"
        style={{ cursor: "pointer" }}
    >
        <div className="card h-100 pos-product-card">
            <div className="pos-product-img-wrapper">
                <img
                    src={`${baseUrl}/storage/${product.image}`}
                    alt={product.name}
                    className="pos-product-img"
                    loading="lazy"
                    onError={(e) => {
                        e.target.onerror = null;
                        e.target.src = `${baseUrl}/assets/images/no-image.png`;
                    }}
                />
            </div>
            <div className="card-body p-2 text-center d-flex flex-column justify-content-between">
                <div>
                    <h6 className="text-secondary text-sm font-weight-bold mb-1 text-truncate" title={product.name}>
                        {product.name}
                    </h6>
                    <span className="badge badge-light border">Qty: {product.quantity}</span>
                </div>
                <div className="mt-2 text-dark font-weight-bolder">
                    Rs. {parseFloat(product?.discounted_price || 0).toFixed(2)}
                </div>
            </div>
        </div>
    </div>
));

export default function Pos() {
    const [products, setProducts] = useState([]);
    const [carts, setCarts] = useState([]);
    const [orderDiscount, setOrderDiscount] = useState(0);
    const [customerId, setCustomerId] = useState();
    
    // Totals
    const [total, setTotal] = useState(0); // Cart Subtotal
    const [updateTotal, setUpdateTotal] = useState(0); // Final Total after discount
    
    // UI State
    const [searchQuery, setSearchQuery] = useState("");
    const [debouncedSearch, setDebouncedSearch] = useState("");
    const [loading, setLoading] = useState(false);
    const [cartUpdated, setCartUpdated] = useState(false);
    
    // Modals
    const [showPaymentModal, setShowPaymentModal] = useState(false);
    const [showReceiptModal, setShowReceiptModal] = useState(false);
    const [receiptUrl, setReceiptUrl] = useState('');

    const { protocol, hostname, port } = window.location;
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(0);
    const [initialLoadDone, setInitialLoadDone] = useState(false);

    // Read settings
    const autoFractionalDiscount = window.posSettings?.autoFractionalDiscount || false;

    // Derived values
    const fullDomainWithPort = useMemo(() =>
        `${protocol}//${hostname}${port ? `:${port}` : ""}`,
        [protocol, hostname, port]
    );

    // Auto-apply fractional discount
    useEffect(() => {
        if (autoFractionalDiscount && total > 0) {
            const fractionalPart = total % 1;
            if (fractionalPart > 0) {
                setOrderDiscount(fractionalPart.toFixed(2));
            }
        }
    }, [total, autoFractionalDiscount]);

    // Recalculate Final Total
    useEffect(() => {
        let disc = parseFloat(orderDiscount) || 0;
        const subTotal = parseFloat(total) || 0;
        setUpdateTotal((subTotal - disc).toFixed(2));
    }, [total, orderDiscount]);

    // Fetch Products
    const getProducts = useCallback(async (search = "", page = 1) => {
        setLoading(true);
        try {
            const isBarcode = /^\d{3,}/.test(search); 
            const params = { page };
            if (isBarcode) params.barcode = search;
            else params.search = search;

            const res = await axios.get('/admin/get/products', { params });
            const productsData = res.data;

            if (page === 1) {
                setProducts(productsData.data);
                if (productsData.data.length === 1 && isBarcode) {
                   addProductToCart(productsData.data[0].id);
                   setSearchQuery(''); 
                }
            } else {
                setProducts(prev => [...prev, ...productsData.data]);
            }
            setTotalPages(productsData.meta.last_page);
        } catch (error) {
            console.error("Error fetching products:", error);
        } finally {
            setLoading(false);
        }
    }, []);

    // Initial Load
    useEffect(() => {
         getProducts();
         setInitialLoadDone(true);
    }, []);

    // Fetch Cart
    const getCarts = async () => {
        try {
            const res = await axios.get('/admin/cart');
            setTotal(res.data?.total);
            setCarts(res.data?.carts);
        } catch (error) {
            console.error("Cart error", error);
        }
    };
    useEffect(() => { getCarts(); }, [cartUpdated]);

    // Search Debounce
    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(searchQuery);
        }, 300);
        return () => clearTimeout(timer);
    }, [searchQuery]);

    useEffect(() => {
        if (initialLoadDone) {
            setCurrentPage(1);
            getProducts(debouncedSearch, 1);
        }
    }, [debouncedSearch]);

    // Scroll Logic for Products
    useEffect(() => {
        const productContainer = document.getElementById('product-grid-container');
        if (!productContainer) return;

        const handleScroll = throttle(() => {
            if (productContainer.scrollTop + productContainer.clientHeight >= productContainer.scrollHeight - 100) {
                 if (currentPage < totalPages && !loading) {
                    setCurrentPage(prev => prev + 1);
                 }
            }
        }, 200);

        productContainer.addEventListener('scroll', handleScroll);
        return () => productContainer.removeEventListener('scroll', handleScroll);
    }, [currentPage, totalPages, loading]);

    // Load more products
    useEffect(() => {
        if (currentPage > 1) getProducts(debouncedSearch, currentPage);
    }, [currentPage]);


    // Actions
    function addProductToCart(id) {
        axios.post("/admin/cart", { id })
            .then((res) => {
                setCartUpdated(!cartUpdated);
                playSound(SuccessSound);
                toast.success("Added to cart");
            })
            .catch((err) => {
                playSound(WarningSound);
                toast.error(err.response?.data?.message || "Error adding item");
            });
    }

    function cartEmpty() {
        if (total <= 0) return;
        
        Swal.fire({
            title: "Clear Cart?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Clear",
            confirmButtonColor: "#d33",
        }).then((result) => {
            if (result.isConfirmed) {
                axios.put("/admin/cart/empty")
                    .then(() => {
                        setCartUpdated(!cartUpdated);
                        setOrderDiscount(0);
                        playSound(SuccessSound);
                    })
                    .catch(() => playSound(WarningSound));
            }
        });
    }

    // Checkout Flow
    const handleCheckoutClick = () => {
        if (total <= 0) {
            toast.error("Cart is empty");
            return;
        }
        if (!customerId) {
            toast.error("Please select a customer first");
            return;
        }
        setShowPaymentModal(true);
    };

    const handlePaymentConfirm = (paymentData) => {
        const { paid, method, trxId } = paymentData;
        
        // Finalize Order
        axios.put("/admin/order/create", {
            customer_id: customerId,
            order_discount: parseFloat(orderDiscount) || 0,
            paid: parseFloat(paid) || 0,
            payment_method: method,
            transaction_id: trxId,
        })
        .then((res) => {
            setShowPaymentModal(false);
            
            // Allow state to clear instantly for UX
            setCarts([]);
            setTotal(0);
            setUpdateTotal(0);
            setOrderDiscount(0);
            setCartUpdated(!cartUpdated);

            // Show Receipt
            const orderId = res.data?.order?.id;
            if (orderId) {
                const url = window.location.origin + `/admin/orders/pos-invoice/${orderId}`;
                setReceiptUrl(url);
                setShowReceiptModal(true);
            }
            
            // Refresh Products (stock update)
            getProducts(debouncedSearch, currentPage);
            toast.success("Order Created Successfully!");
        })
        .catch((err) => {
            toast.error(err.response?.data?.message || "Order Failed");
        });
    };

    // Keyboard Shortcuts
    useEffect(() => {
        const handleKeyDown = (e) => {
            const isInput = ['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName);
            
            // F4 for Pay (Exclusive shortcut as requested)
            if (e.key === 'F4') {
                e.preventDefault();
                handleCheckoutClick();
                return;
            }

            // Removed Enter key for checkout to avoid barcode scanner conflicts
            // Barcode scanners often send 'Enter' after scanning, which was causing premature checkout.

            if ((e.ctrlKey || e.metaKey) && (e.key === 'Delete' || e.key === 'Backspace')) {
                e.preventDefault();
                cartEmpty();
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [total, customerId, showPaymentModal]);

    // Render
    return (
        <div className="pos-app-container">
            {/* LEFT PANEL: PRODUCTS */}
            <div className="d-flex flex-column border-right bg-white" style={{ flex: '0 0 65%', maxWidth: '65%' }}>
                {/* Header / Search */}
                <div className="p-3 border-bottom shadow-sm bg-light">
                    <div className="d-flex align-items-center">
                        <div className="input-group input-group-lg mr-3">
                            <div className="input-group-prepend">
                                <span className="input-group-text bg-white border-right-0"><i className="fas fa-search text-muted"></i></span>
                            </div>
                            <input 
                                type="text" 
                                className="form-control border-left-0 pl-0" 
                                placeholder="Scan Barcode or Search Product..." 
                                value={searchQuery}
                                onChange={e => setSearchQuery(e.target.value)}
                                autoFocus
                            />
                        </div>
                    </div>
                </div>

                {/* Product Grid */}
                <div id="product-grid-container" className="flex-grow-1 custom-scroll p-3" style={{ backgroundColor: '#f4f6f9' }}>
                    <div className="row no-gutters">
                        {products.map((product) => (
                            <ProductCard 
                                key={product.id} 
                                product={product} 
                                onClick={addProductToCart} 
                                baseUrl={fullDomainWithPort} 
                            />
                        ))}
                        {products.length === 0 && !loading && (
                            <div className="col-12 text-center mt-5 text-muted">
                                <i className="fas fa-box-open fa-3x mb-3"></i>
                                <h5>No products found</h5>
                            </div>
                        )}
                        {loading && (
                            <div className="col-12 text-center py-4">
                               <div className="spinner-border text-primary" role="status"></div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* RIGHT PANEL: CART & CHECKOUT */}
            <div className="d-flex flex-column bg-white shadow-lg" style={{ flex: '1', zIndex: 10 }}>
                {/* Customer Select */}
                <div className="p-3 border-bottom bg-gradient-light">
                    <CustomerSelect setCustomerId={setCustomerId} />
                </div>

                {/* Cart Items (Scrollable) */}
                <div className="flex-grow-1 custom-scroll no-scrollbar p-0" style={{ backgroundColor: '#fff' }}>
                    <div className="p-2">
                        <Cart 
                            carts={carts} 
                            setCartUpdated={setCartUpdated} 
                            cartUpdated={cartUpdated} 
                        />
                    </div>
                </div>

                {/* Footer: Totals & Actions */}
                <div className="border-top bg-light p-3" style={{ fontSize: '1.rem' }}>
                     {/* Professional Discount Section */}
                     <div className="bg-white p-3 rounded shadow-sm border mb-3">
                        <div className="d-flex justify-content-between align-items-center mb-2">
                            <span className="font-weight-bold text-dark" style={{ fontSize: '0.9rem' }}>
                                <i className="fas fa-percent mr-2 text-maroon"></i> DISCOUNT / ADJUSTMENT
                            </span>
                        </div>
                        
                        <div className="input-group input-group-lg shadow-sm border rounded overflow-hidden">
                            <div className="input-group-prepend">
                                <span className="input-group-text bg-white border-0 text-muted">Rs.</span>
                            </div>
                            <input 
                                type="number" 
                                className="form-control border-0 font-weight-bold text-right pr-3" 
                                style={{ fontSize: '1.2rem', color: '#800000' }}
                                placeholder="0.00"
                                min="0"
                                max={total}
                                step="any"
                                value={orderDiscount}
                                onChange={e => {
                                    const val = e.target.value === '' ? '' : parseFloat(e.target.value);
                                    if(val === '' || (!isNaN(val) && val >= 0 && val <= total)) {
                                        setOrderDiscount(e.target.value);
                                    }
                                }}
                            />
                            <div className="input-group-append">
                                <button 
                                    className={`btn ${orderDiscount > 0 ? 'btn-maroon' : 'btn-outline-secondary'} px-3`}
                                    onClick={() => setOrderDiscount(0)}
                                    title="Reset Discount"
                                    style={{ border: 'none' }}
                                >
                                    <i className="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div className="mt-2 d-flex justify-content-between align-items-center p-2 bg-light rounded border-dashed">
                            <div>
                                <span className="text-xs text-muted font-weight-bold text-uppercase">Auto-Rounding</span>
                                <div className="text-xs text-dark opacity-75">Automatically clear fractional amounts</div>
                            </div>
                            <div className="custom-control custom-switch custom-switch-maroon">
                                <input 
                                    type="checkbox" 
                                    className="custom-control-input" 
                                    id="autoFrac" 
                                    checked={autoFractionalDiscount} 
                                    readOnly
                                />
                                <label className="custom-control-label" htmlFor="autoFrac"></label>
                            </div>
                        </div>
                     </div>

                    {/* Grand Total */}
                    <div className="d-flex justify-content-between align-items-center mb-3 p-3 bg-gradient-dark rounded shadow-sm">
                        <div className="line-height-1">
                            <small className="text-white-50 text-uppercase font-weight-bold" style={{ fontSize: '0.7em' }}>Total Payable</small>
                            <h4 className="m-0 text-white font-weight-light">PKR</h4>
                        </div>
                        <h1 className="m-0 text-white font-weight-bold display-4" style={{ fontSize: '3.5rem' }}>{Math.max(0, updateTotal)}</h1>
                    </div>

                    <div className="row no-gutters">
                        <div className="col-4 pr-1">
                            <button 
                                onClick={cartEmpty} 
                                className="btn btn-outline-danger btn-block py-3 font-weight-bold border-2"
                                disabled={total <= 0}
                                style={{ height: '100%', borderRadius: '8px' }}
                            >
                                <i className="fas fa-trash-alt fa-lg d-block mb-1"></i> CLEAR
                            </button>
                        </div>
                        <div className="col-8 pl-1">
                            <button 
                                onClick={handleCheckoutClick} 
                                className="btn btn-success btn-block py-3 font-weight-bold shadow hover-lift"
                                style={{ fontSize: '1.4rem', borderRadius: '8px', height: '100%' }}
                                disabled={total <= 0}
                                title="Shortcut: Enter or F4"
                            >
                                <div className="d-flex justify-content-center align-items-center">
                                    <div className="text-left line-height-1 mr-3">
                                        <small className="d-block font-weight-normal opacity-75" style={{ fontSize: '0.6em' }}>ENTER</small>
                                        PAY
                                    </div>
                                    <i className="fas fa-chevron-circle-right fa-lg"></i>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* MODALS */}
            <PaymentModal 
                show={showPaymentModal} 
                total={updateTotal} 
                onCancel={() => setShowPaymentModal(false)}
                onConfirm={handlePaymentConfirm}
            />
            
            <ReceiptModal
                show={showReceiptModal}
                url={receiptUrl}
                onClose={() => {
                   setShowReceiptModal(false);
                   setReceiptUrl('');
                }}
            />
        </div>
    );
}