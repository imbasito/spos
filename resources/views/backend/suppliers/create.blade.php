@extends('backend.master')

@section('title', 'Add New Supplier')

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-lg-8 col-md-10 mx-auto">
    <div class="card shadow-sm border-0 border-radius-15 overflow-hidden">

      <div class="card-header bg-gradient-maroon py-3 d-flex align-items-center">
        <h5 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-plus-circle mr-2"></i> Add New Supplier
        </h5>
        <a href="{{ route('backend.admin.suppliers.index') }}" class="btn btn-light btn-sm ml-auto font-weight-bold text-maroon">
          <i class="fas fa-arrow-left mr-1"></i> Back to Suppliers
        </a>
      </div>

      <div class="card-body p-4">
        @if ($errors->any())
          <div class="alert alert-danger border-0 shadow-sm mb-4">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ $errors->first() }}
          </div>
        @endif

        <form action="{{ route('backend.admin.suppliers.store') }}" method="post">
          @csrf
          <div class="row">
            <div class="mb-3 col-md-6">
              <label class="form-label font-weight-bold text-dark">
                Name <span class="text-danger">*</span>
              </label>
              <input type="text" class="form-control @error('name') is-invalid @enderror"
                placeholder="Enter supplier name" name="name" value="{{ old('name') }}" required>
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3 col-md-6">
              <label class="form-label font-weight-bold text-dark">
                Phone <span class="text-danger">*</span>
              </label>
              <input type="text" class="form-control @error('phone') is-invalid @enderror"
                placeholder="Enter phone number" name="phone" value="{{ old('phone') }}" required>
              @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3 col-md-12">
              <label class="form-label font-weight-bold text-dark">Address</label>
              <input type="text" class="form-control @error('address') is-invalid @enderror"
                placeholder="Enter address (optional)" name="address" value="{{ old('address') }}">
              @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="d-flex justify-content-end mt-2">
            <a href="{{ route('backend.admin.suppliers.index') }}" class="btn btn-outline-secondary mr-2 px-4">Cancel</a>
            <button type="submit" class="btn bg-gradient-maroon text-white px-5 font-weight-bold">
              <i class="fas fa-save mr-1"></i> Save Supplier
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<style>
  .bg-gradient-maroon { background: linear-gradient(45deg, #800000, #A01010) !important; }
  .text-maroon { color: #800000 !important; }
</style>
@endsection