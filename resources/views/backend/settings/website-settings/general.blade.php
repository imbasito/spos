@extends('backend.master')

@section('title', 'General Settings')

@section('content')

<div class="row">
    <div class="col-4 col-sm-2">
        <div class="nav flex-column nav-tabs h-100" id="vert-tabs-tab" role="tablist" aria-orientation="vertical">
            @can('website_settings')
            <a class="nav-link {{ @$_GET['active-tab'] == 'website-info' ? 'active' : '' }}" id="vert-tabs-1"
                data-toggle="pill" href="#tabs-1" role="tab" aria-controls="tabs-1" aria-selected="true">
                <i class="fas fa-desktop"></i>
                &nbsp;Website Info
            </a>
            @endcan
            @can('contact_settings')
            <a class="nav-link {{ @$_GET['active-tab'] == 'contacts' ? 'active' : '' }}" id="vert-tabs-2"
                data-toggle="pill" href="#tabs-2" role="tab" aria-controls="tabs-2" aria-selected="false">
                <i class="fas fa-address-book"></i>
                &nbsp;Contacts
            </a>
            @endcan
            @can('socials_settings')
            <a class="nav-link {{ @$_GET['active-tab'] == 'social-links' ? 'active' : '' }}" id="vert-tabs-3"
                data-toggle="pill" href="#tabs-3" role="tab" aria-controls="tabs-3" aria-selected="false">
                <i class="fas fa-share-alt"></i>
                &nbsp;Social Links
            </a>
            @endcan
            @can('style_settings')
            <a class="nav-link {{ @$_GET['active-tab'] == 'style-settings' ? 'active' : '' }}" id="vert-tabs-4"
                data-toggle="pill" href="#tabs-4" role="tab" aria-controls="tabs-4" aria-selected="false">
                <i class="fas fa-swatchbook"></i>
                &nbsp;Style Settings
            </a>
            @endcan
            @can('custom_settings')
            <a class="nav-link {{ @$_GET['active-tab'] == 'custom-css' ? 'active' : '' }}" id="vert-tabs-5"
                data-toggle="pill" href="#tabs-5" role="tab" aria-controls="tabs-5" aria-selected="false">
                <i class="fas fa-code"></i>
                &nbsp;Custom CSS
            </a>
            @endcan
            @can('notification_settings')
            <a class="nav-link {{ @$_GET['active-tab'] == 'notification-settings' ? 'active' : '' }}" id="vert-tabs-6"
                data-toggle="pill" href="#tabs-6" role="tab" aria-controls="tabs-6" aria-selected="false">
                <i class="fas fa-envelope"></i>
                &nbsp;Notification Settings
            </a>
            @endcan
            @can('website_status_settings')
            <a class="nav-link {{ @$_GET['active-tab'] == 'website-status' ? 'active' : '' }}" id="vert-tabs-7"
                data-toggle="pill" href="#tabs-7" role="tab" aria-controls="tabs-7" aria-selected="false">
                <i class="fas fa-power-off"></i>
                &nbsp;Website Status
            </a>
            @endcan
            @can('invoice_settings')
            <a class="nav-link {{ @$_GET['active-tab'] == 'invoice-settings' ? 'active' : '' }}" id="vert-tabs-8"
                data-toggle="pill" href="#tabs-8" role="tab" aria-controls="tabs-8" aria-selected="false">
                <i class="fas fa-file-invoice"></i>
                &nbsp;Invoice Settings
            </a>
            @endcan
            @can('website_settings')
            <a class="nav-link {{ @$_GET['active-tab'] == 'printer-settings' ? 'active' : '' }}" id="vert-tabs-9"
                data-toggle="pill" href="#tabs-9" role="tab" aria-controls="tabs-9" aria-selected="false">
                <i class="fas fa-print"></i>
                &nbsp;Printer Settings
            </a>
            @endcan
        </div>
    </div>
    <div class="col-8 col-sm-10">
        <div class="tab-content" id="vert-tabs-tabContent">
            @can('website_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'website-info' ? 'active show' : '' }}" id="tabs-1"
                role="tabpanel" aria-labelledby="vert-tabs-1">

                <form action="{{ route('backend.admin.settings.website.info.update') }}" method="post">
                    @csrf
                    <div class="col-md-12 d-flex justify-content-between">
                        <h5>
                            <i class="fas fa-desktop"></i>
                            &nbsp;&nbsp;Website Info
                        </h5>
                        <button type="submit" class="btn bg-gradient-primary">
                            <i class="fas fa-reply"></i>
                            &nbsp;Save Changes
                        </button>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Website Title</label>
                            <input class="form-control" name="site_name" type="text"
                                value="{{ readConfig('site_name') }}" placeholder="Enter Site Title">
                        </div>
                        <div class="form-group">
                            <label>Meta Description</label>
                            <textarea class="form-control" rows="2" name="meta_description" cols="50"
                                placeholder="Enter Meta Description">{{ readConfig('meta_description') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Meta Keywords</label>
                            <textarea class="form-control" rows="2" name="meta_keywords" cols="50" placeholder="Enter Keywords">{{ readConfig('meta_keywords') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Website URL</label>
                            <input class="form-control" name="site_url" type="text"
                                value="{{ readConfig('site_url') }}" placeholder="Enter Site URL (Optional)">
                        </div>
                    </div>
                </form>

            </div>
            @endcan
            @can('contact_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'contacts' ? 'active show' : '' }}" id="tabs-2"
                role="tabpanel" aria-labelledby="vert-tabs-2">

                <form action="{{ route('backend.admin.settings.website.contacts.update') }}" method="post">
                    @csrf
                    <div class="col-md-12 d-flex justify-content-between">
                        <h5>
                            <i class="fas fa-address-book"></i>
                            &nbsp;&nbsp;Contacts
                        </h5>
                        <button type="submit" class="btn bg-gradient-primary">
                            <i class="fas fa-reply"></i>
                            &nbsp;Save Changes
                        </button>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Address</label>
                            <input placeholder="" class="form-control" name="contact_address" type="text"
                                value="{{ readConfig('contact_address') }}">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input placeholder="Phone" class="form-control" name="contact_phone" type="tel"
                                value="{{ readConfig('contact_phone') }}">
                        </div>
                        <div class="form-group">
                            <label>Fax</label>
                            <input placeholder="Fax" class="form-control" name="contact_fax" type="tel"
                                value="{{ readConfig('contact_fax') }}">
                        </div>
                        <div class="form-group">
                            <label>Mobile</label>
                            <input placeholder="Mobile" class="form-control" name="contact_mobile" type="tel"
                                value="{{ readConfig('contact_mobile') }}">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input placeholder="Email" class="form-control" name="contact_email" type="email"
                                value="{{ readConfig('contact_email') }}">
                        </div>
                        <div class="form-group">
                            <label>Working Time</label>
                            <input placeholder="Sunday to Thursday 08:00 AM to 05:00 PM" class="form-control"
                                name="working_hour" type="text" value="{{ readConfig('working_hour') }}">
                        </div>
                    </div>
                </form>

            </div>
            @endcan
            @can('socials_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'social-links' ? 'active show' : '' }}"
                id="tabs-3" role="tabpanel" aria-labelledby="vert-tabs-3">
                <form action="{{ route('backend.admin.settings.website.social.link.update') }}" method="post">
                    @csrf
                    <div class="col-md-12 d-flex justify-content-between">
                        <h5>
                            <i class="fas fa-share-alt"></i>
                            &nbsp;&nbsp;Social Links
                        </h5>
                        <button type="submit" class="btn bg-gradient-primary">
                            <i class="fas fa-reply"></i>
                            &nbsp;Save Changes
                        </button>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>
                                <i class="fab fa-facebook"></i>
                                &nbsp; Facebook
                            </label>
                            <input placeholder="Facebook" class="form-control" name="facebook_link" type="url"
                                value="{{ readConfig('facebook_link') }}">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fab fa-twitter"></i>
                                &nbsp; Twitter
                            </label>
                            <input placeholder="Twitter" class="form-control" name="twitter_link" type="url"
                                value="{{ readConfig('twitter_link') }}">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fab fa-linkedin"></i>
                                &nbsp; Linkedin
                            </label>
                            <input placeholder="Linkedin" class="form-control" name="linkedin_link" type="url"
                                value="{{ readConfig('linkedin_link') }}">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fab fa-youtube"></i>
                                &nbsp; Youtube
                            </label>
                            <input placeholder="Youtube" class="form-control" name="youtube_link" type="url"
                                value="{{ readConfig('youtube_link') }}">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fab fa-instagram"></i>
                                &nbsp; Instagram
                            </label>
                            <input placeholder="Instagram" class="form-control" name="instagram_link" type="url"
                                value="{{ readConfig('instagram_link') }}">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fab fa-pinterest"></i>
                                &nbsp; Pinterest
                            </label>
                            <input placeholder="Pinterest" class="form-control" name="pinterest_link" type="url"
                                value="{{ readConfig('pinterest_link') }}">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fab fa-tumblr"></i>
                                &nbsp; Tumblr
                            </label>
                            <input placeholder="Tumblr" class="form-control" name="tumblr_link" type="url"
                                value="{{ readConfig('tumblr_link') }}">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fab fa-snapchat"></i>
                                &nbsp; Snapchat
                            </label>
                            <input placeholder="Snapchat" class="form-control" name="snapchat_link" type="url"
                                value="{{ readConfig('snapchat_link') }}">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fab fa-whatsapp"></i>
                                &nbsp; Whatsapp
                            </label>
                            <input placeholder="Whatsapp" class="form-control" name="whatsapp_link" type="url"
                                value="{{ readConfig('whatsapp_link') }}">
                        </div>
                    </div>
                </form>
            </div>
            @endcan
            @can('style_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'style-settings' ? 'active show' : '' }}"
                id="tabs-4" role="tabpanel" aria-labelledby="vert-tabs-4">

                <form action="{{ route('backend.admin.settings.website.style.settings.update') }}" method="post"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="col-md-12 d-flex justify-content-between">
                        <h5>
                            <i class="fas fa-swatchbook"></i>
                            &nbsp;&nbsp;Style Settings
                        </h5>
                        <button type="submit" class="btn bg-gradient-primary">
                            <i class="fas fa-reply"></i>
                            &nbsp;Save Changes
                        </button>
                    </div>

                    <div class="col-12 my-2">
                        <label>Site Logo</label>

                        <div class="row">
                            <div class="col-sm-12">
                                <div class="col-sm-12 box p-a-xs text-center">
                                    <img src="{{ assetImage(readconfig('site_logo')) }}"
                                        class="img-fluid thumbnail-preview site-logo-placeholder">
                                </div>
                            </div>
                        </div>
                        <input class="form-control" accept="image/*" name="site_logo" type="file"
                            onchange="previewThumbnail(this)">
                        <small>
                            <i class="far fa-question-circle"></i>
                            ( 260x60 px ) - Extensions: .png, .jpg, .jpeg, .gif, .svg
                        </small>
                    </div>
                    <hr>
                    <div class="form-group row">
                        <div class="col-sm-6">
                            <label for="style_fav">Favicon</label>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="col-sm-12 box p-a-xs text-center">
                                        <a target="_blank" href="{{ assetImage(readconfig('favicon_icon')) }}">
                                            <img src="{{ assetImage(readconfig('favicon_icon')) }}"
                                                class="img-fluid thumbnail-preview site-logo-placeholder">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <input class="form-control" accept="image/*" name="favicon_icon" type="file"
                                onchange="previewThumbnail(this)">
                            <small>
                                <i class="far fa-question-circle"></i>
                                ( 32x32 px ) - Extensions: .png, .jpg, .jpeg, .gif, .svg
                            </small>
                        </div>
                        <div class="col-sm-6">
                            <label for="style_apple">Apple Icon</label>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="col-sm-12 box p-a-xs text-center">
                                        <a target="_blank" href="{{ assetImage(readconfig('favicon_icon_apple')) }}">
                                            <img src="{{ assetImage(readconfig('favicon_icon_apple')) }}"
                                                class="img-fluid thumbnail-preview site-logo-placeholder">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <input class="form-control" accept="image/*" name="favicon_icon_apple" type="file"
                                onchange="previewThumbnail(this)">
                            <small>
                                <i class="far fa-question-circle"></i>
                                ( 180x180 px ) - Extensions: .png, .jpg, .jpeg, .gif, .svg
                            </small>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label>Newsletter Subscribe</label>
                        <div class="radio bg-white rounded pt-2 pl-2 border">
                            <label class="ui-check ui-check-md">
                                <input {{ readConfig('newsletter_subscribe') == 1 ? 'checked' : '' }}
                                    name="newsletter_subscribe" type="radio" value="1">
                                <i class="dark-white"></i>
                                Active
                            </label>
                            &nbsp; &nbsp;
                            <label class="ui-check ui-check-md">
                                <input {{ readConfig('newsletter_subscribe') == 0 ? 'checked' : '' }}
                                    name="newsletter_subscribe" type="radio" value="0">
                                <i class="dark-white"></i>
                                Not Active
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            @endcan
            @can('custom_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'custom-css' ? 'active show' : '' }}" id="tabs-5"
                role="tabpanel" aria-labelledby="vert-tabs-5">
                <form action="{{ route('backend.admin.settings.website.custom.css.update') }}" method="post">
                    @csrf
                    <div class="col-md-12 d-flex justify-content-between">
                        <h5>
                            <i class="fas fa-code"></i>
                            &nbsp;&nbsp;Custom CSS
                        </h5>
                        <button type="submit" class="btn bg-gradient-primary">
                            <i class="fas fa-reply"></i>
                            &nbsp;Save Changes
                        </button>
                    </div>
                    <div class="col-md-12 mt-2">
                        <div class="form-group">
                            <textarea placeholder="" class="form-control" rows="17" name="custom_css" cols="50">{{ readConfig('custom_css') }}</textarea>
                        </div>
                    </div>
                </form>
            </div>
            @endcan
            @can('notification_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'notification-settings' ? 'active show' : '' }}"
                id="tabs-6" role="tabpanel" aria-labelledby="vert-tabs-6">
                <form action="{{ route('backend.admin.settings.website.notification.settings.update') }}"
                    method="post">
                    @csrf
                    <div class="col-md-12 d-flex justify-content-between">
                        <h5>
                            <i class="fas fa-envelope"></i>
                            &nbsp;&nbsp;Notification Settings
                        </h5>
                        <button type="submit" class="btn bg-gradient-primary">
                            <i class="fas fa-reply"></i>
                            &nbsp;Save Changes
                        </button>
                    </div>
                    <div class="p-a-md col-md-12">
                        <div class="form-group">
                            <label>Website Notification Email</label>
                            <input placeholder="Enter email" class="form-control" name="notify_email_address"
                                type="email" value="{{ readConfig('notify_email_address') }}">
                        </div>
                        <div class="form-group">
                            <label>Send me an email on new contact Messages : </label>
                            <div class="radio bg-white rounded pt-2 pl-2 border">
                                <label class="ui-check ui-check-md">
                                    <input {{ readConfig('notify_messages_status') == 1 ? 'checked' : '' }}
                                        name="notify_messages_status" type="radio" value="1">
                                    <i class="dark-white"></i>
                                    Yes
                                </label>
                                &nbsp; &nbsp;
                                <label class="ui-check ui-check-md">
                                    <input {{ readConfig('notify_messages_status') == 0 ? 'checked' : '' }}
                                        name="notify_messages_status" type="radio" value="0">
                                    <i class="dark-white"></i>
                                    No
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Send me an email on new Comments : </label>
                            <div class="radio bg-white rounded pt-2 pl-2 border">
                                <label class="ui-check ui-check-md">
                                    <input {{ readConfig('notify_comments_status') == 1 ? 'checked' : '' }}
                                        name="notify_comments_status" type="radio" value="1">
                                    <i class="dark-white"></i>
                                    Yes
                                </label>
                                &nbsp; &nbsp;
                                <label class="ui-check ui-check-md">
                                    <input {{ readConfig('notify_comments_status') == 0 ? 'checked' : '' }}
                                        name="notify_comments_status" type="radio" value="0">
                                    <i class="dark-white"></i>
                                    No
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            @endcan
            @can('website_status_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'website-status' ? 'active show' : '' }}"
                id="tabs-7" role="tabpanel" aria-labelledby="vert-tabs-7">
                <form action="{{ route('backend.admin.settings.website.status.update') }}" method="post">
                    @csrf
                    <div class="col-md-12 d-flex justify-content-between">
                        <h5>
                            <i class="fas fa-power-off"></i>
                            &nbsp;&nbsp;Website Status
                        </h5>
                        <button type="submit" class="btn bg-gradient-primary">
                            <i class="fas fa-reply"></i>
                            &nbsp;Save Changes
                        </button>
                    </div>
                    <div class="p-a-md col-md-12">
                        <div class="form-group">
                            <label>Website Status : </label>
                            <div class="radio bg-white rounded pt-2 pl-2 border">
                                <label class="ui-check ui-check-md">
                                    <input {{ readConfig('is_live') == 1 ? 'checked' : '' }} name="is_live"
                                        type="radio" value="1">
                                    <i class="dark-white"></i>
                                    Active
                                </label>
                                &nbsp; &nbsp;
                                <label class="ui-check ui-check-md">
                                    <input {{ readConfig('is_live') == 0 ? 'checked' : '' }} name="is_live"
                                        type="radio" value="0">
                                    <i class="dark-white"></i>
                                    Not Active
                                </label>
                            </div>
                        </div>

                        <div class="form-group {{ readConfig('is_live') == 1 ? 'd-none' : '' }}" id="close_msg_div">
                            <label>Close Message</label>
                            <textarea placeholder="Close Message" class="form-control" rows="4" name="close_msg" cols="50">Website under maintenance&lt;h1&gt;Comming SOON&lt;/h1&gt;</textarea>
                        </div>
                    </div>
                </form>
            </div>
            @endcan
            @can('invoice_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'invoice-settings' ? 'active show' : '' }}"
                id="tabs-8" role="tabpanel" aria-labelledby="vert-tabs-8">
                <form action="{{ route('backend.admin.settings.website.invoice.update') }}" method="post">
                    @csrf
                    <div class="col-md-12 d-flex justify-content-between">
                        <h5>
                            <i class="fas fa-file-invoice"></i>
                            &nbsp;&nbsp;Invoice Settings
                        </h5>
                        <button type="submit" class="btn bg-gradient-primary">
                            <i class="fas fa-reply"></i>
                            &nbsp;Save Changes
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Note to customer</label>
                        <input type="text" class="form-control" placeholder="Enter message for invoice" name="note_to_customer_invoice" value="{{ readConfig('note_to_customer_invoice') }}">
                    </div>
                    <div class="form-group d-flex align-items-center">
                        <label class="switch"><input type="hidden" name="is_show_logo_invoice" value="0">
                            <input onclick="updateCheckboxValue(this)" type="checkbox" {{ readConfig('is_show_logo_invoice') == 1 ? 'checked' : '' }} name="is_show_logo_invoice" id="is_show_logo_invoice" value="{{ readConfig('is_show_logo_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                        <label for="is_show_logo_invoice" class="mx-2">Logo</label>
                    </div>
                    <div class="form-group d-flex align-items-center">
                        <label class="switch"><input type="hidden" name="is_show_site_invoice" value="0">
                            <input onclick="updateCheckboxValue(this)" type="checkbox" {{ readConfig('is_show_site_invoice') == 1 ? 'checked' : '' }} name="is_show_site_invoice" id="is_show_site_invoice" value="{{ readConfig('is_show_site_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                        <label for="is_show_site_invoice" class="mx-2">Site Name</label>
                    </div>
                    <div class="form-group d-flex align-items-center">
                        <label class="switch"><input type="hidden" name="is_show_phone_invoice" value="0">
                            <input onclick="updateCheckboxValue(this)" type="checkbox" {{ readConfig('is_show_phone_invoice') == 1 ? 'checked' : '' }} name="is_show_phone_invoice" id="is_show_phone_invoice" value="{{ readConfig('is_show_phone_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>

                        <label for="is_show_phone_invoice" class="mx-2">Phone</label>
                    </div>
                    <div class="form-group d-flex align-items-center">
                        <label class="switch"><input type="hidden" name="is_show_email_invoice" value="0">
                            <input onclick="updateCheckboxValue(this)" type="checkbox" {{ readConfig('is_show_email_invoice') == 1 ? 'checked' : '' }} name="is_show_email_invoice" id="is_show_email_invoice" value="{{ readConfig('is_show_email_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                        <label for="is_show_email_invoice" class="mx-2">Email</label>
                    </div>
                    <div class="form-group d-flex align-items-center">
                        <label class="switch"><input type="hidden" name="is_show_address_invoice" value="0">
                            <input onclick="updateCheckboxValue(this)" type="checkbox" {{ readConfig('is_show_address_invoice') == 1 ? 'checked' : '' }} name="is_show_address_invoice" id="is_show_address_invoice" value="{{ readConfig('is_show_address_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                        <label for="is_show_address_invoice" class="mx-2">Address</label>
                    </div>
                    <div class="form-group d-flex align-items-center">
                        <label class="switch"><input type="hidden" name="is_show_customer_invoice" value="0">
                            <input onclick="updateCheckboxValue(this)" type="checkbox" {{ readConfig('is_show_customer_invoice') == 1 ? 'checked' : '' }} name="is_show_customer_invoice" id="is_show_customer_invoice" value="{{ readConfig('is_show_customer_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                        <label for="is_show_customer_invoice" class="mx-2">Customer</label>
                    </div>
                    <div class="form-group d-flex align-items-center">
                        <label class="switch"><input type="hidden" name="is_show_note_invoice" value="0">
                            <input onclick="updateCheckboxValue(this)" type="checkbox" {{ readConfig('is_show_note_invoice') == 1 ? 'checked' : '' }} name="is_show_note_invoice" id="is_show_note_invoice" value="{{ readConfig('is_show_note_invoice') == 1 ? 1 : '0' }}">
                            <span class="slider round"></span>
                        </label>
                        <label for="is_show_note_invoice" class="mx-2">Note to customer</label>
                    </div>
                    <div class="form-group">
                        <label class="">Pos Invoice Width</label>
                        <select name="receiptMaxwidth" class="form-control col-6">
                            <option value="300px" {{ readConfig('receiptMaxwidth') == '300px' ? 'selected' : '' }}>Small</option>
                            <option value="400px" {{ readConfig('receiptMaxwidth') == '400px' ? 'selected' : '' }}>Medium</option>
                            <option value="500px" {{ readConfig('receiptMaxwidth') == '500px' ? 'selected' : '' }}>Large</option>
                        </select>
                    </div>
                </form>
            </div>
            @endcan
            @can('website_settings')
            <div class="tab-pane fade {{ @$_GET['active-tab'] == 'printer-settings' ? 'active show' : '' }}" id="tabs-9"
                role="tabpanel" aria-labelledby="vert-tabs-9">

                <form action="{{ route('backend.admin.settings.website.printer.update') }}" method="post">
                    @csrf
                    <div class="col-md-12 d-flex justify-content-between align-items-center">
                        <h5>
                            <i class="fas fa-print"></i>
                            &nbsp;&nbsp;Printer Configuration
                        </h5>
                        <div>
                            <button type="button" class="btn btn-secondary mr-2" onclick="loadPrinters()">
                                <i class="fas fa-sync-alt"></i> Refresh List
                            </button>
                            <button type="submit" class="btn bg-gradient-primary">
                                <i class="fas fa-save"></i> Save Configuration
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-md-12 mt-4">
                        <div class="alert alert-light border shadow-sm">
                            <h6 class="text-primary"><i class="fas fa-info-circle mr-1"></i> How it works</h6>
                            <p class="mb-0 text-muted small">
                                Select the specific thermal printers for your Receipts and Barcode Tags. 
                                <br>If you leave these as <strong>System Default</strong>, the system will use your computer's default printer.
                                <br><strong>Ensure your printers are connected and turned on before refreshing the list.</strong>
                            </p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card shadow-none border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-receipt mr-2"></i>Sales Receipt Printer</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label class="text-muted small text-uppercase font-weight-bold">Select Printer</label>
                                            <select name="receipt_printer" id="receipt_printer_select" class="form-control custom-select">
                                                <option value="">System Default</option>
                                                @if(readConfig('receipt_printer'))
                                                    <option value="{{ readConfig('receipt_printer') }}" selected>{{ readConfig('receipt_printer') }} (Saved)</option>
                                                @endif
                                            </select>
                                            <small class="form-text text-muted">Used for printing sale invoices and refund receipts.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card shadow-none border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-tags mr-2"></i>Barcode Label Printer</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label class="text-muted small text-uppercase font-weight-bold">Select Printer</label>
                                            <select name="tag_printer" id="tag_printer_select" class="form-control custom-select">
                                                <option value="">System Default</option>
                                                @if(readConfig('tag_printer'))
                                                    <option value="{{ readConfig('tag_printer') }}" selected>{{ readConfig('tag_printer') }} (Saved)</option>
                                                @endif
                                            </select>
                                            <small class="form-text text-muted">Used for printing adhesive product labels and barcodes.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <span id="printer_status_msg" class="text-muted font-italic small">Checking for printers...</span>
                        </div>
                    </div>
                </form>
            </div>
            @endcan
        </div>
    </div>
</div>
</div>
@endsection

@push('script')
<script>
    $('input[type=radio][name=is_live]').on("change", function() {
        if (this.value == '0') {
            $("#close_msg_div").removeClass('d-none');
        } else {
            $("#close_msg_div").addClass('d-none');
        }
    });

    // Printer Discovery Logic
    function loadPrinters() {
        const statusMsg = document.getElementById('printer_status_msg');
        const receiptSelect = document.getElementById('receipt_printer_select');
        const tagSelect = document.getElementById('tag_printer_select');
        
        // Safer way to get config values using blade json directive
        // We use 'String' constructor to ensure we don't have nulls breaking things
        const currentReceipt = String(@json(readConfig('receipt_printer')) || "");
        const currentTag = String(@json(readConfig('tag_printer')) || "");

        console.log("Server Saved Config -> Receipt:", currentReceipt, "Tag:", currentTag);

        if (!statusMsg || !receiptSelect || !tagSelect) return;

        statusMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning for connected printers...';
        statusMsg.className = 'text-primary font-italic small';

        // Check if Electron API is available
        if (window.electron && window.electron.getPrinters) {
            window.electron.getPrinters().then(prList => {
                
                if (prList.length === 0) {
                     statusMsg.innerHTML = '<i class="fas fa-exclamation-triangle"></i> No printers found. Please check connections.';
                     statusMsg.className = 'text-warning font-weight-bold small';
                     return;
                }

                // Helper to populate select
                const populateSelect = (selectEl, currentVal, type) => {
                    selectEl.innerHTML = '<option value="">System Default</option>';
                    let foundCurrent = false;

                    prList.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.name;
                        // Show (Default) if it's the OS default
                        let label = p.name;
                        if (p.isDefault) label += ' (OS Default)';
                        
                        opt.textContent = label;
                        
                        // Loose comparison and trimming to catch subtle mismatches
                        if (p.name.trim() === currentVal.trim()) {
                            opt.selected = true;
                            foundCurrent = true;
                        }
                        selectEl.appendChild(opt);
                    });

                    // Critical: If the saved printer IS NOT in the list, add it back
                    if (currentVal && !foundCurrent) {
                        const opt = document.createElement('option');
                        opt.value = currentVal;
                        opt.textContent = currentVal + ' (Saved - Not Found)';
                        opt.selected = true;
                        opt.style.color = 'red';
                        selectEl.appendChild(opt);
                    }
                    
                    // Final safety check to ensure UI reflects value
                    selectEl.value = currentVal;
                };

                populateSelect(receiptSelect, currentReceipt, 'receipt');
                populateSelect(tagSelect, currentTag, 'tag');

                statusMsg.innerHTML = `<i class="fas fa-check-circle"></i> Successfully loaded ${prList.length} printers.`;
                statusMsg.className = 'text-success font-weight-bold small';

            }).catch(err => {
                console.error('Failed to load printers:', err);
                statusMsg.innerHTML = '<i class="fas fa-times-circle"></i> Failed to access printer service.';
                statusMsg.className = 'text-danger font-weight-bold small';
            });
        } else {
            statusMsg.innerHTML = '<i class="fas fa-desktop"></i> Desktop App required for printer scanning. Viewing saved settings only.';
            statusMsg.className = 'text-muted small';
        }
    }

    // Warning for PDF Printers
    function checkPrinterSelection(select) {
        const val = select.value.toLowerCase();
        if (val.includes('pdf') || val.includes('xps') || val.includes('one note')) {
             alert("⚠️ WARNING: You selected a PDF/Virtual Printer.\n\nIn POS Silent Mode, this may NOT open a 'Save As' dialog and printing might fail or hang.\n\nPlease select a physical Thermal Printer for best results.");
        }
    }
    
    document.getElementById('receipt_printer_select').addEventListener('change', function() { checkPrinterSelection(this); });
    document.getElementById('tag_printer_select').addEventListener('change', function() { checkPrinterSelection(this); });

    document.addEventListener('DOMContentLoaded', function() {
        // Initial load
        loadPrinters();
    });
</script>
@endpush