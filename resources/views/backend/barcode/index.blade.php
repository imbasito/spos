@extends('backend.master')
@section('title', 'Barcode Generator')
@section('content')
    <script>
        window.posSettings = {
            receiptPrinter: "{{ readConfig('receipt_printer') }}",
            tagPrinter: "{{ readConfig('tag_printer') }}"
        };
    </script>
    <div id="barcode-root"></div>
@endsection

@push('js')
    @viteReactRefresh
    @vite('resources/js/app.jsx')
@endpush
