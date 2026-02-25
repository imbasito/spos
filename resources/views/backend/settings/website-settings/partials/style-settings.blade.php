<form action="{{ route('backend.admin.settings.website.style.settings.update') }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="card shadow-sm border-0 border-radius-15 mb-4">
        <div class="card-header bg-gradient-maroon py-3">
            <h5 class="text-white mb-0 font-weight-bold">
                <i class="fas fa-swatchbook mr-2"></i> Branding &amp; Visuals
            </h5>
        </div>
        <div class="card-body p-4">
            
            <div class="row mb-5">
                <div class="col-md-12">
                    <h6 class="font-weight-bold text-maroon border-bottom pb-2 mb-3">Main Logo</h6>
                </div>
                <div class="col-md-4 text-center">
                    <div class="p-3 border rounded bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                        <img src="{{ assetImage(readconfig('site_logo')) }}" class="img-fluid site-logo-placeholder" style="max-height: 100px;">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="font-weight-bold">Upload New Logo</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="site_logo" id="site_logo" accept="image/*" onchange="previewThumbnail(this)">
                            <label class="custom-file-label" for="site_logo">Choose file</label>
                        </div>
                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle"></i> Recommended Dimensions: 260x60 px. Supports PNG, JPG, SVG.
                        </small>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-12">
                     <h6 class="font-weight-bold text-maroon border-bottom pb-2 mb-3">Icons</h6>
                </div>
                <div class="col-md-6 border-right">
                    <div class="form-group text-center">
                        <label class="font-weight-bold d-block mb-3">Favicon (Browser Tab)</label>
                        <div class="mb-3 d-inline-block p-2 border rounded bg-white">
                            <img src="{{ assetImage(readconfig('favicon_icon')) }}" class="img-fluid" style="width: 32px; height: 32px;">
                        </div>
                        <div class="custom-file text-left">
                            <input type="file" class="custom-file-input" name="favicon_icon" id="favicon_icon" accept="image/*" onchange="previewThumbnail(this)">
                            <label class="custom-file-label" for="favicon_icon">Choose 32x32 Icon</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group text-center">
                        <label class="font-weight-bold d-block mb-3">Apple Touch Icon</label>
                        <div class="mb-3 d-inline-block p-2 border rounded bg-white">
                            <img src="{{ assetImage(readconfig('favicon_icon_apple')) }}" class="img-fluid" style="width: 64px; height: 64px;">
                        </div>
                        <div class="custom-file text-left">
                            <input type="file" class="custom-file-input" name="favicon_icon_apple" id="favicon_icon_apple" accept="image/*" onchange="previewThumbnail(this)">
                            <label class="custom-file-label" for="favicon_icon_apple">Choose 180x180 Icon</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h6 class="font-weight-bold text-maroon border-bottom pb-2 mb-3">Misc Features</h6>
                    <div class="form-group">
                         <label class="font-weight-bold mr-3">Newsletter Subscription Form</label>
                         <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-outline-success {{ readConfig('newsletter_subscribe') == 1 ? 'active' : '' }}">
                                <input type="radio" name="newsletter_subscribe" value="1" {{ readConfig('newsletter_subscribe') == 1 ? 'checked' : '' }}> 
                                <i class="fas fa-check mr-1"></i> Active
                            </label>
                            <label class="btn btn-outline-danger {{ readConfig('newsletter_subscribe') == 0 ? 'active' : '' }}">
                                <input type="radio" name="newsletter_subscribe" value="0" {{ readConfig('newsletter_subscribe') == 0 ? 'checked' : '' }}> 
                                <i class="fas fa-times mr-1"></i> Inactive
                            </label>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="card-footer bg-white border-top-0 pt-0 px-4 pb-4">
            <button type="submit" class="btn bg-gradient-primary">
                <i class="fas fa-save mr-1"></i> Save Changes
            </button>
        </div>
    </div>
</form>
