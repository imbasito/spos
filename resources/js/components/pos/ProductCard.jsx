import React, { memo } from "react";

/**
 * ProductCard Component
 * Apple-style product card for POS grid display
 */
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

ProductCard.displayName = 'ProductCard';

export default ProductCard;
