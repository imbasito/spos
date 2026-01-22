import React, { useEffect, useState, useCallback, useMemo, useRef, memo } from "react";
import axios from "axios";
import Swal from "sweetalert2";
import Cart from "./Cart";
import toast, { Toaster } from "react-hot-toast";
import CustomerSelect from "./CutomerSelect";
import debounce from "lodash/debounce";
import throttle from "lodash/throttle";

import SuccessSound from "../sounds/beep-07a.mp3";
import WarningSound from "../sounds/beep-02.mp3";
import playSound from "../utils/playSound";

// Memoized ProductCard component - only re-renders when props change
const ProductCard = memo(({ product, onClick, baseUrl }) => (
    <div
        onClick={() => onClick(product.id)}
        className="col-6 col-md-4 col-lg-3 mb-3"
        style={{ cursor: "pointer" }}
    >
        <div className="text-center product-card">
            <img
                src={`${baseUrl}/storage/${product.image}`}
                alt={product.name}
                className="mr-2 img-thumb"
                loading="lazy"
                decoding="async"
                onError={(e) => {
                    e.target.onerror = null;
                    e.target.src = `${baseUrl}/assets/images/no-image.png`;
                }}
                width={100}
                height={80}
                style={{ objectFit: 'cover' }}
            />
            <div className="product-details">
                <p className="mb-0 text-bold product-name">
                    {product.name} ({product.quantity})
                </p>
                <p>Price: {product?.discounted_price}</p>
            </div>
        </div>
    </div>
));

export default function Pos() {
    const [products, setProducts] = useState([]);
    const [carts, setCarts] = useState([]);
    const [orderDiscount, setOrderDiscount] = useState(0);
    const [paid, setPaid] = useState(0);
    const [due, setDue] = useState(0);
    const [total, setTotal] = useState(0);
    const [updateTotal, setUpdateTotal] = useState(0);
    const [customerId, setCustomerId] = useState();
    const [cartUpdated, setCartUpdated] = useState(false);
    const [productUpdated, setProductUpdated] = useState(false);
    const [searchQuery, setSearchQuery] = useState("");
    const [searchBarcode, setSearchBarcode] = useState("");
    const [debouncedSearch, setDebouncedSearch] = useState("");
    const [debouncedBarcode, setDebouncedBarcode] = useState("");
    const [paymentMethod, setPaymentMethod] = useState("cash");
    const [transactionId, setTransactionId] = useState("");
    const { protocol, hostname, port } = window.location;
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(0);
    const [loading, setLoading] = useState(false);
    const [initialLoadDone, setInitialLoadDone] = useState(false);

    // Read auto fractional discount setting from backend
    const autoFractionalDiscount = window.posSettings?.autoFractionalDiscount || false;

    // Auto-apply fractional discount when total changes (if setting enabled)
    useEffect(() => {
        if (autoFractionalDiscount && total > 0) {
            const fractionalPart = total % 1;
            if (fractionalPart > 0) {
                setOrderDiscount(fractionalPart.toFixed(2));
            }
        }
    }, [total, autoFractionalDiscount]);

    // Memory cache for products - persists across re-renders
    const productCache = useRef(new Map());
    const lastFetchTime = useRef(0);
    const CACHE_DURATION = 60000; // 1 minute cache

    const fullDomainWithPort = useMemo(() =>
        `${protocol}//${hostname}${port ? `:${port}` : ""}`,
        [protocol, hostname, port]
    );
    const getProducts = useCallback(
        async (search = "", page = 1, barcode = "") => {
            const cacheKey = `${search}-${page}-${barcode}`;
            const now = Date.now();

            // Check cache first (only for non-search queries)
            if (!search && !barcode && productCache.current.has(cacheKey)) {
                const cached = productCache.current.get(cacheKey);
                if (now - cached.timestamp < CACHE_DURATION) {
                    setProducts((prev) => [...prev, ...cached.data]);
                    setTotalPages(cached.totalPages);
                    return;
                }
            }

            setLoading(true);
            try {
                const res = await axios.get('/admin/get/products', {
                    params: { search, page, barcode },
                });
                const productsData = res.data;

                // Cache the result
                productCache.current.set(cacheKey, {
                    data: productsData.data,
                    totalPages: productsData.meta.last_page,
                    timestamp: now
                });

                setProducts((prev) => [...prev, ...productsData.data]);
                if (productsData.data.length === 1 && barcode != "") {
                    addProductToCart(productsData.data[0].id);
                    getCarts();
                }
                setTotalPages(productsData.meta.last_page);
            } catch (error) {
                console.error("Error fetching products:", error);
            } finally {
                setLoading(false);
            }
        },
        []
    );
    // Debounce search input - waits 300ms after user stops typing
    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(searchQuery);
        }, 300);
        return () => clearTimeout(timer);
    }, [searchQuery]);

    // Debounce barcode input - waits 300ms after user stops typing
    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedBarcode(searchBarcode);
        }, 300);
        return () => clearTimeout(timer);
    }, [searchBarcode]);

    // Keyboard shortcuts for faster POS operation
    useEffect(() => {
        const handleKeyDown = (e) => {
            // Ctrl+Enter or Cmd+Enter = Quick Checkout
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                if (total > 0 && customerId) {
                    // Trigger checkout button click
                    document.querySelector('[data-checkout-btn]')?.click();
                }
            }
            // Ctrl+Delete or Ctrl+Backspace = Clear cart (with confirmation)
            // Changed from Escape to avoid conflict with fullscreen exit
            if ((e.ctrlKey || e.metaKey) && (e.key === 'Delete' || e.key === 'Backspace')) {
                e.preventDefault();
                if (total > 0) {
                    // Trigger clear cart button click
                    document.querySelector('[data-clear-cart-btn]')?.click();
                }
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [total, customerId]);

    // Refresh products after order completion
    const refreshProducts = useCallback(async () => {
        try {
            setLoading(true);
            const res = await axios.get('/admin/get/products');
            const productsData = res.data;
            setProducts(productsData.data);
            setTotalPages(productsData.meta.last_page);
        } catch (error) {
            console.error("Error fetching products:", error);
        } finally {
            setLoading(false);
        }
    }, []);

    // Initial product load - runs only once on mount
    useEffect(() => {
        if (!initialLoadDone) {
            refreshProducts();
            setInitialLoadDone(true);
        }
    }, [initialLoadDone, refreshProducts]);

    // Refresh products when order is completed
    useEffect(() => {
        if (productUpdated && initialLoadDone) {
            refreshProducts();
        }
    }, [productUpdated]);

    const getCarts = async () => {
        try {
            const res = await axios.get('/admin/cart');
            const data = res.data;
            setTotal(data?.total);
            setUpdateTotal(data?.total - orderDiscount);
            setCarts(data?.carts);
        } catch (error) {
            console.error("Error fetching carts:", error);
        }
    };

    useEffect(() => {
        getCarts();
    }, []);

    useEffect(() => {
        getCarts();
    }, [cartUpdated]);

    useEffect(() => {
        let paid1 = paid;
        let disc = orderDiscount;
        if (paid == "") {
            paid1 = 0;
        }
        if (orderDiscount == "") {
            disc = 0;
        }
        const updatedTotalAmount = parseFloat(total) - parseFloat(disc);
        const dueAmount = updatedTotalAmount - parseFloat(paid1);
        setUpdateTotal(updatedTotalAmount?.toFixed(2));
        setDue(dueAmount?.toFixed(2));
    }, [orderDiscount, paid, total]);
    // Handle debounced search - only fires after user stops typing
    useEffect(() => {
        if (debouncedSearch) {
            setProducts([]);
            setCurrentPage(1);
            getProducts(debouncedSearch, 1, "");
        } else if (initialLoadDone && !debouncedBarcode) {
            // Reset to show all products when search is cleared
            refreshProducts();
        }
    }, [debouncedSearch]);

    // Handle debounced barcode search
    useEffect(() => {
        if (debouncedBarcode) {
            setProducts([]);
            setCurrentPage(1);
            getProducts("", 1, debouncedBarcode);
        }
    }, [debouncedBarcode]);

    // Handle pagination (load more on scroll)
    useEffect(() => {
        if (currentPage > 1 && initialLoadDone) {
            getProducts(debouncedSearch, currentPage, debouncedBarcode);
        }
    }, [currentPage]);

    // Throttled scroll handler - fires at most once per 200ms
    useEffect(() => {
        const handleScroll = throttle(() => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight;
            const clientHeight = document.documentElement.clientHeight;

            // Load more when user is 200px from bottom
            if (scrollTop + clientHeight >= scrollHeight - 200) {
                if (currentPage < totalPages && !loading) {
                    setCurrentPage((prev) => prev + 1);
                }
            }
        }, 200);

        window.addEventListener("scroll", handleScroll, { passive: true });
        return () => {
            handleScroll.cancel();
            window.removeEventListener("scroll", handleScroll);
        };
    }, [currentPage, totalPages, loading]);

    function addProductToCart(id) {
        axios
            .post("/admin/cart", { id })
            .then((res) => {
                setCartUpdated(!cartUpdated);
                playSound(SuccessSound);
                toast.success(res?.data?.message);
            })
            .catch((err) => {
                playSound(WarningSound);
                toast.error(err.response.data.message);
            });
    }
    function cartEmpty() {
        if (total <= 0) {
            return;
        }
        Swal.fire({
            title: "Are you sure you want to delete Cart?",
            showDenyButton: true,
            confirmButtonText: "Yes",
            denyButtonText: "No",
            customClass: {
                actions: "my-actions",
                cancelButton: "order-1 right-gap",
                confirmButton: "order-2",
                denyButton: "order-3",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .put("/admin/cart/empty")
                    .then((res) => {
                        setCartUpdated(!cartUpdated);
                        // Reset payment fields when cart is cleared
                        setPaid(0);
                        setOrderDiscount(0);
                        playSound(SuccessSound);
                        toast.success(res?.data?.message);
                    })
                    .catch((err) => {
                        playSound(WarningSound);
                        toast.error(err.response.data.message);
                    });
            } else if (result.isDenied) {
                return;
            }
        });
    }
    function orderCreate() {
        if (total <= 0) {
            return;
        }
        if (!customerId) {
            toast.error("Please select customer");
            return;
        }
        Swal.fire({
            title: `Are you sure you want to complete this order? <br> Paid: ${paid} <br> Due: ${due}`,
            showDenyButton: true,
            confirmButtonText: "Yes",
            denyButtonText: "No",
            customClass: {
                actions: "my-actions",
                cancelButton: "order-1 right-gap",
                confirmButton: "order-2",
                denyButton: "order-3",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .put("/admin/order/create", {
                        customer_id: customerId,
                        order_discount: parseFloat(orderDiscount) || 0,
                        paid: parseFloat(paid) || 0,
                        payment_method: paymentMethod,
                        transaction_id: transactionId,
                    })
                    .then((res) => {
                        setCartUpdated(!cartUpdated);
                        setProductUpdated(!productUpdated);
                        toast.success(res?.data?.message);
                        // window.location.href = `orders/invoice/${res?.data?.order?.id}`;
                        const width = 450;
                        const height = 600;
                        const left = (window.screen.width / 2) - (width / 2);
                        const top = (window.screen.height / 2) - (height / 2);
                        const url = window.location.origin + `/admin/orders/pos-invoice/${res?.data?.order?.id}`;
                        window.open(url, 'Receipt', `width=${width},height=${height},top=${top},left=${left},scrollbars=yes`);
                    })
                    .catch((err) => {
                        toast.error(err.response.data.message);
                    });
            } else if (result.isDenied) {
                return;
            }
        });
    }

    return (
        <>
            <div className="card">
                {/* <div class="mt-n5 mb-3 d-flex justify-content-end">
                    <a
                        href="/admin"
                        className="btn bg-gradient-primary mr-2"
                    >
                        Dashboard
                    </a>
                    <a
                        href="/admin/ordersma"
                        className="btn bg-gradient-primary"
                    >
                        Orders
                    </a>
                </div> */}

                <div className="card-body p-2 p-md-4 pt-0">
                    <div className="row">
                        <div className="col-md-6 col-lg-6 mb-2">
                            <div className="row mb-2">
                                <div className="col-12">
                                    <CustomerSelect
                                        setCustomerId={setCustomerId}
                                    />
                                </div>
                                {/* <div className="col-6">
                                <form className="form">
                                    <input
                                        type="text"
                                        className="form-control"
                                        placeholder="Enter barcode"
                                        value={searchQuery}
                                        onChange={(e) =>
                                            setSearchQuery(e.target.value)
                                        }
                                    />
                                </form>
                            </div> */}
                            </div>
                            <Cart
                                carts={carts}
                                setCartUpdated={setCartUpdated}
                                cartUpdated={cartUpdated}
                            />
                            <div className="card">
                                <div className="card-body">
                                    <div className="row text-bold mb-1">
                                        <div className="col">Sub Total:</div>
                                        <div className="col text-right mr-2">
                                            {total}
                                        </div>
                                    </div>
                                    <div className="row text-bold mb-1">
                                        <div className="col">Discount:</div>
                                        <div className="col text-right mr-2">
                                            <input
                                                type="number"
                                                className="form-control form-control-sm"
                                                placeholder="Enter discount"
                                                min={0}
                                                disabled={total <= 0}
                                                value={orderDiscount}
                                                onChange={(e) => {
                                                    const value =
                                                        e.target.value;
                                                    if (
                                                        parseFloat(value) >
                                                        total ||
                                                        parseFloat(value) < 0
                                                    ) {
                                                        return;
                                                    }
                                                    setOrderDiscount(value);
                                                }}
                                            />
                                        </div>
                                    </div>
                                    <div className="row text-bold mb-1">
                                        <div className="col">
                                            Apply Fractional Discount:
                                        </div>
                                        <div className="col text-right mr-2">
                                            <input
                                                type="checkbox"
                                                className="form-control-sm"
                                                disabled={total <= 0}
                                                checked={autoFractionalDiscount && total > 0 && (total % 1) > 0}
                                                onChange={(e) => {
                                                    if (e.target.checked) {
                                                        const fractionalPart =
                                                            total % 1;
                                                        setOrderDiscount(
                                                            fractionalPart?.toFixed(
                                                                2
                                                            )
                                                        );
                                                    } else {
                                                        setOrderDiscount(0);
                                                    }
                                                }}
                                            />
                                        </div>
                                    </div>
                                    <div className="row text-bold mb-1">
                                        <div className="col">Total:</div>
                                        <div className="col text-right mr-2">
                                            {updateTotal}
                                        </div>
                                    </div>
                                    <div className="row text-bold mb-1">
                                        <div className="col">Paid:</div>
                                        <div className="col text-right mr-2">
                                            <input
                                                type="number"
                                                className="form-control form-control-sm"
                                                placeholder="Customer pays"
                                                min={0}
                                                disabled={total <= 0}
                                                value={paid}
                                                onFocus={(e) => {
                                                    // Clear zero when focused for professional UX
                                                    if (parseFloat(e.target.value) === 0) {
                                                        setPaid('');
                                                    }
                                                }}
                                                onBlur={(e) => {
                                                    // Restore to 0 if left empty
                                                    if (e.target.value === '' || e.target.value === null) {
                                                        setPaid(0);
                                                    }
                                                }}
                                                onChange={(e) => {
                                                    const value = e.target.value;
                                                    if (parseFloat(value) < 0) {
                                                        return;
                                                    }
                                                    setPaid(value);
                                                }}
                                            />
                                        </div>
                                    </div>
                                    {/* Show Change when customer pays more AND cart has items */}
                                    {parseFloat(updateTotal) > 0 && parseFloat(paid) > parseFloat(updateTotal) && (
                                        <div className="row text-bold mb-1" style={{ color: '#28a745' }}>
                                            <div className="col">Change:</div>
                                            <div className="col text-right mr-2">
                                                Rs. {(parseFloat(paid) - parseFloat(updateTotal)).toFixed(2)}
                                            </div>
                                        </div>
                                    )}
                                    {/* Show Due when customer pays less OR cart is empty */}
                                    {(parseFloat(updateTotal) <= 0 || parseFloat(paid) <= parseFloat(updateTotal)) && (
                                        <div className="row text-bold">
                                            <div className="col">Due:</div>
                                            <div className="col text-right mr-2">
                                                {parseFloat(updateTotal) > 0 ? due : '0.00'}
                                            </div>
                                        </div>
                                    )}

                                    {/* Payment Method Selection - Moved Below Due */}
                                    <div className="row text-bold mb-1 mt-3 pt-2" style={{ borderTop: '1px dashed #eee' }}>
                                        <div className="col-12 mb-1">Payment Method:</div>
                                        <div className="col-12">
                                            <div className="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                                <label className={`btn btn-maroon btn-sm ${paymentMethod === 'cash' ? 'active' : ''}`} onClick={() => setPaymentMethod('cash')}>
                                                    <input type="radio" name="payment_method" autoComplete="off" checked={paymentMethod === 'cash'} readOnly /> 
                                                    <i className="fas fa-money-bill-wave"></i> Cash
                                                </label>
                                                <label className={`btn btn-maroon btn-sm ${paymentMethod === 'card' ? 'active' : ''}`} onClick={() => setPaymentMethod('card')}>
                                                    <input type="radio" name="payment_method" autoComplete="off" checked={paymentMethod === 'card'} readOnly /> 
                                                    <i className="fas fa-credit-card"></i> Card
                                                </label>
                                                <label className={`btn btn-maroon btn-sm ${paymentMethod === 'online' ? 'active' : ''}`} onClick={() => setPaymentMethod('online')}>
                                                    <input type="radio" name="payment_method" autoComplete="off" checked={paymentMethod === 'online'} readOnly /> 
                                                    <i className="fas fa-mobile-alt"></i> Online (Easypaisa etc.)
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Transaction ID Input (Only for Card/Online) */}
                                    {paymentMethod !== 'cash' && (
                                        <div className="row text-bold mb-1 mt-2">
                                            <div className="col">Trx ID:</div>
                                            <div className="col text-right mr-2">
                                                <input
                                                    type="text"
                                                    className="form-control form-control-sm"
                                                    placeholder="Transaction ID (Optional)"
                                                    value={transactionId}
                                                    onChange={(e) => setTransactionId(e.target.value)}
                                                />
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col">
                                    <button
                                        onClick={() => cartEmpty()}
                                        type="button"
                                        data-clear-cart-btn
                                        className="btn bg-gradient-danger btn-block text-white text-bold"
                                    >
                                        Clear Cart
                                    </button>
                                </div>
                                <div className="col">
                                    <button
                                        onClick={() => {
                                            orderCreate();
                                        }}
                                        type="button"
                                        data-checkout-btn
                                        className="btn bg-gradient-maroon btn-block text-white text-bold"
                                    >
                                        Checkout
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div className="col-md-6 col-lg-6">
                            <div className="row">
                                <div className="input-group mb-2 col-md-6">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="fas fa-barcode"></i>
                                        </span>
                                    </div>
                                    <input
                                        type="text"
                                        className="form-control"
                                        placeholder="Enter Product Barcode"
                                        value={searchBarcode}
                                        autoFocus
                                        onChange={(e) =>
                                            setSearchBarcode(e.target.value)
                                        }
                                    />
                                </div>
                                <div className="mb-2 col-md-6">
                                    <input
                                        type="text"
                                        className="form-control"
                                        placeholder="Enter Product Name"
                                        value={searchQuery}
                                        onChange={(e) =>
                                            setSearchQuery(e.target.value)
                                        }
                                    />
                                </div>
                            </div>
                            <div className="row products-card-container">
                                {products.length > 0 &&
                                    products.map((product) => (
                                        <ProductCard
                                            key={product.id}
                                            product={product}
                                            onClick={addProductToCart}
                                            baseUrl={fullDomainWithPort}
                                        />
                                    ))}
                            </div>
                            {loading && (
                                <div className="loading-more">
                                    <div className="loading-spinner"></div>
                                    <span>Loading...</span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
            <Toaster position="top-right" reverseOrder={false} />
        </>
    );
}