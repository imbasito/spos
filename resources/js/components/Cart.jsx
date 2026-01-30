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
                            <div className="responsive-table">
                                <table className="table apple-table mb-0">
                                    <thead style={{ background: 'var(--system-gray-6)' }}>
                                        <tr className="text-center">
                                            <th className="border-0 py-3" style={{ fontSize: '0.75rem', color: '#8e8e93', textTransform: 'uppercase' }}>Item Name</th>
                                            <th className="border-0 py-3" style={{ fontSize: '0.75rem', color: '#8e8e93', textTransform: 'uppercase' }}>Weight/Qty</th>
                                            <th className="border-0 py-3" style={{ fontSize: '0.75rem', color: '#8e8e93', textTransform: 'uppercase' }}>Disc</th>
                                            <th className="border-0 py-3"></th>
                                            <th className="border-0 py-3" style={{ fontSize: '0.75rem', color: '#8e8e93', textTransform: 'uppercase' }}>Rate</th>
                                            <th className="border-0 py-3" style={{ fontSize: '0.75rem', color: '#8e8e93', textTransform: 'uppercase' }}>Amount</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        {carts.map((item) => (
                                            <tr key={item.id} className="align-middle" style={{ transition: 'background 0.2s' }}>
                                                <td className="py-3 font-weight-bold" style={{ color: '#1d1d1f' }}>{item.product.name}</td>
                                                <td className="py-3">
                                                    <div className="d-flex align-items-center justify-content-center">
                                                        <button
                                                            className="btn btn-sm btn-light shadow-none"
                                                            style={{ borderRadius: '15px', width: '28px', height: '28px', padding: 0, border: '1px solid #eee', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                                                            onClick={() => onDecrement(item.id)}
                                                        >
                                                            <span style={{ fontSize: '18px', fontWeight: 'bold', lineHeight: '0' }}>-</span>
                                                        </button>
                                                        <input
                                                            key={`qty-${item.id}-${item.quantity}`}
                                                            type="number"
                                                            className="form-control form-control-sm text-center mx-2"
                                                            defaultValue={item.quantity}
                                                            step="0.001"
                                                            style={{ width: '70px', borderRadius: '8px', border: '1px solid #eee', fontWeight: '700' }}
                                                            onBlur={(e) => handleQtyChange(item.id, e.target.value)}
                                                            onKeyDown={(e) => e.key === 'Enter' && e.target.blur()}
                                                        />
                                                        <button
                                                            className="btn btn-sm btn-light shadow-none"
                                                            style={{ borderRadius: '15px', width: '28px', height: '28px', padding: 0, border: '1px solid #eee', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                                                            onClick={() => onIncrement(item.id)}
                                                        >
                                                            <span style={{ fontSize: '18px', fontWeight: 'bold', lineHeight: '0' }}>+</span>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td className="py-3 text-center">
                                                    {parseFloat(item.product.discount || 0) > 0 ? (
                                                        <span className="badge badge-success-light" style={{ background: 'rgba(52,199,89,0.1)', color: '#34c759', padding: '6px 10px', borderRadius: '12px', fontSize: '14px', fontWeight: '800' }}>
                                                            -{item.product.discount_type === 'percentage' ? `${parseFloat(item.product.discount)}%` : `${parseFloat(item.product.discount)}`}
                                                        </span>
                                                    ) : '-'}
                                                </td>
                                                <td className="py-3">
                                                    <button
                                                        className="btn btn-sm text-danger shadow-none"
                                                        onClick={() => onDelete(item.id)}
                                                        style={{ borderRadius: '20px', background: 'rgba(255,59,48,0.05)' }}
                                                    >
                                                        <i className="fas fa-trash-alt small"></i>
                                                    </button>
                                                </td>
                                                <td className="py-3 text-right">
                                                    <input
                                                        key={`rate-${item.id}-${item.product.discounted_price}`}
                                                        type="number"
                                                        className="form-control form-control-sm text-right no-spinner"
                                                        defaultValue={parseFloat(item.product.discounted_price).toFixed(2)}
                                                        style={{ 
                                                            width: '85px', display: 'inline-block', borderRadius: '8px', 
                                                            border: '1px solid #f0f0f5', fontWeight: '600', color: '#1d1d1f',
                                                            backgroundColor: '#fafafa'
                                                        }}
                                                        onBlur={(e) => handleRateChange(item.id, e.target.value)}
                                                        onKeyDown={(e) => e.key === 'Enter' && e.target.blur()}
                                                    />
                                                </td>
                                                <td className="py-3 text-right">
                                                    <input
                                                        key={`total-${item.id}-${item.row_total}`}
                                                        type="number"
                                                        className="form-control form-control-sm text-right no-spinner"
                                                        defaultValue={parseFloat(item.row_total).toFixed(2)}
                                                        style={{ 
                                                            width: '85px', display: 'inline-block', borderRadius: '8px', 
                                                            border: '1px solid #777', fontWeight: '800', color: 'var(--primary-color)',
                                                            backgroundColor: '#fff'
                                                        }}
                                                        onBlur={(e) => handlePriceChange(item.id, e.target.value)}
                                                        onKeyDown={(e) => e.key === 'Enter' && e.target.blur()}
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
