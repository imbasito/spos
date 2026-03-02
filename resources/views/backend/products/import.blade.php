@extends('backend.master')

@section('title', 'Import Products')

@section('content')
<div class="row animate__animated animate__fadeInUp">
<div class="col-lg-9 col-xl-7 mx-auto">

    {{-- ══════════════════════════════════════════════ --}}
    {{-- PAGE HEADER                                    --}}
    {{-- ══════════════════════════════════════════════ --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div>
        <h5 class="font-weight-bold mb-0">
          <i class="fas fa-file-import text-primary mr-2"></i>Bulk Product Import
        </h5>
      </div>
      <a href="{{ route('backend.admin.products.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to Products
      </a>
    </div>


{{-- ══════════════════════════════════════════════ --}}
    {{-- SUCCESS / WARNING / ERROR ALERTS               --}}
    {{-- ══════════════════════════════════════════════ --}}
    @if(session('success'))
      <div class="alert alert-success border-0 shadow-sm d-flex align-items-center">
        <i class="fas fa-check-circle fa-lg mr-3 text-success"></i>
        <div>{{ session('success') }}</div>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center">
        <i class="fas fa-exclamation-circle fa-lg mr-3 text-danger"></i>
        <div>{{ session('error') }}</div>
      </div>
    @endif
    @if(session('import_errors'))
      <div class="alert alert-warning border-0 shadow-sm">
        <div class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-2"></i>Skipped Rows</div>
        <ul class="small mb-0 pl-3">
          @foreach(session('import_errors') as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- ══════════════════════════════════════════════ --}}
    {{-- STEP 2 — REVIEW & CONFIRM (after preview)     --}}
    {{-- ══════════════════════════════════════════════ --}}
    @if(session('import_preview'))
      @php($preview = session('import_preview'))
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0 pt-3 pb-0">
          <h6 class="font-weight-bold mb-0">
            <i class="fas fa-clipboard-check text-info mr-2"></i>Step 2 — Review &amp; Confirm
          </h6>
        </div>
        <div class="card-body">

          {{-- Row counts --}}
          <div class="row text-center mb-2">
            <div class="col-4">
              <div class="p-2 rounded" style="background:#f0f4ff">
                <div class="font-weight-bold" style="font-size:1.3rem;color:#007bff">{{ $preview['total_rows'] ?? 0 }}</div>
                <div class="small text-muted">Total Rows</div>
              </div>
            </div>
            <div class="col-4">
              <div class="p-2 rounded" style="background:#f0fff4">
                <div class="font-weight-bold" style="font-size:1.3rem;color:#28a745">{{ $preview['valid_rows'] ?? 0 }}</div>
                <div class="small text-muted">Valid</div>
              </div>
            </div>
            <div class="col-4">
              <div class="p-2 rounded" style="background:#fff8f0">
                <div class="font-weight-bold" style="font-size:1.3rem;color:{{ ($preview['invalid_rows'] ?? 0) > 0 ? '#dc3545' : '#6c757d' }}">{{ $preview['invalid_rows'] ?? 0 }}</div>
                <div class="small text-muted">Skipped</div>
              </div>
            </div>
          </div>

          {{-- Validation errors detail --}}
          @if(!empty($preview['errors']))
            <div class="border rounded p-2 mb-2" style="background:#fffdf0;border-color:#ffc107 !important;max-height:120px;overflow-y:auto">
              <p class="small font-weight-bold text-warning mb-1"><i class="fas fa-exclamation-triangle mr-1"></i>Row issues (these rows will be skipped):</p>
              <ul class="small mb-0 pl-3 text-muted">
                @foreach($preview['errors'] as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          {{-- No valid rows — abort early --}}
          @if(($preview['valid_rows'] ?? 0) === 0)
            <div class="alert alert-danger mb-0">
              <i class="fas fa-times-circle mr-2"></i>
              No valid rows found. Please fix the errors above and try again.
            </div>
          @else

            {{-- No supplier guard --}}
            @if($suppliers->isEmpty())
              <div class="alert alert-warning d-flex align-items-center">
                <i class="fas fa-store-slash fa-lg mr-3 text-warning"></i>
                <div>
                  <strong>No suppliers found.</strong> A supplier is required to record the purchase.
                  <br><a href="{{ route('backend.admin.suppliers.create') }}" class="font-weight-bold" target="_blank">
                    <i class="fas fa-plus mr-1"></i>Create a Supplier
                  </a> — then come back and re-upload your file.
                </div>
              </div>
            @else

              {{-- CONFIRM FORM --}}
              <form action="{{ route('backend.admin.products.import') }}" method="post" id="confirmImportForm">
                @csrf
                <input type="hidden" name="action" value="import">
                <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">

                <div class="row">
                  <div class="col-md-6 mb-2">
                    <label class="font-weight-bold small mb-1">
                      Supplier <span class="text-danger">*</span>
                      <span class="text-muted font-weight-normal">(purchase will be recorded under this supplier)</span>
                    </label>
                    <select name="supplier_id" class="form-control" required>
                      <option value="">Select supplier…</option>
                      @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-6 mb-2">
                    <label class="font-weight-bold small mb-1">
                      Payment Status <span class="text-danger">*</span>
                      <span class="text-muted font-weight-normal">(how to record this purchase)</span>
                    </label>
                    <select name="payment_option" class="form-control" required>
                      <option value="due" {{ old('payment_option', 'due') === 'due' ? 'selected' : '' }}>Due — pay supplier later</option>
                      <option value="paid" {{ old('payment_option') === 'paid' ? 'selected' : '' }}>Paid — already settled</option>
                    </select>
                  </div>
                </div>

                <div class="d-flex align-items-center mt-2">
                  <button type="submit" class="btn btn-success px-4 font-weight-bold mr-3" id="confirmImportBtn">
                    <i class="fas fa-check mr-2"></i>Import {{ $preview['valid_rows'] ?? 0 }} Product(s)
                  </button>
                  <a href="{{ route('backend.admin.products.import') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-undo mr-1"></i>Back
                  </a>

                  {{-- Loading indicator (hidden initially) --}}
                  <div id="importingSpinner" class="ml-3 d-none">
                    <span class="spinner-border spinner-border-sm text-primary mr-2" role="status"></span>
                    <span class="small text-muted">Importing, please wait…</span>
                  </div>
                </div>
              </form>

            @endif
          @endif
        </div>
      </div>
    @endif

    {{-- ══════════════════════════════════════════════ --}}
    {{-- STEP 1 — UPLOAD FILE                          --}}
    {{-- ══════════════════════════════════════════════ --}}
    @if(!session('import_preview'))
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white border-0 pt-3 pb-0">
        <h6 class="font-weight-bold mb-0">
          <i class="fas fa-upload text-primary mr-2"></i>Step 1 — Upload File
        </h6>
      </div>
      <div class="card-body">

        {{-- Template download banner --}}
        <div class="rounded p-2 mb-3 d-flex align-items-center justify-content-between" style="background:#f0f4ff;border:1px dashed #94b4f5">
          <div>
            <div class="font-weight-bold small">Don't have a file yet?</div>
            <div class="small text-muted mt-1">Download our template — it includes 6 example products with all columns correctly named and ordered.</div>
          </div>
          <a href="{{ route('backend.admin.products.import', ['download-demo' => true]) }}"
             class="btn btn-sm btn-primary ml-3 text-nowrap">
            <i class="fas fa-download mr-1"></i> Download Template
          </a>
        </div>

        {{-- Column guide (collapsible) --}}
        <div class="mb-3">
          <a class="small font-weight-bold text-muted text-uppercase d-flex align-items-center" style="letter-spacing:.05em;cursor:pointer;text-decoration:none" data-toggle="collapse" href="#colGuide" role="button">
            <i class="fas fa-table mr-1"></i> Column Reference
            <i class="fas fa-chevron-down ml-1" style="font-size:.65rem"></i>
          </a>
          <div class="collapse" id="colGuide">
            <div class="table-responsive mt-2">
              <table class="table table-sm table-bordered small mb-0" style="font-size:.78rem">
                <thead class="thead-light">
                  <tr><th>Column</th><th>Required?</th><th>Notes</th></tr>
                </thead>
                <tbody>
                  <tr><td><code>name</code></td><td><span class="badge badge-danger">Required</span></td><td>Product name</td></tr>
                  <tr><td><code>category</code></td><td><span class="badge badge-danger">Required</span></td><td>Created automatically if new</td></tr>
                  <tr><td><code>brand</code></td><td><span class="badge badge-danger">Required</span></td><td>Created automatically if new</td></tr>
                  <tr><td><code>unit</code></td><td><span class="badge badge-danger">Required</span></td><td>e.g. <code>pcs</code>, <code>kg</code>, <code>litre</code> — created if new</td></tr>
                  <tr><td><code>price</code></td><td><span class="badge badge-danger">Required</span></td><td>Selling price (number)</td></tr>
                  <tr><td><code>purchase_price</code></td><td><span class="badge badge-danger">Required</span></td><td>Cost price (number)</td></tr>
                  <tr><td><code>quantity</code></td><td><span class="badge badge-danger">Required</span></td><td>Must be &gt; 0</td></tr>
                  <tr class="table-light"><td><code>sku</code></td><td><span class="badge badge-secondary">Optional</span></td><td>Auto-generated if blank</td></tr>
                  <tr class="table-light"><td><code>barcode</code></td><td><span class="badge badge-secondary">Optional</span></td><td>Auto-generated if blank</td></tr>
                  <tr class="table-light"><td><code>description</code></td><td><span class="badge badge-secondary">Optional</span></td><td>Any text</td></tr>
                  <tr class="table-light"><td><code>discount</code></td><td><span class="badge badge-secondary">Optional</span></td><td>0 if none</td></tr>
                  <tr class="table-light"><td><code>discount_type</code></td><td><span class="badge badge-secondary">Optional</span></td><td><code>fixed</code> or <code>percentage</code></td></tr>
                  <tr class="table-light"><td><code>expire_date</code></td><td><span class="badge badge-secondary">Optional</span></td><td>YYYY-MM-DD or Excel date cell — leave blank if N/A</td></tr>
                  <tr class="table-light"><td><code>status</code></td><td><span class="badge badge-secondary">Optional</span></td><td><code>1</code> active, <code>0</code> inactive (default 1)</td></tr>
                </tbody>
              </table>
            </div>
            <p class="small text-muted mt-1 mb-0"><i class="fas fa-info-circle mr-1"></i>Column headers are <strong>not</strong> case-sensitive. Unit can be a full name or short form — it is created automatically if it doesn't already exist.</p>
          </div>
        </div>

        {{-- Upload form --}}
        <form action="{{ route('backend.admin.products.import') }}" method="post"
              enctype="multipart/form-data" id="previewForm">
          @csrf
          <input type="hidden" name="action" value="preview">

          <div class="form-group mb-3">
            <label class="font-weight-bold small mb-1">Choose file <span class="text-muted font-weight-normal">(.csv, .xls, .xlsx — max 10 MB)</span></label>
            <div class="custom-file">
              <input type="file" class="custom-file-input" name="file" id="importFile"
                     accept=".csv,.xls,.xlsx" required>
              <label class="custom-file-label" for="importFile" id="importFileLabel">Choose file…</label>
            </div>
          </div>

          {{-- Progress bar (hidden until submit) --}}
          <div id="uploadProgress" class="d-none mb-3">
            <div class="small text-muted mb-1">Validating file rows…</div>
            <div class="progress" style="height:6px;border-radius:3px">
              <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary w-100" role="progressbar"></div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary px-4 font-weight-bold" id="previewBtn">
            <i class="fas fa-search mr-2"></i>Preview
          </button>
        </form>

      </div>
    </div>
    @endif

  </div>
</div>
@endsection

@push('style')
<style>
  code { background: #f0f4ff; padding: 1px 5px; border-radius: 3px; font-size: .8rem; color: #0056b3; }
</style>
@endpush

@push('script')
<script>
  // Update file label with chosen filename
  document.getElementById('importFile')?.addEventListener('change', function () {
    const label = document.getElementById('importFileLabel');
    if (label) label.textContent = this.files.length ? this.files[0].name : 'Choose file…';
  });

  // Show animated progress bar when preview form submits
  document.getElementById('previewForm')?.addEventListener('submit', function () {
    const btn = document.getElementById('previewBtn');
    const bar = document.getElementById('uploadProgress');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2" role="status"></span>Analysing…'; }
    if (bar) bar.classList.remove('d-none');
  });

  // Show spinner on confirm submit
  document.getElementById('confirmImportForm')?.addEventListener('submit', function () {
    const btn   = document.getElementById('confirmImportBtn');
    const spin  = document.getElementById('importingSpinner');
    if (btn)  { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2" role="status"></span>Importing…'; }
    if (spin) spin.classList.remove('d-none');
  });
</script>
@endpush