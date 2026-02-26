@extends('backend.master')
@section('title', 'Pos')
@section('content')
<script>
    window.posSettings = {
        autoFractionalDiscount: {{ (readConfig('auto_fractional_discount') ?? 0) == 1 ? 'true' : 'false' }},
        logoPath: "{{ assetImage(readConfig('site_logo')) }}",
        receiptPrinter: "{{ readConfig('receipt_printer') }}",
        tagPrinter: "{{ readConfig('tag_printer') }}",
        currencySymbol: "{{ currency()->symbol ?? 'Rs.' }}"
    };
</script>

<div id="cart"></div>
@push('style')
<link rel="stylesheet" href="{{ asset('css/pos.css') }}">
<style>
    .products-card-container {
        max-height: 400px;
        overflow-y: auto;
        overflow-x: hidden;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 10px;
        background: #fafafa;
    }

    /* Professional Product Card Styling */
    .product-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 10px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    
    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-color: var(--primary-color, #800000);
    }
    
    .product-card:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .product-name {
        margin-bottom: 0;
        font-weight: bold;
        overflow: hidden;
        white-space: normal;
        text-overflow: ellipsis;
        font-size: 0.85rem;
    }

    .product-details p {
        margin: 0;
        max-height: 3.6em;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.8rem;
        color: #666;
    }

    /* Professional Loading Spinner */
    .loading-more {
        text-align: center;
        padding: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        color: #666;
    }
    
    .loading-spinner {
        width: 20px;
        height: 20px;
        border: 2px solid #e0e0e0;
        border-top: 2px solid var(--primary-color, #800000);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .responsive-table {
        height: 100%;
        overflow-y: scroll;
    }

    .qty {
        -moz-appearance: textfield;
        -webkit-appearance: none;
        appearance: none;
    }

    .qty::-webkit-inner-spin-button,
    .qty::-webkit-outer-spin-button {
        display: none;
    }
    
    /* Image thumbnail styling */
    .img-thumb {
        border-radius: 6px;
        border: 1px solid #eee;
    }
</style>
@endpush
@endsection