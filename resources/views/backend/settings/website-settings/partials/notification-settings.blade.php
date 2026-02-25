<form action="{{ route('backend.admin.settings.website.notification.settings.update') }}" method="post" data-ajax-save>
    @csrf
    <div class="card shadow-sm border-0 border-radius-15 mb-4">
        <div class="card-header bg-gradient-maroon py-3">
            <h5 class="text-white mb-0 font-weight-bold">
                <i class="fas fa-envelope mr-2"></i> Notification Settings
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark">Admin Notification Email</label>
                        <input class="form-control apple-input" name="notify_email_address" type="email"
                            value="{{ readConfig('notify_email_address') }}" placeholder="admin@example.com">
                        <small class="text-muted">System alerts will be sent to this address.</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border rounded shadow-sm">
                        <div class="card-body">
                            <h6 class="font-weight-bold mb-3"><i class="fas fa-comment-alt mr-2 text-primary"></i> New Contact Messages</h6>
                            <p class="text-muted small">Receive an email when a user submits the "Contact Us" form.</p>
                            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                <label class="btn btn-outline-success {{ readConfig('notify_messages_status') == 1 ? 'active' : '' }}">
                                    <input type="radio" name="notify_messages_status" value="1" {{ readConfig('notify_messages_status') == 1 ? 'checked' : '' }}> Yes
                                </label>
                                <label class="btn btn-outline-danger {{ readConfig('notify_messages_status') == 0 ? 'active' : '' }}">
                                    <input type="radio" name="notify_messages_status" value="0" {{ readConfig('notify_messages_status') == 0 ? 'checked' : '' }}> No
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border rounded shadow-sm">
                         <div class="card-body">
                            <h6 class="font-weight-bold mb-3"><i class="fas fa-comments mr-2 text-info"></i> New Comments</h6>
                            <p class="text-muted small">Receive an email when a user comments on a blog posts.</p>
                            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                <label class="btn btn-outline-success {{ readConfig('notify_comments_status') == 1 ? 'active' : '' }}">
                                    <input type="radio" name="notify_comments_status" value="1" {{ readConfig('notify_comments_status') == 1 ? 'checked' : '' }}> Yes
                                </label>
                                <label class="btn btn-outline-danger {{ readConfig('notify_comments_status') == 0 ? 'active' : '' }}">
                                    <input type="radio" name="notify_comments_status" value="0" {{ readConfig('notify_comments_status') == 0 ? 'checked' : '' }}> No
                                </label>
                            </div>
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
