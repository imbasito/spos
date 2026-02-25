<form action="{{ route('backend.admin.settings.website.custom.css.update') }}" method="post" data-ajax-save>
    @csrf
    <div class="card shadow-sm border-0 border-radius-15 mb-4">
        <div class="card-header bg-gradient-maroon py-3">
            <h5 class="text-white mb-0 font-weight-bold">
                <i class="fas fa-code mr-2"></i> Custom CSS
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-light border shadow-sm mb-4">
                <i class="fas fa-info-circle text-info mr-2"></i> 
                <strong>Tip:</strong> You can add custom CSS styles here to override the default theme. Do not include <code>&lt;style&gt;</code> tags.
            </div>
            <div class="form-group">
                <textarea class="form-control apple-input font-monospace bg-dark text-light p-3" rows="20" name="custom_css"
                    placeholder="/* Enter your custom CSS here */">{{ readConfig('custom_css') }}</textarea>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 pt-0 px-4 pb-4">
            <button type="submit" class="btn bg-gradient-primary">
                <i class="fas fa-save mr-1"></i> Save Changes
            </button>
        </div>
    </div>
</form>
