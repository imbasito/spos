<form action="{{ route('backend.admin.settings.website.status.update') }}" method="post" data-ajax-save>
    @csrf
    <div class="card shadow-sm border-0 border-radius-15 mb-4">
        <div class="card-header bg-gradient-maroon py-3">
            <h5 class="text-white mb-0 font-weight-bold">
                <i class="fas fa-power-off mr-2"></i> Maintenance Mode
            </h5>
        </div>
        <div class="card-body p-4">
            
             <div class="row justify-content-center">
                <div class="col-md-8 text-center mb-4">
                    <label class="font-weight-bold d-block mb-3">Website Status</label>
                    <div class="btn-group btn-group-toggle btn-block shadow-sm" data-toggle="buttons">
                        <label class="btn btn-lg btn-outline-success {{ readConfig('is_live') == 1 ? 'active' : '' }}">
                            <input type="radio" name="is_live" value="1" {{ readConfig('is_live') == 1 ? 'checked' : '' }}> 
                            <i class="fas fa-check-circle mr-2"></i> Live (Online)
                        </label>
                        <label class="btn btn-lg btn-outline-danger {{ readConfig('is_live') == 0 ? 'active' : '' }}">
                            <input type="radio" name="is_live" value="0" {{ readConfig('is_live') == 0 ? 'checked' : '' }}> 
                            <i class="fas fa-tools mr-2"></i> Maintenance Mode
                        </label>
                    </div>
                </div>

                <div class="col-md-10 {{ readConfig('is_live') == 1 ? 'd-none' : '' }}" id="close_msg_div">
                    <div class="form-group">
                        <label class="font-weight-bold text-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Maintenance Message (HTML Allowed)</label>
                        <textarea class="form-control apple-input border-danger" rows="6" name="close_msg"
                            placeholder="We are currently performing scheduled maintenance...">{{ readConfig('close_msg') }}</textarea>
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
