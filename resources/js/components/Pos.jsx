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
import ErrorBoundary from "./ErrorBoundary";

import SuccessSound from "../sounds/beep-07a.mp3";
import WarningSound from "../sounds/beep-02.mp3";
import playSound from "../utils/playSound";

// Custom Professional Context Menu Component
const ContextMenu = ({ x, y, onAction, onClose, product }) => {
    useEffect(() => {
        const handleClick = () => onClose();
        window.addEventListener('click', handleClick);
        return () => window.removeEventListener('click', handleClick);
    }, [onClose]);

    return (
        <div 
            className="pos-context-menu shadow-lg"
            style={{ 
                position: 'fixed', 
                top: y + 'px', 
                left: x + 'px', 
                zIndex: 10000, 
                display: 'block',
                margin: 0
            }}
        >
            <div className="px-3 py-1 mb-1 border-bottom" style={{ background: 'rgba(0,0,0,0.02)' }}>
                <small className="font-weight-bold text-muted text-uppercase" style={{ fontSize: '0.65rem', letterSpacing: '0.08em' }}>{product.name}</small>
            </div>
            <div className="context-item d-flex align-items-center px-3 py-2" onClick={() => onAction('edit')} style={{ cursor: 'pointer' }}>
                <i className="fas fa-edit mr-3 text-secondary" style={{ fontSize: '0.85rem', opacity: 0.8 }}></i> <span>Edit Detail</span>
            </div>
            <div className="context-item d-flex align-items-center px-3 py-2 border-top" onClick={() => onAction('purchase')} style={{ cursor: 'pointer' }}>
                <i className="fas fa-cart-arrow-down mr-3 text-success" style={{ fontSize: '0.85rem', opacity: 0.8 }}></i> <span>Add Stock</span>
            </div>


            <style>{`
                .pos-context-menu {
                    background-color: rgba(255, 255, 255, 0.82);
                    backdrop-filter: blur(25px);
                    -webkit-backdrop-filter: blur(25px);
                    border-radius: 12px;
                    min-width: 220px; 
                    padding: 6px 0;
                    overflow: hidden; 
                    border: 1px solid rgba(0, 0, 0, 0.1) !important;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
                    animation: appleContextMenuFade 0.15s ease-out;
                    transform-origin: 0 0;
                }

                @keyframes appleContextMenuFade {
                    from { opacity: 0; transform: scale(0.95); }
                    to { opacity: 1; transform: scale(1); }
                }
                .context-item { 
                    font-size: 0.9rem; 
                    font-weight: 500;
                    color: #1d1d1f !important;
                    transition: all 0.2s ease; 
                }
                .context-item:hover { background: rgba(0,0,0,0.05); }
            `}</style>
        </div>
    );
};







// Memoized ProductCard component (Apple-Style)
const ProductCard = memo(({ product, onClick, onContextMenu, baseUrl }) => (
    <div
        onClick={() => onClick(product.id)}
        onContextMenu={(e) => onContextMenu(e, product)}
        className="col-6 col-md-4 col-xl-3 mb-4 px-2"
        style={{ cursor: "pointer" }}
    >
        <div className={`apple-card h-100 ${product.quantity <= 0 ? 'out-of-stock-card' : ''}`}>
            <div className="pos-product-img-wrapper" style={{ position: 'relative', border: 'none', background: 'transparent' }}>
                <div className="pos-availability-badge" style={{ 
                    position: 'absolute', top: '10px', left: '10px', 
                    background: 'rgba(255,255,255,0.9)', backdropFilter: 'blur(10px)',
                    borderRadius: '20px', padding: '2px 10px', fontSize: '0.65rem'
                }}>
                    <span className={`pos-availability-dot ${product.quantity <= 0 ? 'out-of-stock' : ''}`} 
                          style={{ display: 'inline-block', width: '6px', height: '6px', borderRadius: '50%', background: '#34c759', marginRight: '5px' }}></span>
                    {product.quantity > 0 ? `${product.quantity} In Stock` : `Sold Out`}
                </div>
                <img
                    src={`${baseUrl}/storage/${product.image}`}
                    alt={product.name}
                    className="pos-product-img"
                    style={{ transition: 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)', padding: '15px' }}
                    loading="lazy"
                    onError={(e) => {
                        e.target.onerror = null;
                        e.target.src = `${baseUrl}/assets/images/no-image.png`;
                    }}
                />
            </div>
            <div className="p-3" style={{ borderTop: '1px solid rgba(0,0,0,0.03)' }}>
                    <div className="d-flex flex-column">
                        <h2 className="pos-product-name mb-1" title={product.name} style={{ 
                            fontSize: '0.9rem', fontWeight: '600', color: '#1d1d1f',
                            overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap'
                        }}>
                            {product.name}
                        </h2>
                        <div className="d-flex align-items-center justify-content-between">
                            <span className="pos-product-price" style={{ 
                                fontSize: '1rem', fontWeight: '700', color: 'var(--primary-color)' 
                            }}>
                                <span className="mr-1 text-muted" style={{ textDecoration: 'line-through', fontSize: '0.8rem', fontWeight: 'normal' }}>
                                    {parseFloat(product.price) > parseFloat(product.discounted_price) ? `Rs.${parseFloat(product.price).toFixed(0)}` : ''}
                                </span>
                                Rs.{parseFloat(product?.discounted_price || 0).toFixed(2)}
                            </span>
                            {parseFloat(product.discount) > 0 && (
                                <span className="badge badge-success" style={{ fontSize: '0.65rem' }}>
                                    {product.discount_type === 'percentage' ? `${parseFloat(product.discount)}%` : `Rs.${parseFloat(product.discount)}`} OFF
                                </span>
                            )}
                        </div>
                    </div>
            </div>
        </div>
        <style>{`
            .out-of-stock-card { opacity: 0.6; filter: grayscale(0.5); }
            .pos-availability-dot.out-of-stock { background-color: #ff3b30 !important; }
            .apple-card:hover .pos-product-img { transform: scale(1.08); }
        `}</style>
    </div>
));


// Skeleton Loader Component
const ProductSkeleton = () => (
    <div className="col-6 col-md-4 col-xl-3 mb-4 px-2">
        <div className="apple-card h-100" style={{ pointerEvents: 'none', border: 'none' }}>
            <div className="pos-product-img-wrapper skeleton-shimmer" style={{ height: '160px', width: '100%', borderRadius: '12px 12px 0 0' }}></div>
            <div className="p-3">
                 <div className="skeleton-shimmer mb-2" style={{ height: '18px', width: '80%', borderRadius: '4px' }}></div>
                 <div className="skeleton-shimmer" style={{ height: '22px', width: '40%', borderRadius: '4px' }}></div>
            </div>
        </div>
    </div>
);

export default function Pos() {
    const [products, setProducts] = useState([]);
    const [carts, setCarts] = useState([]);
    // Manual discount removed as per professional requirement
    const [manualDiscount, setManualDiscount] = useState('');
    const [autoRound, setAutoRound] = useState(false);
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
    const [showReceiptModal, setShowReceipt] = useState(false);
    const [receiptUrl, setReceiptUrl] = useState('');
    
    // Context Menu State
    const [contextMenu, setContextMenu] = useState(null);


    const { protocol, hostname, port } = window.location;
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(0);
    const [initialLoadDone, setInitialLoadDone] = useState(false);
    const [productCache, setProductCache] = useState({}); // Simple memory cache for Instant-On

    // Derived values
    const fullDomainWithPort = useMemo(() =>
        `${protocol}//${hostname}${port ? `:${port}` : ""}`,
        [protocol, hostname, port]
    );

    // ==========================================
    // GLOBAL SCANNER LOGIC (Professional POS)
    // Hybrid Strategy: Buffer (Fast) + Input Fallback (Slow/Reliable)
    // ==========================================
    const scanBuffer = useRef("");
    const lastKeyTime = useRef(0);
    // VERIFICATION MARKER
    console.log("ANTIGRAVITY_BUILD_VERIFICATION_ACTIVE");
    
    useEffect(() => {
        const handleGlobalKeyDown = (e) => {
            // Ignore if sensitive modal is open (like Payment)
            if (showPaymentModal) return;

            const now = Date.now();
            const timeDiff = now - lastKeyTime.current;
            lastKeyTime.current = now;

            // Reset buffer if gap is too large (increased to 200ms for slower scanners)
            if (timeDiff > 300) {
                scanBuffer.current = e.key;  // Reset buffer
            } else {
                scanBuffer.current += e.key; // Append
            }

            if (e.key === 'Enter') {
                // STRATEGY 1: Check Buffer
                let scannedCode = scanBuffer.current.trim();
                
                // STRATEGY 2: Fallback to Input Value (If Buffer was reset/empty)
                if ((!scannedCode || scannedCode.length < 3) && searchInputRef.current) {
                    scannedCode = searchInputRef.current.value.trim();
                }

                if (scannedCode.length > 2) { 
                    // Check logic
                    const product = products.find(p => 
                        p.sku?.toLowerCase() === scannedCode.toLowerCase() || 
                        p.barcode?.toLowerCase() === scannedCode.toLowerCase()
                    );
                    
                    if (product) {
                        e.preventDefault(); // Stop form submits
                        e.stopPropagation();
                        
                        addProductToCart(product.id);
                        
                        // HYPER-AGGRESSIVE CLEARING
                        // 1. Clear State
                        setSearchQuery("");
                        // 2. Clear DOM
                        if(searchInputRef.current) {
                            searchInputRef.current.value = "";
                            searchInputRef.current.focus();
                        }
                        // 3. Clear Buffer
                        scanBuffer.current = ""; 
                        
                        // 4. Double-tap Clear (Async) to beat React race conditions
                        setTimeout(() => {
                             setSearchQuery("");
                             if(searchInputRef.current) searchInputRef.current.value = "";
                        }, 50);

                        return;
                    }
                }
                scanBuffer.current = ""; // Reset on Enter anyway
            } else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) { 
                // Printable characters only
                scanBuffer.current += e.key;
            }
        };

        window.addEventListener('keydown', handleGlobalKeyDown);
        return () => window.removeEventListener('keydown', handleGlobalKeyDown);
    }, [products, showPaymentModal]); 
    
    // Normal Manual Search (Fallback - Prevent Default Only)
    const handleSearchKeyDown = (e) => {
        if(e.key === 'Enter') e.preventDefault();
    }


    // Helper: Centralized calculation to prevent logic drift
    const calculateOrderValues = useCallback(() => {
        const subTotal = parseFloat(total) || 0;
        const manual = parseFloat(manualDiscount) || 0;
        
        // Sum of net amounts in the cart
        const totalNetItems = carts.reduce((acc, item) => acc + (parseFloat(item.row_total) || 0), 0);
        
        // Final Total before rounding
        const beforeRounding = Math.max(0, totalNetItems - manual);

        // Calculate Rounding on the resulting net
        let roundDisc = 0;
        if (autoRound && beforeRounding > 0) {
            const rawDiff = beforeRounding - Math.floor(beforeRounding);
            if (rawDiff > 0) {
                roundDisc = parseFloat(rawDiff.toFixed(2));
            }
        }

        const finalTotal = parseFloat((beforeRounding - roundDisc).toFixed(2));
        
        // Manual + Rounding aggregate
        const totalExplicitDiscount = parseFloat((manual + roundDisc).toFixed(2));

        return {
            subTotal: totalNetItems,
            manualDiscount: manual,
            roundingDiscount: roundDisc,
            finalTotal,
            finalDiscount: totalExplicitDiscount 
        };
    }, [carts, total, manualDiscount, autoRound]);

    // Recalculate Final Total & Rounding using helper
    useEffect(() => {
        const { finalTotal } = calculateOrderValues();
        setUpdateTotal(finalTotal.toFixed(2));
    }, [calculateOrderValues]);

    // Fetch Products (Optimized with Caching and Pre-fetching)
    const getProducts = useCallback(async (search = "", page = 1, isPreFetch = false) => {
        if (!isPreFetch) setLoading(true);
        try {
            const cacheKey = `${search}_${page}`;
            if (productCache[cacheKey] && !isPreFetch) {
                setProducts(prev => page === 1 ? productCache[cacheKey] : [...prev, ...productCache[cacheKey]]);
                if (!isPreFetch) setLoading(false);
                return;
            }

            const isBarcode = /^\d{3,}/.test(search); 
            const params = { page };
            if (isBarcode) params.barcode = search;
            else params.search = search;

            const res = await axios.get('/admin/get/products', { params });
            let productsData = res.data;

            // Professional Partitioning: Available (Quantity > 0) items first, then Not Available.
            const sortedData = [...productsData.data].sort((a, b) => {
                if (a.quantity > 0 && b.quantity <= 0) return -1;
                if (a.quantity <= 0 && b.quantity > 0) return 1;
                return 0; // Maintain relative order (ID order) within groups
            });

            productsData.data = sortedData;

            // Cache the result
            setProductCache(prev => ({ ...prev, [cacheKey]: productsData.data }));

            if (isPreFetch) return; // Don't update state if just pre-fetching

            if (page === 1) {
                setProducts(productsData.data);

                if (productsData.data.length === 1 && isBarcode) {
                   addProductToCart(productsData.data[0].id);
                   setSearchQuery(''); 
                } else if (productsData.data.length === 0 && isBarcode) {
                    playSound(WarningSound);
                    toast.error("Product not found");
                }
            } else {
                setProducts(prev => [...prev, ...productsData.data]);
            }
            setTotalPages(productsData.meta.last_page);
        } catch (error) {
            console.error("Error fetching products:", error);
        } finally {
            if (!isPreFetch) setLoading(false);
        }
    }, [productCache]);

    // Idle Pre-fetching (Apple-style "Instant-On")
    useEffect(() => {
        const idleTimer = setTimeout(() => {
            if (!loading && currentPage < totalPages) {
                console.log("[POS]: Idle... Pre-fetching next page");
                getProducts(debouncedSearch, currentPage + 1, true);
            }
        }, 5000); // Start pre-fetching after 5s of idleness
        return () => clearTimeout(idleTimer);
    }, [loading, currentPage, totalPages, debouncedSearch, getProducts]);


    // Initial Load
    useEffect(() => {
         getProducts();
         
         // Only check journal if not already checked in this session (Tab switching vs App launch)
         const alreadyChecked = sessionStorage.getItem('pos_journal_checked');
         if (!alreadyChecked) {
             checkJournal();
         }
         
         setInitialLoadDone(true);
         // Bridge Check
         if(window.electron && window.electron.isElectron) {
             console.log("SPOS: Desktop Bridge Ready");
         }
    }, []);

    // --- KEYBOARD SHORTCUTS (Professional Workflow) ---
    useEffect(() => {
        const handleKeyDown = (e) => {
            // F1: New Sale (Reload)
            if (e.key === 'F1') {
                e.preventDefault();
                if(confirm('Start New Sale? Current cart will be cleared.')) {
                   window.location.reload();
                }
            }
            
            // F2: Focus Search
            if (e.key === 'F2') {
                e.preventDefault();
                document.getElementById('product-search-input')?.focus();
            }

            // F10: Checkout / Pay
            if (e.key === 'F10') {
                e.preventDefault();
                // Check if cart has items
                if(cart.length > 0) {
                   handleCheckoutModal();
                } else {
                   toast.error('Cart is empty!');
                }
            }

             // F4: Customer Search (Optional add-on)
             if (e.key === 'F4') {
                e.preventDefault();
                document.querySelector('.select2-selection')?.click(); // Trigger Select2
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [cart]); // Re-bind if cart changes (for Checkout check)
    const checkJournal = async () => {
        try {
            const res = await axios.get('/admin/cart/check-journal');
            if (res.data?.exists && res.data.data.items.length > 0) {
                const journal = res.data.data;
                const lastItems = journal.items.length;
                
                // Set flag immediately to prevent re-popups during tab switching 
                // but keep it in database until Decision is made
                sessionStorage.setItem('pos_journal_checked', 'true');

                Swal.fire({
                    title: 'Interrupted Session Found!',
                    text: `Restore ${lastItems} items from previous session?`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Restore',
                    cancelButtonText: 'Discard',
                    confirmButtonColor: '#800000',
                    allowOutsideClick: false // Ensure professional decision flow
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        const toastId = toast.loading('Restoring session...');
                        try {
                            // 1. Clear existing cart physically to prevent incrementing existing DB items
                            await axios.put("/admin/cart/empty");

                            // 2. Restore items with correct Quantity and Custom Prices
                            for (const item of journal.items) {
                                // Add item first (Creates row with Qty 1)
                                const addRes = await axios.post('/admin/cart', { id: item.product_id });
                                
                                if (addRes.data?.cart?.id) {
                                    const cartId = addRes.data.cart.id;

                                    // If quantity differs from 1 (or is fractional), update it
                                    if (parseFloat(item.quantity) !== 1) {
                                        await axios.put("/admin/cart/update-quantity", { 
                                            id: cartId, 
                                            quantity: item.quantity 
                                        });
                                    }
                                    
                                    // If price was customized (e.g. Price overrides), restore it
                                    if (item.price_override !== undefined && item.price_override !== null) {
                                        await axios.put("/admin/cart/update-rate", { 
                                            id: cartId, 
                                            price: item.price_override 
                                        });
                                    }
                                }
                            }
                            setCartUpdated(!cartUpdated);
                            toast.success('Session restored successfully!', { id: toastId });
                        } catch (e) {
                            console.error(e);
                            toast.error('Restoration failed. Please try fresh.', { id: toastId });
                        }
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // DISCARD: Must clear the cart physically from backend
                        axios.put("/admin/cart/empty").then(() => {
                            setCartUpdated(!cartUpdated);
                            toast.success("Previous session discarded");
                        });
                    }
                });
            } else {
                // If no journal exists, still set the flag so we don't hit the DB again
                sessionStorage.setItem('pos_journal_checked', 'true');
            }
        } catch (e) {
            console.error("Journal check failed", e);
        }
    };



    // Fetch Cart
    const getCarts = async () => {
        try {
            const res = await axios.get('/admin/cart');
            
            // Apply price overrides to product price for display
            const processedCarts = (res.data?.carts || []).map(item => {
                if (item.price_override !== null && item.price_override !== undefined) {
                    return {
                        ...item,
                        product: { ...item.product, price: item.price_override }
                    };
                }
                return item;
            });

            setTotal(res.data?.total);
            setCarts(processedCarts);
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


    // Optimized Cart Actions
    
    // Helper to calculate total from carts array
    const calculateCartTotal = (cartItems) => {
        return cartItems.reduce((sum, item) => {
            const price = parseFloat(item.product.discounted_price) || 0;
            const qty = parseFloat(item.quantity) || 0;
            const rowTotal = parseFloat((price * qty).toFixed(2));
            return sum + rowTotal;
        }, 0);
    };

    const updateCartOptimistically = (newCarts) => {
        setCarts(newCarts);
        setTotal(calculateCartTotal(newCarts));
    };

    // 1. Add Product (Optimistic)
    const addProductToCart = useCallback((id) => {
        const product = products.find(p => p.id === id);
        if(!product) return;

        const existingItemIndex = carts.findIndex(c => c.product_id === id);
        const prevCarts = [...carts];
        const prevTotal = total;

        let newCarts = [...carts];

        if(existingItemIndex >= 0) {
            // Increment existing
            const item = { ...newCarts[existingItemIndex] };
            if(parseFloat(item.quantity) >= parseFloat(product.quantity)) {
                playSound(WarningSound);
                toast.error(`Only ${product.quantity} units available in stock`);
                return;
            }

            item.quantity = parseFloat(item.quantity) + 1;
            item.row_total = (item.quantity * item.product.discounted_price).toFixed(2);
            newCarts[existingItemIndex] = item;
            updateCartOptimistically(newCarts);
            playSound(SuccessSound);
        } else {
            // Add new with temporary ID
            const tempId = `temp-${Date.now()}`;
            const newItem = {
                id: tempId,
                product_id: product.id,
                quantity: 1,
                row_total: product.discounted_price,
                product: product
            };
            newCarts = [newItem, ...carts];
            updateCartOptimistically(newCarts);
            playSound(SuccessSound);
            
            // Sync with server and replace tempId
            axios.post("/admin/cart", { id })
                .then((res) => {
                    const realItem = res.data.cart; // Assuming backend returns the new cart item
                    if (realItem) {
                        // Ensure row_total is set correctly from backend, or fallback to calculation
                        if (!realItem.row_total) {
                            realItem.row_total = (parseFloat(realItem.quantity) * parseFloat(realItem.product.discounted_price)).toFixed(2);
                        }
                        
                        setCarts(currentCarts => 
                            currentCarts.map(item => item.id === tempId ? realItem : item)
                        );
                    } else {
                        setCartUpdated(prev => !prev);
                    }
                    toast.success("Added to cart");
                })
                .catch((err) => {
                    setCarts(prevCarts);
                    setTotal(prevTotal);
                    playSound(WarningSound);
                    toast.error(err.response?.data?.message || "Error adding item");
                });
            return;
        }

        // Server Sync for existing item
        axios.post("/admin/cart", { id })
            .catch((err) => {
                setCarts(prevCarts);
                setTotal(prevTotal);
                playSound(WarningSound);
                toast.error(err.response?.data?.message || "Sync Error");
            });

    }, [carts, products, total]);

    // 2. Increment (Optimistic)
    const handleIncrement = (cartId) => {
        const index = carts.findIndex(c => c.id === cartId);
        if(index < 0) return;

        const item = { ...carts[index] };
        if(item.product.quantity > 0 && parseFloat(item.quantity) >= parseFloat(item.product.quantity)) {
             toast.error(`Only ${item.product.quantity} units available`);
             return;
        }

        const prevCarts = [...carts];
        const prevTotal = total;

        const newCarts = [...carts];
        item.quantity = parseFloat(item.quantity) + 1;
        item.row_total = (item.quantity * item.product.discounted_price).toFixed(2);
        newCarts[index] = item;
        
        updateCartOptimistically(newCarts);

        axios.put("/admin/cart/increment", { id: cartId }).catch(err => {
            setCarts(prevCarts);
            setTotal(prevTotal);
            toast.error("Sync failed");
        });
    };

    // 3. Decrement (Optimistic)
    const handleDecrement = (cartId) => {
        const index = carts.findIndex(c => c.id === cartId);
        if(index < 0) return;

        const item = { ...carts[index] };
        if(item.quantity <= 1) return; // Use delete for 0

        const prevCarts = [...carts];
        const prevTotal = total;

        const newCarts = [...carts];
        item.quantity = parseFloat(item.quantity) - 1;
        item.row_total = (item.quantity * item.product.discounted_price).toFixed(2);
        newCarts[index] = item;
        
        updateCartOptimistically(newCarts);

        axios.put("/admin/cart/decrement", { id: cartId }).catch(err => {
             setCarts(prevCarts);
             setTotal(prevTotal);
             toast.error("Sync failed");
        });
    };

    // 4. Remove (Optimistic)
    const handleRemove = (cartId) => {
         const prevCarts = [...carts];
         const prevTotal = total;
         
         const newCarts = carts.filter(c => c.id !== cartId);
         updateCartOptimistically(newCarts);
         playSound(WarningSound); // Delete sound

         axios.put("/admin/cart/delete", { id: cartId }).catch(err => {
             setCarts(prevCarts);
             setTotal(prevTotal);
             toast.error("Delete failed");
         });
    };
    
    // 5. Update Quantity (Direct)
    const handleUpdateQuantity = (cartId, qty) => {
        const index = carts.findIndex(c => c.id === cartId);
        if(index < 0) return;
        
        const prevCarts = [...carts];
        const prevTotal = total;
        
        const newCarts = [...carts];
        const item = { ...newCarts[index] };
        item.quantity = parseFloat(qty);
        item.row_total = (item.quantity * item.product.discounted_price).toFixed(2);
        newCarts[index] = item;
        
        updateCartOptimistically(newCarts);
        
        axios.put("/admin/cart/update-quantity", { id: cartId, quantity: qty }).catch(err => {
            setCarts(prevCarts);
            setTotal(prevTotal);
            toast.error(err.response?.data?.message || "Update failed");
        });
    };

    // 6. Update By Price (Optimistic)
    const handleUpdateByPrice = (cartId, targetPrice) => {
        const index = carts.findIndex(c => c.id === cartId);
        if(index < 0) return;
        
        const prevCarts = [...carts];
        const prevTotal = total;
        
        const newCarts = [...carts];
        const item = { ...newCarts[index] };
        
        // Optimization: Use Original Price if it's weight-based to calculate Qty correctly
        const unitPrice = parseFloat(item.product.price) || 0;
        
        if(unitPrice <= 0) return;
        
        item.row_total = parseFloat(targetPrice).toFixed(2);
        item.quantity = (parseFloat(targetPrice) / unitPrice).toFixed(3);
        newCarts[index] = item;
        
        updateCartOptimistically(newCarts);
        
        axios.put("/admin/cart/update-by-price", { id: cartId, price: targetPrice }).catch(err => {
            setCarts(prevCarts);
            setTotal(prevTotal);
            toast.error(err.response?.data?.message || "Update failed");
        });
    };

    // 7. Update Rate (Price Override)
    const handleUpdateRate = (cartId, newRate) => {
        const index = carts.findIndex(c => c.id === cartId);
        if(index < 0) return;

        const prevCarts = [...carts];
        const prevTotal = total;

        const newCarts = [...carts];
        const item = { ...newCarts[index] };
        
        // Note: This only overrides the ORIGINAL PRICE for the row total calculation.
        // If there's a discount, it might need to stay or be cleared.
        // User said: "showing the price as the original price and amount when the discount applied"
        // This implies the 'Rate' is the base.
        
        const rate = parseFloat(newRate);
        if(isNaN(rate) || rate < 0) return;

        // Apply same discount logic if it exists? 
        // For simplicity, if they override the rate, we keep the existing "Amount" behavior.
        // If they want to override the base price, the product resource stores it.
        // We need a way to tell the backend about this override.
        // Since pos_carts doesn't have a price column, we'll use a hack or add the column.
        // User said "leave this feature" regarding the column, so we'll just handle it in UI/Calculation
        // but it won't persist across refreshes without the column.
        
        item.product = { ...item.product, price: rate };
        item.row_total = (rate * item.quantity).toFixed(2); // Default to no discount if rate is manual?
        // Actually, let's keep it simple: Rate * Qty = Amount.
        
        newCarts[index] = item;
        updateCartOptimistically(newCarts);
        
        axios.put("/admin/cart/update-rate", { id: cartId, price: newRate }).catch(err => {
            setCarts(prevCarts);
            setTotal(prevTotal);
            toast.error(err.response?.data?.message || "Price sync failed");
        });
    };

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
                        setManualDiscount('');
                        setAutoRound(false);
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
        
        // Calculate Final Discount for Backend using unified helper
        const { finalDiscount } = calculateOrderValues();

        // Finalize Order
        axios.put("/admin/order/create", {
            customer_id: customerId,
            order_discount: finalDiscount,
            paid: parseFloat(paid) || 0,
            payment_method: method,
            transaction_id: trxId,
            items: carts.map(item => ({
                id: item.product_id,
                qty: item.quantity,
                price: item.product.price, // Overridden or original
                row_total: item.row_total
            }))
        })
        .then((res) => {
            setShowPaymentModal(false);
            
            // Allow state to clear instantly for UX
            setCarts([]);
            setTotal(0);
            setUpdateTotal(0);
            setManualDiscount('');
            setAutoRound(false);
            setCartUpdated(!cartUpdated);

            // Show Receipt
            const orderId = res.data?.order?.id;
            if (orderId) {
                const url = window.location.origin + `/admin/orders/pos-invoice/${orderId}`;
                const apiUrl = `/admin/orders/receipt-details/${orderId}`;
                
                // 1. AUTO-PRINT (Background)
                if (window.electron && window.electron.printSilent) {
                     // Fetch JSON specifically for the "Headless" Engine
                     axios.get(apiUrl).then(jsonRes => {
                         const jsonData = jsonRes.data?.data;
                         if(jsonData) {
                             const toastId = toast.loading("Auto-Printing...");
                             // Pass jsonData for Raw Engine
                             window.electron.printSilent(url, window.posSettings?.receiptPrinter, null, jsonData)
                                 .then(pRes => {
                                     if(pRes.success) toast.success("Receipt Sent to Printer", { id: toastId });
                                     else toast.error("Print Error: " + pRes.error, { id: toastId });
                                 })
                                 .catch(e => console.error("AutoPrint Error", e));
                         }
                     }).catch(e => console.error("Fetch Error", e));
                }

                // 2. SHOW PREVIEW (Disabled per user request)
                // setReceiptUrl(url);
                // setShowReceiptModal(true);
            }
            
            // Refresh Products (stock update)
            
            // Refresh Products (stock update)
            getProducts(debouncedSearch, currentPage);
            toast.success(`Order #${orderId} Created Successfully!`);

        }).catch(err => {
            console.error("Order Creation Failed", err);
            toast.error(err.response?.data?.message || "Order Creation Failed: Check Console");
        });
    };

    // Headless Print Helper
    const handleReceiptPrint = async (url) => {
        if (window.electron && window.electron.printSilent) {
             const toastId = toast.loading("Printing Receipt...");
             try {
                 // Convert URL to API for JSON
                 let targetUrl = url;
                 if (!url.startsWith('http')) {
                    const { protocol, host } = window.location;
                    targetUrl = `${protocol}//${host}${url}`;
                 }
                 const invoiceMatch = targetUrl.match(/pos-invoice\/(\d+)/);
                 if (invoiceMatch && invoiceMatch[1]) {
                     const orderId = invoiceMatch[1];
                     const jsonResp = await axios.get(`/admin/orders/receipt-details/${orderId}`);
                     if(jsonResp.data?.data) {
                         await window.electron.printSilent(
                             targetUrl, 
                             window.posSettings?.receiptPrinter, 
                             null, 
                             null // FORCE URL MODE: Disable JSON/Headless to fix "Inch Print" issue
                         );
                         toast.success("Printed Successfully", { id: toastId });
                     }
                 }
             } catch (e) {
                 console.error("Auto-Print Failed", e);
                 toast.error("Print Failed", { id: toastId });
             }
        }
    };

    // Context Menu Handlers
    const handleContextMenu = (e, product) => {
        e.preventDefault();
        // Adjust coordinates for the 0.9 zoom factor used in pos-app-container
        const zoom = 0.9;
        setContextMenu({
            product,
            x: e.clientX / zoom,
            y: e.clientY / zoom
        });
    };


    const handleContextAction = (action) => {
        const product = contextMenu.product;
        if (action === 'edit') {
            window.location.href = `/admin/products/${product.id}/edit`;
        } else if (action === 'print') {
            // Simplified print tag logic using existing utility if possible, 
            // otherwise navigate to tag print page
             window.location.href = `/admin/barcode/print?label=${product.name}&barcode=${product.sku}&size=large`;
        } else if (action === 'purchase') {
            window.location.href = `/admin/purchase/create?search=${encodeURIComponent(product.name)}&barcode=${product.sku}`;
        }
        setContextMenu(null);
    };

    // Hotkeys Ref

    const searchInputRef = useRef(null);

    useEffect(() => {
        const handleKeyDown = (e) => {
            // F2: Focus Search
            if (e.key === 'F2') {
                e.preventDefault();
                searchInputRef.current?.focus();
            }

            // F10: Pay (Standard POS key)
            if (e.key === 'F10') {
                e.preventDefault();
                handleCheckoutClick();
            }
            
            // Esc: Close Modals
            if (e.key === 'Escape') {
                e.preventDefault();
                setShowPaymentModal(false);
                setShowReceiptModal(false);
            }

            if ((e.ctrlKey || e.metaKey) && (e.key === 'Delete' || e.key === 'Backspace')) {
                e.preventDefault();
                cartEmpty();
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [total, customerId, showPaymentModal, showReceiptModal]);

    // Render
    return (
        <ErrorBoundary>
        <div className="pos-app-container">
            <Toaster position="top-right" containerStyle={{ zIndex: 99999 }} />
            {/* LEFT PANEL: PRODUCTS */}
            <div className="pos-left-panel d-flex flex-column border-right bg-white">
                {/* Header / Search */}
                <div className="p-3 border-bottom shadow-sm bg-light">
                    <div className="d-flex align-items-center justify-content-between">
                        <div className="d-flex align-items-center flex-grow-1 mr-3">
                            <div className="input-group apple-input-group shadow-none" style={{ background: '#f5f5f7', borderRadius: '12px', border: '1px solid rgba(0,0,0,0.05)', overflow: 'hidden' }}>
                                <div className="input-group-prepend">
                                    <span className="input-group-text bg-transparent border-0 pr-0" style={{ fontSize: '0.8rem', color: '#8e8e93', fontWeight: 'bold' }}><i className="fas fa-search"></i></span>
                                </div>
                                <input 
                                    id="product-search-input"
                                    type="text" 
                                    className="form-control border-0 bg-transparent font-weight-bold" 
                                    style={{ fontSize: '1.2rem', color: '#1d1d1f', boxShadow: 'none' }}
                                    placeholder="Scan Barcode or Search (F2)..."
                                    value={searchQuery}
                                    ref={searchInputRef}
                                    autoComplete="off"
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                            </div>
                            <button 
                                onClick={() => {
                                    const isDesktop = window.electron && window.electron.isElectron;
                                    if(isDesktop) {
                                        window.electron.openDrawer(window.posSettings?.receiptPrinter);
                                        toast.success("Drawer Signal Sent", {
                                            style: { borderRadius: '15px', background: '#333', color: '#fff', fontSize: '13px' }
                                        });
                                    } else {
                                        toast.error("Desktop Only feature");
                                    }
                                }}
                                className="btn btn-apple ml-3 d-flex align-items-center"
                                style={{ 
                                    height: '52px', background: '#fff', border: '1px solid rgba(0,0,0,0.05)', 
                                    borderRadius: '26px', padding: '0 25px', boxShadow: '0 4px 12px rgba(0,0,0,0.03)'
                                }}
                            >
                                <i className="fas fa-cash-register mr-2" style={{ color: 'var(--primary-color)' }}></i>
                                <span style={{ fontWeight: '700', fontSize: '0.75rem', letterSpacing: '0.05em' }}>OPEN DRAWER</span>
                            </button>
                        </div>
                        
                        {/* Hardware Status Monitor - Removed redundant local instance, moved to Global Navbar */}
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
                                onContextMenu={handleContextMenu}
                                baseUrl={fullDomainWithPort} 
                            />
                        ))}

                        {products.length === 0 && !loading && (
                            <div className="col-12">
                                <div className="empty-state-container mx-auto" style={{ maxWidth: '400px' }}>
                                    <i className="fas fa-box-open fa-4x mb-3 text-muted" style={{ opacity: 0.3 }}></i>
                                    <h5 className="text-muted font-weight-bold">No products found</h5>
                                    <p className="text-muted small">Try a different search term or check stock.</p>
                                </div>
                            </div>
                        )}
                        {loading && Array.from({ length: 8 }).map((_, i) => (
                             <ProductSkeleton key={i} />
                        ))}
                    </div>
                </div>
            </div>

            {/* Context Menu Overlay */}
            {contextMenu && (
                <ContextMenu 
                    {...contextMenu} 
                    onAction={handleContextAction} 
                    onClose={() => setContextMenu(null)} 
                />
            )}


            {/* RIGHT PANEL: CART & CHECKOUT */}
            <div className="pos-right-panel d-flex flex-column bg-white shadow-md" style={{ zIndex: 10, borderLeft: 'var(--apple-border)' }}>

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
                            onIncrement={handleIncrement}
                            onDecrement={handleDecrement}
                            onDelete={handleRemove}
                            onUpdateQty={handleUpdateQuantity}
                            onUpdatePrice={handleUpdateByPrice}
                            onUpdateRate={handleUpdateRate}
                        />
                    </div>
                </div>

                {/* Footer: Totals & Actions */}
                <div className="border-top p-3" style={{ fontSize: '1.rem', backgroundColor: 'var(--apple-bg)' }}>
                     {/* Professional Unified Discount Toolbar */}
                     <div className="p-0 rounded shadow-sm border mb-3 overflow-hidden d-flex align-items-center" style={{ height: '70px', backgroundColor: 'var(--pastel-bg)' }}>
                        {/* Manual Discount Input Area */}
                        <div className="flex-grow-1 d-flex flex-column justify-content-center px-3 border-right" style={{ height: '100%' }}>
                            <div className="d-flex justify-content-between align-items-center">
                                <small className="text-muted font-weight-bold text-uppercase" style={{ fontSize: '0.7rem', letterSpacing: '1px' }}>
                                    Manual Discount
                                </small>
                            </div>
                            <div className="d-flex align-items-baseline">
                                <span className="text-muted mr-1" style={{ fontSize: '1rem' }}>{window.posSettings?.currencySymbol || 'Rs.'}</span>
                                <input 
                                    type="number" 
                                    className="form-control-plaintext font-weight-bold text-dark h-auto p-0" 
                                    style={{ fontSize: '1.4rem', border: 'none', outline: 'none', background: 'transparent' }}
                                    placeholder="0"
                                    value={manualDiscount}
                                    onChange={(e) => setManualDiscount(e.target.value)}
                                    onFocus={(e) => e.target.select()}
                                    disabled={total <= 0}
                                />
                            </div>
                        </div>
                        {/* Auto-Round Toggle Area */}
                        <div 
                            className="d-flex flex-column align-items-center justify-content-center px-3 cursor-pointer hover-bg-light" 
                            style={{ height: '100%', minWidth: '100px', backgroundColor: autoRound ? '#fff5f5' : 'transparent', userSelect: 'none' }}
                            onClick={(e) => {
                                // Prevent default if coming from label to avoid double toggle
                                e.preventDefault();
                                setAutoRound(prev => !prev);
                            }}
                        >
                            <div className="custom-control custom-switch custom-switch-maroon mb-1" style={{ pointerEvents: 'none' }}>
                                <input 
                                    type="checkbox" 
                                    className="custom-control-input" 
                                    checked={autoRound} 
                                    readOnly
                                />
                                <label className="custom-control-label"></label>
                            </div>
                            <small className={`font-weight-bold ${autoRound ? 'text-maroon' : 'text-muted'}`} style={{ fontSize: '0.7rem' }}>
                                FRACTIONAL DISCOUNT {autoRound && calculateOrderValues().roundingDiscount > 0 && <span>(-{calculateOrderValues().roundingDiscount})</span>}
                            </small>
                        </div>
                     </div>

                    {/* Grand Total - Focal Point Refinement */}
                    <div className="d-flex justify-content-between align-items-center mb-3 p-3 bg-gradient-maroon rounded shadow-md" style={{ borderRadius: 'var(--radius-md) !important' }}>
                        <div className="line-height-1">
                            <small className="text-white-50 text-uppercase font-weight-bold" style={{ fontSize: '0.7em', letterSpacing: '1px' }}>Total Payable</small>
                            <h4 className="m-0 text-white font-weight-light">{window.posSettings?.currencySymbol || 'PKR'}</h4>
                        </div>
                        <h1 className="m-0 text-white font-weight-bold" style={{ fontSize: '3.8rem', letterSpacing: '-1px' }}>{Math.max(0, updateTotal)}</h1>
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
                                className="btn btn-pay-premium btn-block py-3 font-weight-bold shadow-lg"
                                style={{ fontSize: '1.4rem', borderRadius: '12px', height: '100%' }}
                                disabled={total <= 0}
                                title="Shortcut: F10"
                            >
                                <div className="d-flex justify-content-center align-items-center">
                                    <div className="text-left line-height-1 mr-3">
                                        <small className="d-block font-weight-normal opacity-75" style={{ fontSize: '0.6em', textTransform: 'uppercase', letterSpacing: '1px' }}>Process Order</small>
                                        PAY NOW
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
                }}
            />
        </div>
        </ErrorBoundary>
    );
}
