/**
 * PosMain.jsx - Professional POS Interface
 * Last Updated: 2026-01-30 - Scanner V9 Global Fix
 * 
 * Key Features:
 * - Global barcode scanner (works regardless of focus)
 * - Speed-based scanner detection (<80ms = scanner)
 * - Visual feedback when scanning
 */
import React, { useEffect, useState, useCallback, useMemo, useRef, memo } from "react";
import axios from "axios";
import Swal from "sweetalert2";
import Cart from "./Cart";
import toast, { Toaster } from "react-hot-toast";
import CustomerSelect from "./CustomerSelect";
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
        onClick={() => onClick({ product })}
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
                    {product.quantity > 0 ? `${parseFloat(product.quantity).toFixed(2)} In Stock` : `Sold Out`}
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
                                    {parseFloat(product.price) > parseFloat(product.discounted_price) ? `${window.posSettings?.currencySymbol ?? 'Rs.'}${parseFloat(product.price).toFixed(0)}` : ''}
                                </span>
                                {window.posSettings?.currencySymbol ?? 'Rs.'}{parseFloat(product?.discounted_price || 0).toFixed(2)}
                            </span>
                            {parseFloat(product.discount) > 0 && (
                                <span className="badge badge-success" style={{ fontSize: '0.65rem' }}>
                                    {product.discount_type === 'percentage' ? `${parseFloat(product.discount)}%` : `${window.posSettings?.currencySymbol ?? 'Rs.'}${parseFloat(product.discount)}`} OFF
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
    const isProcessing = useRef(false);
    const pendingDeletions = useRef(new Set()); 
    const scanBuffer = useRef("");
    const lastKeyTime = useRef(0);
    const searchInputRef = useRef(null);
    const audioRef = useRef(null);

    // Derived values
    const fullDomainWithPort = useMemo(() =>
        `${protocol}//${hostname}${port ? `:${port}` : ""}`,
        [protocol, hostname, port]
    );

    // (Global Scanner Logic moved below addProductToCart to avoid TDZ)
    
    // Normal Manual Search (Fallback - Prevent Default Only)
    const handleSearchKeyDown = (e) => {
        if(e.key === 'Enter') e.preventDefault();
    }


    // Helper: Centralized calculation to prevent logic drift
    const calculateOrderValues = useCallback(() => {
        const totalNetItems = carts.reduce((acc, item) => 
            acc + (parseFloat(item.row_total) || 0), 0);
        
        // CRITICAL FIX: Parse manualDiscount to number to prevent string concatenation bug
        const manualDiscountNum = parseFloat(manualDiscount) || 0;
        const beforeRounding = Math.max(0, totalNetItems - manualDiscountNum);
        
        // Fractional Rounding (if enabled)
        let roundDisc = 0;
        if (autoRound && beforeRounding > 0) {
            roundDisc = beforeRounding - Math.floor(beforeRounding);
        }
        
        return {
            finalTotal: beforeRounding - roundDisc,
            finalDiscount: manualDiscountNum + roundDisc  // FIXED: Now adding numbers, not concatenating strings
        };
    }, [carts, manualDiscount, autoRound]);

    // Recalculate Final Total & Rounding using helper
    useEffect(() => {
        const { finalTotal } = calculateOrderValues();
        setUpdateTotal(finalTotal.toFixed(2));
    }, [calculateOrderValues]);

    // Fetch Products (Optimized with Caching and Pre-fetching)
    const getProducts = useCallback(async (search = "", page = 1, isPreFetch = false, forceRefresh = false) => {
        if (!isPreFetch) setLoading(true);
        try {
            const cacheKey = `${search}_${page}`;
            
            // NUKE LOGIC: If forceRefresh is true, skip the cache check entirely
            if (!forceRefresh && productCache[cacheKey] && !isPreFetch) {
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
                   addProductToCart(productsData.data[0]); // Pass Full Object
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
         // Bridge: Desktop-only features are handled by Electron IPC in preload.cjs
    }, []);
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

                                    // If row_total_override exists (user modified Amount column), restore it
                                    if (item.row_total_override !== undefined && item.row_total_override !== null) {
                                        await axios.put("/admin/cart/update-by-price", { 
                                            id: cartId, 
                                            price: item.row_total_override 
                                        });
                                    } else {
                                        // Otherwise restore quantity and rate separately
                                        if (parseFloat(item.quantity) !== 1) {
                                            await axios.put("/admin/cart/update-quantity", { 
                                                id: cartId, 
                                                quantity: item.quantity 
                                            });
                                        }
                                        
                                        // If price was customized, restore it
                                        if (item.price_override !== undefined && item.price_override !== null) {
                                            await axios.put("/admin/cart/update-rate", { 
                                                id: cartId, 
                                                price: item.price_override 
                                            });
                                        }
                                    }
                                }
                            }
                            
                            // 3. CRITICAL: Delete journal after successful restore to prevent re-triggering
                            await axios.delete("/admin/cart/delete-journal");
                            
                            setCartUpdated(!cartUpdated);
                            toast.success('Session restored successfully!', { id: toastId });
                        } catch (e) {
                            toast.error('Restoration failed. Please try fresh.', { id: toastId });
                        }
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // DISCARD: Cart is already cleared by empty() which also deletes journal
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


    // 1. Universal Add Product (Professional Protocol)
    const addProductToCart = useCallback((params) => {
        // params can be:
        // 1. { product: Object } - from clicking a product card
        // 2. { id: number } - direct ID
        // 3. { barcode: string } - from scanner
        const { id, barcode, product: directProduct } = params;
        
        // Clean and validate inputs
        const cleanBarcode = (barcode && typeof barcode === 'string') ? barcode.trim() : null;
        const cleanId = (id !== undefined && id !== null) ? parseInt(id) : null;
        
        // If we have a direct product object, use its ID
        const productId = directProduct?.id || cleanId;
        
        // VALIDATION: Must have either product, id, or barcode
        if (!productId && !cleanBarcode) {
            playSound(WarningSound);
            toast.error("Unable to add product - no identifier");
            return;
        }
        
        // Find product locally for Optimistic UI
        const product = directProduct || products.find(p => 
            (productId && p.id == productId) || 
            (cleanBarcode && (p.sku === cleanBarcode || p.barcode === cleanBarcode))
        );
        
        // Play Sound 
        playSound(SuccessSound);

        // Optimistic UI Block
        if (product) {
            const existingItemIndex = carts.findIndex(c => c.product_id === product.id);
            const tempId = existingItemIndex >= 0 ? carts[existingItemIndex].id : `opt-${Date.now()}`;
            
            let newCarts = [...carts];
            if (existingItemIndex >= 0) {
                const item = { ...newCarts[existingItemIndex] };
                item.quantity = parseFloat(item.quantity) + 1;
                item.row_total = (item.quantity * item.product.discounted_price).toFixed(2);
                newCarts[existingItemIndex] = item;
            } else {
                const newItem = {
                    id: tempId,
                    product_id: product.id,
                    quantity: 1,
                    row_total: product.discounted_price,
                    product: product
                };
                newCarts = [newItem, ...carts];
            }
            setCarts(newCarts);
            setTotal(calculateCartTotal(newCarts));
        }

        // Server Sync (Reliable & Professional)
        // Priority: productId (from direct product or cleaned id) > cleanBarcode
        let postData = {};
        if (productId) {
            postData.id = productId;
        } else if (cleanBarcode) {
            postData.barcode = cleanBarcode;
        }

        // Safety check (should never happen due to validation above)
        if (!postData.id && !postData.barcode) {
            console.error("UniversalSync: No valid data to send", { productId, cleanBarcode });
            return;
        }

        axios.post("/admin/cart", postData)
            .then((res) => {
                // If it was a new item, refresh state to get real DB data (ID, product details, etc.)
                if (res.data?.cart) {
                    toast.success(res.data.message || "Item Added", { duration: 1000 });
                }
                
                // CRITICAL: Clear product cache so stock quantities refresh immediately
                setProductCache({});
                
                getCarts(); // Consistent refresh
            })
            .catch((err) => {
                playSound(WarningSound);
                const msg = err.response?.data?.message || "Scan/Add Failed";
                toast.error(msg);
                // getCarts(); // Rollback if needed
            });
    }, [carts, products, calculateCartTotal, getCarts]);

    // ==========================================
    // GLOBAL SCANNER LOGIC (Professional V9)
    // Key Features:
    // 1. Works regardless of focus (truly global)
    // 2. Detects scanner by keystroke speed (< 80ms = scanner)
    // 3. Increased buffer timeout (400ms) for slower USB scanners
    // 4. Visual feedback when scanner is detected
    // 5. Supports both buffer capture and input fallback
    // ==========================================

    const [scannerActive, setScannerActive] = useState(false);
    const scannerTimeoutRef = useRef(null);
    const consecutiveFastKeys = useRef(0);

    useEffect(() => {
        const handleUnifiedKeyDown = (e) => {
            const currentTime = Date.now();
            const timeDiff = currentTime - lastKeyTime.current;
            lastKeyTime.current = currentTime;

            // Skip if payment modal is open (user is typing payment details)
            if (showPaymentModal) return;

            // 1. HOTKEYS (Always work, highest priority)
            if (e.key === 'F2') {
                e.preventDefault();
                e.stopImmediatePropagation();
                searchInputRef.current?.focus();
                searchInputRef.current?.select();
                return;
            }
            if (e.key === 'F10') {
                e.preventDefault();
                e.stopImmediatePropagation();
                handleCheckoutClick();
                return;
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopImmediatePropagation();
                setShowPaymentModal(false);
                setShowReceiptModal(false);
                // Also clear any partial scan
                scanBuffer.current = "";
                setScannerActive(false);
                return;
            }
            // Ctrl+Backspace or Ctrl+Delete: Clear cart
            if ((e.ctrlKey || e.metaKey) && (e.key === 'Delete' || e.key === 'Backspace')) {
                e.preventDefault();
                if (carts.length > 0) {
                    cartEmpty();
                }
                return;
            }

            // 2. SCANNER DETECTION (Speed-based, focus-independent)
            const target = e.target;
            const isInput = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA';
            const isSearchInput = target.id === 'product-search-input' || target.classList.contains('allow-scanner');
            
            // Check if user is editing cart fields (quantity, rate, amount inputs in cart table)
            const isCartInput = isInput && (
                target.closest('.user-cart') || 
                target.closest('.apple-table') ||
                target.classList.contains('form-control-sm')
            ) && !isSearchInput;
            
            // Track consecutive fast keystrokes to detect scanner
            if (timeDiff < 80) {
                consecutiveFastKeys.current++;
            } else {
                consecutiveFastKeys.current = 0;
            }
            
            // Scanner detected: 3+ consecutive fast keys
            const isScannerInput = consecutiveFastKeys.current >= 2 || timeDiff < 50;
            
            // Show visual feedback when scanner is detected
            if (isScannerInput && !scannerActive) {
                setScannerActive(true);
            }
            
            // Clear scanner indicator after 600ms of no input
            if (scannerTimeoutRef.current) clearTimeout(scannerTimeoutRef.current);
            scannerTimeoutRef.current = setTimeout(() => {
                setScannerActive(false);
                consecutiveFastKeys.current = 0;
            }, 600);

            // 3. ENTER KEY: Process scan or manual search
            if (e.key === 'Enter') {
                // IMPORTANT: If user is in a cart input field (qty/rate/amount), 
                // let the input's native handler work (blur to save)
                if (isCartInput && !isScannerInput) {
                    // Don't intercept - let the input handle Enter naturally
                    // The input's onKeyDown will blur() which triggers onBlur to save
                    return;
                }
                
                e.preventDefault();
                e.stopImmediatePropagation();

                const bufferCode = scanBuffer.current.trim();
                const inputCode = searchInputRef.current?.value?.trim() || '';
                
                // Priority 1: Buffer code (scanner captured it globally)
                // Priority 2: Search input value (manual entry or fallback)
                const code = bufferCode.length >= 3 ? bufferCode : inputCode;
                
                if (code.length >= 3) {
                    // Process the barcode/search
                    addProductToCart({ barcode: code });
                    
                    // Clear everything
                    scanBuffer.current = "";
                    if (searchInputRef.current) {
                        searchInputRef.current.value = "";
                    }
                    setSearchQuery("");
                    setScannerActive(false);
                    consecutiveFastKeys.current = 0;
                    
                    // Refocus search for next scan
                    setTimeout(() => searchInputRef.current?.focus(), 50);
                }
                
                scanBuffer.current = "";
                return;
            }

            // 4. CHARACTER INPUT: Build buffer
            if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                // Buffer timeout: 400ms (accommodates slower USB scanners)
                if (timeDiff > 400) {
                    scanBuffer.current = e.key;
                } else {
                    scanBuffer.current += e.key;
                }
                
                // If scanner input detected and typing into non-search input, 
                // prevent character from appearing there (redirect to buffer only)
                if (isScannerInput && isInput && !isSearchInput) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        };

        // Use capture phase to intercept before any other handlers
        window.addEventListener('keydown', handleUnifiedKeyDown, { capture: true });
        return () => {
            window.removeEventListener('keydown', handleUnifiedKeyDown, { capture: true });
            if (scannerTimeoutRef.current) clearTimeout(scannerTimeoutRef.current);
        };
    }, [showPaymentModal, showReceiptModal, addProductToCart, carts.length]); 

    // 2. Increment (Unified Server-First)
    const handleIncrement = (cartId) => {
        if (isProcessing.current) return;
        isProcessing.current = true;
        
        axios.put("/admin/cart/increment", { id: cartId })
            .then(() => {
                getCarts();
            })
            .catch(err => {
                toast.error(err.response?.data?.message || "Increment failed");
            })
            .finally(() => {
                isProcessing.current = false;
            });
    };

    // 3. Decrement (Unified Server-First)
    const handleDecrement = (cartId) => {
        if (isProcessing.current) return;
        isProcessing.current = true;

        axios.put("/admin/cart/decrement", { id: cartId })
            .then(() => {
                getCarts();
            })
            .catch(err => {
                toast.error(err.response?.data?.message || "Decrement failed");
            })
            .finally(() => {
                isProcessing.current = false;
            });
    };

    // 4. Remove (Simplified)
    const handleRemove = (cartId) => {
          axios.put("/admin/cart/delete", { id: cartId })
            .then(() => {
                getCarts();
                playSound(WarningSound);
            })
            .catch(err => {
                toast.error(err.response?.data?.message || "Delete failed");
            });
    };
    
    // 5. Update Quantity (Unified Server-First)
    const handleUpdateQuantity = (cartId, qty) => {
        if (isProcessing.current) return;
        isProcessing.current = true;

        axios.put("/admin/cart/update-quantity", { id: cartId, quantity: qty })
            .then(() => {
                getCarts();
            })
            .catch(err => {
                toast.error(err.response?.data?.message || "Update failed");
            })
            .finally(() => {
                isProcessing.current = false;
            });
    };

    // 6. Update By Price (Robust Server-First)
    const handleUpdateByPrice = (cartId, targetTotal) => {
        if (isProcessing.current) return;
        isProcessing.current = true;

        const parsedTotal = parseFloat(targetTotal);
        if (isNaN(parsedTotal) || parsedTotal < 0) {
            toast.error("Invalid amount");
            isProcessing.current = false;
            return;
        }

        axios.put("/admin/cart/update-by-price", { 
            id: cartId, 
            price: parsedTotal 
        })
        .then(() => {
            getCarts();
        })
        .catch(err => {
            toast.error(err.response?.data?.message || "Amount update failed");
        })
        .finally(() => {
            isProcessing.current = false;
        });
    };

    // 7. Update Rate (Unified Server-First)
    const handleUpdateRate = (cartId, newRate) => {
        if (isProcessing.current) return;
        isProcessing.current = true;

        axios.put("/admin/cart/update-rate", { id: cartId, price: newRate })
            .then(() => {
                getCarts();
            })
            .catch(err => {
                toast.error(err.response?.data?.message || "Rate update failed");
            })
            .finally(() => {
                isProcessing.current = false;
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
            
            // CRITICAL FIX: Clear local product cache so stock quantities refresh immediately
            setProductCache({}); 

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
            
            // Refresh Products (stock update) from server
            // NUKE: Force refresh by ignoring local cache after payment success
            getProducts(searchQuery, 1, false, true); 
            
            playSound(SuccessSound);
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
            window.location.href = `/admin/purchase/create?product_id=${product.id}`;
        }
        setContextMenu(null);
    };

    // Hotkeys Ref

    // Unified Hotkey Listener removed in favor of Unified Capture Listener above

    // Render
    return (
        <ErrorBoundary>
        <div className="pos-app-container">
            <Toaster position="top-right" containerStyle={{ zIndex: 99999 }} />
            {/* Scanner Active Indicator - Global Overlay */}
            {scannerActive && (
                <div className="scanner-active-indicator" style={{
                    position: 'fixed',
                    top: '10px',
                    left: '50%',
                    transform: 'translateX(-50%)',
                    background: 'linear-gradient(135deg, #34c759 0%, #30d158 100%)',
                    color: '#fff',
                    padding: '8px 20px',
                    borderRadius: '20px',
                    fontSize: '0.85rem',
                    fontWeight: '700',
                    zIndex: 100000,
                    boxShadow: '0 4px 20px rgba(52, 199, 89, 0.4)',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    animation: 'scannerPulse 0.3s ease-out'
                }}>
                    <i className="fas fa-barcode"></i>
                    <span>Scanner Active</span>
                    <div style={{
                        width: '8px',
                        height: '8px',
                        borderRadius: '50%',
                        background: '#fff',
                        animation: 'scannerBlink 0.5s infinite'
                    }}></div>
                </div>
            )}
            <style>{`
                @keyframes scannerPulse {
                    from { transform: translateX(-50%) scale(0.9); opacity: 0; }
                    to { transform: translateX(-50%) scale(1); opacity: 1; }
                }
                @keyframes scannerBlink {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.3; }
                }
            `}</style>
            {/* LEFT PANEL: PRODUCTS */}
            <div className="pos-left-panel d-flex flex-column border-right bg-white">
                {/* Header / Search */}
                <div className="p-3 border-bottom shadow-sm bg-light">
                    <div className="d-flex align-items-center justify-content-between">
                        <div className="d-flex align-items-center flex-grow-1 mr-3">
                            <div className="input-group apple-input-group shadow-none" style={{ 
                                background: scannerActive ? 'rgba(52, 199, 89, 0.1)' : '#f5f5f7', 
                                borderRadius: '12px', 
                                border: scannerActive ? '2px solid #34c759' : '1px solid rgba(0,0,0,0.05)', 
                                overflow: 'hidden',
                                transition: 'all 0.2s ease'
                            }}>
                                <div className="input-group-prepend">
                                    <span className="input-group-text bg-transparent border-0 pr-0" style={{ 
                                        fontSize: '0.8rem', 
                                        color: scannerActive ? '#34c759' : '#8e8e93', 
                                        fontWeight: 'bold' 
                                    }}>
                                        <i className={scannerActive ? "fas fa-barcode" : "fas fa-search"}></i>
                                    </span>
                                </div>
                                <input 
                                    id="product-search-input"
                                    type="text" 
                                    className="form-control border-0 bg-transparent font-weight-bold allow-scanner" 
                                    style={{ fontSize: '1.2rem', color: '#1d1d1f', boxShadow: 'none' }}
                                    placeholder={scannerActive ? "Scanning..." : "Scan Barcode or Search (F2)..."}
                                    value={searchQuery}
                                    ref={searchInputRef}
                                    autoComplete="off"
                                    autoFocus={true}
                                    onBlur={(e) => {
                                        // RELAXED FOCUS: Don't steal focus aggressively anymore
                                        // The new Global Listener handles "Frictionless Entry" from anywhere.
                                    }}
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
                                FRACTIONAL DISCOUNT {autoRound && calculateOrderValues().finalDiscount > 0 && <span>(-{calculateOrderValues().finalDiscount})</span>}
                            </small>
                        </div>
                     </div>

                    {/* Grand Total - Focal Point Refinement */}
                    <div className="d-flex justify-content-between align-items-center mb-3 p-3 bg-gradient-maroon rounded shadow-md" style={{ borderRadius: 'var(--radius-md) !important' }}>
                        <div className="line-height-1">
                            <small className="text-white-50 text-uppercase font-weight-bold" style={{ fontSize: '0.7em', letterSpacing: '1px' }}>Total Payable</small>
                            <h4 className="m-0 text-white font-weight-light">{window.posSettings?.currencySymbol || 'Rs.'}</h4>
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
                onClose={() => setShowReceiptModal(false)}
            />
        </div>
        </ErrorBoundary>
    );
}
