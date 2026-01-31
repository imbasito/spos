import axios from "axios";
import React, { useState, useEffect } from "react";
import toast, { Toaster } from "react-hot-toast";
import Swal from "sweetalert2";
import SuccessSound from "../sounds/beep-07a.mp3";
import WarningSound from "../sounds/beep-02.mp3";
import playSound from "../utils/playSound";

export default function Cart({ carts, setCartUpdated, cartUpdated, onIncrement, onDecrement, onDelete, onUpdateQty, onUpdatePrice, onUpdateRate }) {
    
    // Direct quantity update wrapper
    function handleQtyChange(id, newQuantity) {
        const qty = parseFloat(newQuantity);
        if (isNaN(qty) || qty <= 0) {
            toast.error("Enter valid quantity");
            return;
        }
        onUpdateQty(id, qty);
    }

    // Update by price wrapper for optimistic sync
    function handlePriceChange(id, targetPrice) {
        const price = parseFloat(targetPrice);
        if (isNaN(price) || price <= 0) {
            toast.error("Enter valid amount");
            return;
        }
        onUpdatePrice(id, price);
    }

    function handleRateChange(id, newRate) {
        const rate = parseFloat(newRate);
        if (isNaN(rate) || rate < 0) {
            toast.error("Enter valid price");
            return;
        }
        onUpdateRate(id, rate);
    }

    return (
        <>
            <div className="user-cart">
                <div className="card shadow-soft apple-card-refinement" style={{ borderRadius: 'var(--radius-md)', border: 'var(--apple-border)' }}>
                    <div className="card-body">
                        {carts.length === 0 ? (
                            <div className="empty-state-container py-5 text-center animate__animated animate__fadeIn">
                                {/* Bismillah Calligraphy SVG - Premium Apple Decoration */}
                                <div className="bismillah-calligraphy mb-3 position-relative" style={{ height: '110px', opacity: 1, transform: 'scale(0.85)' }}>
                                    {/* Subtle Crystalline Glow (Apple standard) */}
                                    <div className="position-absolute" style={{ top: '50%', left: '50%', transform: 'translate(-50%, -50%)', width: '250px', height: '80px', background: 'radial-gradient(circle, rgba(128,0,0,0.03) 0%, transparent 70%)', zIndex: -1 }}></div>
                                    
                                    <svg viewBox="0 0 600 120" fill="var(--apple-accent)" xmlns="http://www.w3.org/2000/svg" style={{ height: '100%', width: 'auto' }}>
                                        {/* Precision Geometric Decor (Apple style) */}
                                        <g opacity="0.15">
                                            <circle cx="50" cy="60" r="1.5" />
                                            <circle cx="550" cy="60" r="1.5" />
                                            <path d="M70,60 L120,60 M480,60 L530,60" stroke="var(--apple-accent)" stroke-width="0.5" />
                                        </g>
                                        
                                        {/* Main Calligraphy */}
                                        <text x="300" y="75" font-family="Traditional Arabic, Times New Roman, serif" font-size="68" text-anchor="middle" fill="var(--apple-accent)" font-weight="500" style={{ letterSpacing: '0.02em' }}>
                                            بسم الله الرحمن الرحيم
                                        </text>
                                        
                                        <path d="M220,100 Q300,92 380,100" fill="none" stroke="var(--apple-accent)" stroke-width="0.8" stroke-linecap="round" opacity="0.1" />
                                    </svg>
                                </div>




                                <h5 className="text-apple-sub font-weight-bold" style={{ letterSpacing: '0.05em' }}>Your Cart is Ready</h5>
                                <p className="text-apple-tiny">Scan or select products to begin a blessed transaction</p>
                            </div>
                        ) : (
                            <div className="apple-table-container" style={{ overflowX: 'hidden', width: '100%' }}>
                                <table className="table apple-table mb-0" style={{ tableLayout: 'fixed', width: '100%' }}>
                                    <thead style={{ background: 'var(--system-gray-6)' }}>
                                        <tr className="text-center">
                                            <th className="border-0 py-2 px-2" style={{ fontSize: '0.7rem', color: '#8e8e93', textTransform: 'uppercase', width: '25%', fontWeight: '600' }}>Item</th>
                                            <th className="border-0 py-2 px-1" style={{ fontSize: '0.7rem', color: '#8e8e93', textTransform: 'uppercase', width: '22%', fontWeight: '600' }}>Qty</th>
                                            <th className="border-0 py-2 px-1" style={{ fontSize: '0.7rem', color: '#8e8e93', textTransform: 'uppercase', width: '10%', fontWeight: '600' }}>Disc</th>
                                            <th className="border-0 py-2 px-0" style={{ width: '6%' }}></th>
                                            <th className="border-0 py-2 px-1" style={{ fontSize: '0.7rem', color: '#8e8e93', textTransform: 'uppercase', width: '18%', fontWeight: '600' }}>Rate</th>
                                            <th className="border-0 py-2 px-1" style={{ fontSize: '0.7rem', color: '#8e8e93', textTransform: 'uppercase', width: '19%', fontWeight: '600' }}>Amount</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        {carts.map((item) => (
                                            <tr key={item.id} className="align-middle" style={{ transition: 'background 0.2s' }}>
                                                <td className="py-2 px-2" style={{ fontSize: '0.85rem', fontWeight: '600', color: '#1d1d1f', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={item.product.name}>
                                                    {item.product.name}
                                                </td>
                                                <td className="py-2 px-1">
                                                    <div className="d-flex align-items-center justify-content-center" style={{ gap: '2px' }}>
                                                        <button
                                                            className="btn btn-sm btn-light shadow-none"
                                                            style={{ borderRadius: '12px', width: '24px', height: '24px', padding: 0, border: '1px solid #e0e0e0', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}
                                                            onClick={() => onDecrement(item.id)}
                                                        >
                                                            <span style={{ fontSize: '16px', fontWeight: 'bold', lineHeight: '0', color: '#666' }}>−</span>
                                                        </button>
                                                        <input
                                                            key={`qty-${item.id}-${item.quantity}`}
                                                            type="number"
                                                            className="form-control form-control-sm text-center"
                                                            defaultValue={parseFloat(item.quantity).toFixed(3)}
                                                            step="0.001"
                                                            style={{ 
                                                                width: '52px', 
                                                                borderRadius: '6px', 
                                                                border: '1.5px solid #d1d1d6', 
                                                                fontWeight: '700', 
                                                                fontSize: '0.85rem',
                                                                color: '#1d1d1f',
                                                                padding: '4px 2px',
                                                                height: '32px',
                                                                backgroundColor: '#fff'
                                                            }}
                                                            onBlur={(e) => {
                                                                const val = parseFloat(e.target.value);
                                                                if (!isNaN(val) && val > 0) {
                                                                    handleQtyChange(item.id, val);
                                                                } else {
                                                                    e.target.value = parseFloat(item.quantity).toFixed(3);
                                                                }
                                                            }}
                                                            onKeyDown={(e) => {
                                                                if (e.key === 'Enter') {
                                                                    e.preventDefault();
                                                                    e.target.blur();
                                                                }
                                                                if (e.key === 'Escape') {
                                                                    e.preventDefault();
                                                                    e.target.value = parseFloat(item.quantity).toFixed(3);
                                                                    e.target.blur();
                                                                }
                                                            }}
                                                        />
                                                        <button
                                                            className="btn btn-sm btn-light shadow-none"
                                                            style={{ borderRadius: '12px', width: '24px', height: '24px', padding: 0, border: '1px solid #e0e0e0', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}
                                                            onClick={() => onIncrement(item.id)}
                                                        >
                                                            <span style={{ fontSize: '16px', fontWeight: 'bold', lineHeight: '0', color: '#666' }}>+</span>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td className="py-2 px-1 text-center">
                                                    {parseFloat(item.product.discount || 0) > 0 ? (
                                                        <span className="badge badge-success-light" style={{ background: 'rgba(52,199,89,0.12)', color: '#34c759', padding: '4px 6px', borderRadius: '8px', fontSize: '0.7rem', fontWeight: '700' }}>
                                                            -{item.product.discount_type === 'percentage' ? `${parseFloat(item.product.discount)}%` : `${parseFloat(item.product.discount).toFixed(0)}`}
                                                        </span>
                                                    ) : <span style={{ color: '#c7c7cc', fontSize: '0.8rem' }}>—</span>}
                                                </td>
                                                <td className="py-2 px-0 text-center">
                                                    <button
                                                        className="btn btn-sm text-danger shadow-none p-1"
                                                        onClick={() => onDelete(item.id)}
                                                        style={{ borderRadius: '50%', background: 'rgba(255,59,48,0.06)', width: '28px', height: '28px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                                                    >
                                                        <i className="fas fa-times" style={{ fontSize: '0.75rem' }}></i>
                                                    </button>
                                                </td>
                                                <td className="py-2 px-1 text-right">
                                                    <input
                                                        key={`rate-${item.id}-${item.product.discounted_price}`}
                                                        type="number"
                                                        className="form-control form-control-sm text-right"
                                                        defaultValue={parseFloat(item.product.discounted_price).toFixed(2)}
                                                        step="0.01"
                                                        style={{ 
                                                            width: '100%',
                                                            borderRadius: '6px', 
                                                            border: '1.5px solid #d1d1d6', 
                                                            fontWeight: '700', 
                                                            fontSize: '0.85rem',
                                                            color: '#1d1d1f',
                                                            padding: '4px 6px',
                                                            height: '32px',
                                                            backgroundColor: '#fafafa'
                                                        }}
                                                        onBlur={(e) => {
                                                            const val = parseFloat(e.target.value);
                                                            if (!isNaN(val) && val >= 0) {
                                                                handleRateChange(item.id, val);
                                                            } else {
                                                                e.target.value = parseFloat(item.product.discounted_price).toFixed(2);
                                                            }
                                                        }}
                                                        onKeyDown={(e) => {
                                                            if (e.key === 'Enter') {
                                                                e.preventDefault();
                                                                e.target.blur();
                                                            }
                                                            if (e.key === 'Escape') {
                                                                e.preventDefault();
                                                                e.target.value = parseFloat(item.product.discounted_price).toFixed(2);
                                                                e.target.blur();
                                                            }
                                                        }}
                                                    />
                                                </td>
                                                <td className="py-2 px-1 text-right">
                                                    <input
                                                        key={`total-${item.id}-${item.row_total}`}
                                                        type="number"
                                                        className="form-control form-control-sm text-right"
                                                        defaultValue={parseFloat(item.row_total).toFixed(2)}
                                                        step="0.01"
                                                        style={{ 
                                                            width: '100%',
                                                            borderRadius: '6px', 
                                                            border: '2px solid var(--primary-color)', 
                                                            fontWeight: '800', 
                                                            fontSize: '0.9rem',
                                                            color: 'var(--primary-color)',
                                                            padding: '4px 6px',
                                                            height: '32px',
                                                            backgroundColor: '#fff'
                                                        }}
                                                        onBlur={(e) => {
                                                            const val = parseFloat(e.target.value);
                                                            if (!isNaN(val) && val >= 0) {
                                                                handlePriceChange(item.id, val);
                                                            } else {
                                                                e.target.value = parseFloat(item.row_total).toFixed(2);
                                                            }
                                                        }}
                                                        onKeyDown={(e) => {
                                                            if (e.key === 'Enter') {
                                                                e.preventDefault();
                                                                e.target.blur();
                                                            }
                                                            if (e.key === 'Escape') {
                                                                e.preventDefault();
                                                                e.target.value = parseFloat(item.row_total).toFixed(2);
                                                                e.target.blur();
                                                            }
                                                        }}
                                                    />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        
                    </div>
                </div>
            </div>
            <Toaster position="top-right" reverseOrder={false} />
        </>
    );
}
