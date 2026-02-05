@extends('backend.master')

@section('title', 'Create Customer')

@section('content')
<div class="card">
  <div class="card-body">
    <form action="{{ route('backend.admin.customers.store') }}" method="post" class="accountForm"
      enctype="multipart/form-data">
      @csrf
      <div class="card-body">
        <div class="row">
          <div class="mb-3 col-md-6">
            <label for="title" class="form-label">
              Name
              <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" placeholder="Enter title" name="name"
              value="{{ old('name') }}" required>
          </div>
          <div class="mb-3 col-md-6">
            <label for="title" class="form-label">
              Phone
              <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" placeholder="Enter phone" name="phone"
              value="{{ old('phone') }}" required>
          </div>
          <div class="mb-3 col-md-6">
            <label for="title" class="form-label">
              Address
            </label>
            <input type="text" class="form-control" placeholder="Enter Address" name="address"
              value="{{ old('address') }}">
          </div>
          <div class="mb-3 col-md-6">
            <label for="cnic" class="form-label">
              CNIC <small class="text-muted">(Optional)</small>
            </label>
            <input type="text" class="form-control" placeholder="xxxxx-xxxxxxx-x" name="cnic"
              value="{{ old('cnic') }}" pattern="\d{5}-\d{7}-\d{1}">
            <small class="text-muted">Required for sales over PKR 100,000</small>
          </div>
          <div class="mb-3 col-md-6">
            <label for="credit_limit" class="form-label">
              Credit Limit <small class="text-muted">(Optional)</small>
            </label>
            <input type="number" class="form-control" placeholder="0" name="credit_limit"
              value="{{ old('credit_limit', 0) }}" min="0" step="0.01">
            <small class="text-muted">Maximum allowed outstanding balance</small>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <button type="submit" class="btn bg-gradient-primary">Create</button>
          </div>
        </div>
      </div>
      <!-- /.card-body -->
    </form>
  </div>
</div>
@endsection
@push('script')
<script>
</script>
@endpush