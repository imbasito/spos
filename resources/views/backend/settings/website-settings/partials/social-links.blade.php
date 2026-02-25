<form action="{{ route('backend.admin.settings.website.social.link.update') }}" method="post" data-ajax-save>
    @csrf
    <div class="card shadow-sm border-0 border-radius-15 mb-4">
        <div class="card-header bg-gradient-maroon py-3">
            <h5 class="text-white mb-0 font-weight-bold">
                <i class="fas fa-share-alt mr-2"></i> Social Media Links
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark"><i class="fab fa-facebook text-primary mr-1"></i> Facebook</label>
                        <input class="form-control apple-input" name="facebook_link" type="url"
                            value="{{ readConfig('facebook_link') }}" placeholder="https://facebook.com/yourpage">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark"><i class="fab fa-twitter text-info mr-1"></i> Twitter (X)</label>
                        <input class="form-control apple-input" name="twitter_link" type="url"
                            value="{{ readConfig('twitter_link') }}" placeholder="https://twitter.com/yourhandle">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark"><i class="fab fa-linkedin text-primary mr-1"></i> LinkedIn</label>
                        <input class="form-control apple-input" name="linkedin_link" type="url"
                            value="{{ readConfig('linkedin_link') }}" placeholder="https://linkedin.com/in/yourprofile">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark"><i class="fab fa-youtube text-danger mr-1"></i> YouTube</label>
                        <input class="form-control apple-input" name="youtube_link" type="url"
                            value="{{ readConfig('youtube_link') }}" placeholder="https://youtube.com/channel">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark"><i class="fab fa-instagram text-danger mr-1"></i> Instagram</label>
                        <input class="form-control apple-input" name="instagram_link" type="url"
                            value="{{ readConfig('instagram_link') }}" placeholder="https://instagram.com/yourhandle">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark"><i class="fab fa-whatsapp text-success mr-1"></i> WhatsApp</label>
                        <input class="form-control apple-input" name="whatsapp_link" type="url"
                            value="{{ readConfig('whatsapp_link') }}" placeholder="https://wa.me/number">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark"><i class="fab fa-pinterest text-danger mr-1"></i> Pinterest</label>
                        <input class="form-control apple-input" name="pinterest_link" type="url"
                            value="{{ readConfig('pinterest_link') }}" placeholder="https://pinterest.com/username">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark"><i class="fab fa-snapchat text-warning mr-1"></i> Snapchat</label>
                        <input class="form-control apple-input" name="snapchat_link" type="url"
                            value="{{ readConfig('snapchat_link') }}" placeholder="Snapchat Username">
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
