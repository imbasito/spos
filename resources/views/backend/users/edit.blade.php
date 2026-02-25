@extends('backend.master')

@section('title', 'Edit User — ' . $user->name)

@section('content')

{{-- Back button --}}
<div class="mt-n5 mb-3">
    <a href="{{ route('backend.admin.users') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to Users
    </a>
</div>

<div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">

    {{-- Premium Header --}}
    <div class="card-header py-3 d-flex align-items-center" style="background: linear-gradient(45deg, #800000, #A01010);">
        <div class="mr-3">
            <img src="{{ $user->profile_image ? asset('storage/'.$user->profile_image) : nullImg() }}"
                 class="img-circle" style="width:42px;height:42px;object-fit:cover;border:2px solid rgba(255,255,255,0.4);">
        </div>
        <div>
            <h5 class="text-white font-weight-bold mb-0">
                <i class="fas fa-user-edit mr-1"></i> {{ $user->name }}
            </h5>
            <small class="text-white-50">{{ $user->email }}</small>
        </div>
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

    <form action="{{ route('backend.admin.user.edit', $user->id) }}" method="post"
          enctype="multipart/form-data">
        @csrf
        <div class="card-body p-4">
            <div class="row">

                {{-- Full Name --}}
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control apple-input" name="name"
                               value="{{ old('name', $user->name) }}" placeholder="Enter full name" required>
                    </div>
                </div>

                {{-- Email --}}
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Login Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control apple-input" name="email"
                               value="{{ old('email', $user->email) }}" placeholder="user@example.com" required>
                    </div>
                </div>

                {{-- Role --}}
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Role &amp; Permissions <span class="text-danger">*</span></label>
                        <select class="form-control apple-input custom-select" name="role" required>
                            <option value="">— Select a role —</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->id }}"
                                {{ in_array($role->name, $user->getRoleNames()->toArray()) ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Password (optional on edit) --}}
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="font-weight-bold">
                            New Password
                            <span class="text-muted font-weight-normal small">(leave blank to keep current)</span>
                        </label>
                        <input type="password" class="form-control apple-input" name="password"
                               placeholder="Min. 6 characters (optional)">
                    </div>
                </div>

                {{-- Profile Image --}}
                <div class="col-12">
                    <div class="form-group">
                        <label class="font-weight-bold">Profile Photo <span class="text-muted font-weight-normal small">(optional, replaces current)</span></label>
                        <div class="d-flex align-items-center">
                            <img id="avatarPreview"
                                 src="{{ $user->profile_image ? asset('storage/'.$user->profile_image) : nullImg() }}"
                                 class="img-circle shadow-sm mr-3"
                                 style="width:64px;height:64px;object-fit:cover;border:2px solid #dee2e6;">
                            <div class="custom-file flex-grow-1">
                                <input type="file" class="custom-file-input" name="profile_image"
                                       id="profileImageInput" accept="image/*"
                                       onchange="previewAvatar(this)">
                                <label class="custom-file-label" for="profileImageInput">Choose new photo…</label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="card-footer bg-white border-top-0 px-4 pb-4 pt-0">
            <button type="submit" class="btn bg-gradient-primary">
                <i class="fas fa-save mr-1"></i> Save Changes
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
        input.nextElementSibling.textContent = input.files[0].name;
    }
}
</script>
@endpush
