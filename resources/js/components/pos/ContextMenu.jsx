import React, { useEffect } from "react";

/**
 * Professional Context Menu Component
 * Apple-style right-click menu for product actions
 */
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

export default ContextMenu;
