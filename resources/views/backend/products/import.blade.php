@extends('backend.master')

@section('title', 'Import Product')

@section('content')
<div class="row animate__animated animate__fadeInUp">
  <div class="col-md-8 mx-auto">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">
      <div class="card-header bg-white py-3 border-bottom-0">
        <h3 class="card-title font-weight-bold text-dark mb-0">
          <i class="fas fa-file-import mr-2 text-primary"></i> Import Products (Bulk)
        </h3>
      </div>
      <div class="card-body p-4">
        <p class="text-muted mb-4 small">Upload a CSV or Excel file to batch-add products to your inventory. Use the demo file if you're unsure about the format.</p>
        
        <form action="{{ route('backend.admin.products.import') }}" method="post" class="accountForm" enctype="multipart/form-data">
          @csrf
          <div class="row">
            <div class="col-12">
              <div class="form-group mb-4">
                <label class="font-weight-bold small text-uppercase mb-2">Select Spreadsheet File</label>
                <div class="input-group shadow-sm border-radius-10 overflow-hidden">
                  <div class="custom-file border-0">
                    <input type="file" class="custom-file-input" name="file" id="exampleInputFile" required>
                    <label class="custom-file-label border-0 bg-light" for="exampleInputFile">Choose .csv, .xls, .xlsx</label>
                  </div>
                  <div class="input-group-append">
                    <a class="btn btn-outline-primary border-0 bg-white" href="{{ route('backend.admin.products.import',['download-demo' => true]) }}" title="Download Template">
                      <i class="fas fa-download mr-1"></i> Demo Template
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col-12">
              <button type="submit" class="btn btn-block bg-gradient-primary py-3 font-weight-bold shadow-sm hover-lift" style="font-size: 1rem; border-radius: 12px;">
                <i class="fas fa-magic mr-2"></i> START IMPORT PROCESS
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('style')
<style>
  .select2-container--default .select2-selection--single {
    height: calc(1.5em + 0.75rem + 2px) !important;
  }
</style>

@endpush
@push('script')
<script src="{{ asset('js/image-field.js') }}"></script>
@endpush