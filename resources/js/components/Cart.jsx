import axios from "axios";
import React, { useState, useEffect } from "react";
import toast, { Toaster } from "react-hot-toast";
import Swal from "sweetalert2";
import SuccessSound from "../sounds/beep-07a.mp3";
import WarningSound from "../sounds/beep-02.mp3";
import playSound from "../utils/playSound";

export default function Cart({ carts, setCartUpdated, cartUpdated, onIncrement, onDecrement, onDelete, onUpdateQty, onUpdatePrice }) {
    
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
                                <table className="table table-striped">
                                    <thead>
                                        <tr className="text-center">
                                            <th>Name</th>
                                            <th>Qty (kg/pcs)</th>
                                            <th></th>
                                            <th>Price/Unit ({window.posSettings?.currencySymbol || 'Rs.'})</th>
                                            <th>Total ({window.posSettings?.currencySymbol || 'Rs.'})</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        {carts.map((item) => (
                                            <tr key={item.id}>
                                                <td>{item.product.name}</td>
                                                <td className="d-flex align-items-center">
                                                    <button
                                                        className="btn btn-warning btn-sm"
                                                        onClick={() =>
                                                            onDecrement(item.id)
                                                        }
                                                    >
                                                        <i className="fas fa-minus"></i>
                                                    </button>
                                                    <input
                                                        key={`qty-${item.id}-${item.quantity}`}
                                                        type="number"
                                                        className="form-control form-control-sm qty ml-1 mr-1"
                                                        defaultValue={item.quantity}
                                                        step="0.001"
                                                        min="0.001"
                                                        style={{ width: '80px' }}
                                                        onBlur={(e) => {
                                                            if (e.target.value !== String(item.quantity)) {
                                                                handleQtyChange(item.id, e.target.value);
                                                            }
                                                        }}
                                                        onKeyDown={(e) => {
                                                            if (e.key === 'Enter') {
                                                                e.target.blur();
                                                            }
                                                        }}
                                                    />
                                                    <button
                                                        className="btn btn-success btn-sm"
                                                        onClick={() =>
                                                            onIncrement(item.id)
                                                        }
                                                    >
                                                        <i className="fas fa-plus "></i>
                                                    </button>
                                                </td>
                                                <td>
                                                    <button
                                                        className="btn btn-danger btn-sm mr-3"
                                                        onClick={() => onDelete(item.id)}
                                                        title="Remove Item"
                                                    >
                                                        <i className="fas fa-trash "></i>
                                                    </button>
                                                </td>
                                                <td className="text-right">
                                                    {parseFloat(item.product.discounted_price).toFixed(2)}
                                                    {item.product.price > item.product.discounted_price && (
                                                        <>
                                                            <br />
                                                            <del>{parseFloat(item.product.price).toFixed(2)}</del>
                                                        </>
                                                    )}
                                                </td>
                                                <td className="text-right">
                                                    <input
                                                        key={`total-${item.id}-${item.row_total}`}
                                                        type="number"
                                                        className="form-control form-control-sm text-right no-spinner"
                                                        defaultValue={parseFloat(item.row_total).toFixed(2)}
                                                        step="any"
                                                        min="0"
                                                        style={{ width: '90px', display: 'inline-block' }}
                                                        title="Enter Rs. amount"
                                                        onBlur={(e) => {
                                                            if (e.target.value !== String(item.row_total)) {
                                                                handlePriceChange(item.id, e.target.value);
                                                            }
                                                        }}
                                                        onKeyDown={(e) => {
                                                            if (e.key === 'Enter') {
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
