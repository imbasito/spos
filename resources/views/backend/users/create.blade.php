@extends('backend.master')

@section('title', 'Create User')

@section('content')


<div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">

    {{-- Premium Header --}}
    <div class="card-header py-3 d-flex align-items-center" style="background: linear-gradient(45deg, #800000, #A01010);">
        <h5 class="text-white font-weight-bold mb-0">
            <i class="fas fa-user-plus mr-2"></i> Create New User
        </h5>
        <a href="{{ route('backend.admin.users') }}" class="btn btn-light btn-sm ml-auto font-weight-bold text-maroon">
            <i class="fas fa-arrow-left mr-1"></i> Back to Users
        </a>
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
    <div class="alert alert-danger border-0 rounded-0 mb-0 px-4 py-3">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-1 pl-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('backend.admin.user.create') }}" method="post"
          enctype="multipart/form-data">
        @csrf
        <div class="card-body p-4">
            <div class="row">

                {{-- Full Name --}}
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control apple-input" name="name"
                               value="{{ old('name') }}" placeholder="Enter full name" required autofocus>
                    </div>
                </div>

                {{-- Email --}}
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Login Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control apple-input" name="email"
                               value="{{ old('email') }}" placeholder="user@example.com" required>
                    </div>
                </div>

                {{-- Role --}}
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Role &amp; Permissions <span class="text-danger">*</span></label>
                        <select class="form-control apple-input custom-select" name="role" required>
                            <option value="">— Select a role —</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Password --}}
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control apple-input" name="password"
                               placeholder="Min. 6 characters" required>
                    </div>
                </div>

                {{-- Profile Image --}}
                <div class="col-12">
                    <div class="form-group">
                        <label class="font-weight-bold">Profile Photo <span class="text-muted font-weight-normal small">(optional)</span></label>
                        <div class="d-flex align-items-center">
                            <img id="avatarPreview" src="{{ nullImg() }}"
                                 class="img-circle shadow-sm mr-3"
                                 style="width:64px;height:64px;object-fit:cover;border:2px solid #dee2e6;">
                            <div class="custom-file flex-grow-1">
                                <input type="file" class="custom-file-input" name="profile_image"
                                       id="profileImageInput" accept="image/*"
                                       onchange="previewAvatar(this)">
                                <label class="custom-file-label" for="profileImageInput">Choose photo…</label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="card-footer bg-white border-top-0 px-4 pb-4 pt-0">
            <button type="submit" class="btn bg-gradient-maroon text-white px-5 font-weight-bold">
                <i class="fas fa-user-plus mr-1"></i> Create User
            </button>
        </div>
    </form>
</div>

@endsection

@push('script')
<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
        // Update custom-file-label
        input.nextElementSibling.textContent = input.files[0].name;
    }
}
</script>
@endpush
