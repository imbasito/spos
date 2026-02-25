<form action="{{ route('backend.admin.settings.website.contacts.update') }}" method="post" data-ajax-save>
    @csrf
    <div class="card shadow-sm border-0 border-radius-15 mb-4">
        <div class="card-header bg-gradient-maroon py-3">
            <h5 class="text-white mb-0 font-weight-bold">
                <i class="fas fa-address-book mr-2"></i> Contact Information
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark">Address</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-map-marker-alt text-muted"></i></span>
                            </div>
                            <input class="form-control apple-input" name="contact_address" type="text"
                                value="{{ readConfig('contact_address') }}" placeholder="Enter full address">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark">Phone Number</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-phone text-muted"></i></span>
                            </div>
                            <input class="form-control apple-input" name="contact_phone" type="tel"
                                value="{{ readConfig('contact_phone') }}" placeholder="e.g. +1 234 567 8900">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark">Mobile Number</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-mobile-alt text-muted"></i></span>
                            </div>
                            <input class="form-control apple-input" name="contact_mobile" type="tel"
                                value="{{ readConfig('contact_mobile') }}" placeholder="e.g. +1 555 0199 888">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark">Fax</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-fax text-muted"></i></span>
                            </div>
                            <input class="form-control apple-input" name="contact_fax" type="tel"
                                value="{{ readConfig('contact_fax') }}" placeholder="Fax Number">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark">Email Address</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-envelope text-muted"></i></span>
                            </div>
                            <input class="form-control apple-input" name="contact_email" type="email"
                                value="{{ readConfig('contact_email') }}" placeholder="contact@example.com">
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="font-weight-bold text-dark">Working Hours</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-0"><i class="fas fa-clock text-muted"></i></span>
                            </div>
                            <input class="form-control apple-input" name="working_hour" type="text" 
                                value="{{ readConfig('working_hour') }}" placeholder="e.g. Mon - Fri: 9:00 AM - 6:00 PM">
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
