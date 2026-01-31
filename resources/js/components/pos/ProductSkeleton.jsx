import React from "react";

/**
 * ProductSkeleton Component
 * Loading placeholder for product grid
 */
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

export default ProductSkeleton;
