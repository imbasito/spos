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
                <div className="card">
                    <div className="card-body">
                        {carts.length === 0 ? (
                            <div className="empty-state-container py-5">
                                <i className="fas fa-shopping-cart fa-4x mb-3 text-muted" style={{ opacity: 0.2 }}></i>
                                <h5 className="text-muted font-weight-bold">Cart is empty</h5>
                                <p className="text-muted small">Select products to start an order</p>
                            </div>
                        ) : (
                            <div className="responsive-table">
                                <table className="table table-striped">
                                    <thead>
                                        <tr className="text-center">
                                            <th>Name</th>
                                            <th>Qty (kg/pcs)</th>
                                            <th></th>
                                            <th>Price/Unit</th>
                                            <th>Total</th>
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
