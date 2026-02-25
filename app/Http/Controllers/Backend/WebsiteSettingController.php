<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Rules\ValidImageType;
use App\Http\Controllers\Controller;
use App\Trait\FileHandler;

class WebsiteSettingController extends Controller
{
    public $fileHandler;

    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
    }

    // ─── Main Page ─────────────────────────────────────────────────────────────

    public function websiteGeneral(Request $request)
    {
        $activeTab = $request->query('active-tab', 'website-info'); // Default to first tab
        return view('backend.settings.website-settings.general', compact('activeTab'));
    }

    // ─── Shared helper ─────────────────────────────────────────────────────────

    /**
     * Persist all request fields (except _token) via writeConfig, then respond.
     * writeConfig() already handles cache-busting — no Artisan::call needed.
     */
    private function saveTab(Request $request, string $tab, ?string $message = null): JsonResponse|RedirectResponse
    {
        foreach ($request->except('_token') as $key => $value) {
            writeConfig($key, $value);
        }

        $msg = $message ?? 'Settings updated successfully';

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return to_route('backend.admin.settings.website.general', ['active-tab' => $tab])
            ->with('success', $msg);
    }

    // ─── Update methods ────────────────────────────────────────────────────────

    public function websiteInfoUpdate(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'site_name' => 'required',
            'site_url'  => 'nullable|url',
        ]);
        return $this->saveTab($request, 'website-info');
    }

    public function websiteContactsUpdate(Request $request): JsonResponse|RedirectResponse
    {
        return $this->saveTab($request, 'contacts');
    }

    public function websiteSocialLinkUpdate(Request $request): JsonResponse|RedirectResponse
    {
        return $this->saveTab($request, 'social-links');
    }

    public function websiteCustomCssUpdate(Request $request): JsonResponse|RedirectResponse
    {
        return $this->saveTab($request, 'custom-css');
    }

    public function websiteNotificationSettingsUpdate(Request $request): JsonResponse|RedirectResponse
    {
        return $this->saveTab($request, 'notification-settings');
    }

    public function websiteStatusUpdate(Request $request): JsonResponse|RedirectResponse
    {
        return $this->saveTab($request, 'website-status');
    }

    public function websiteInvoiceUpdate(Request $request): JsonResponse|RedirectResponse
    {
        return $this->saveTab($request, 'invoice-settings');
    }

    public function websiteTaxUpdate(Request $request): JsonResponse|RedirectResponse
    {
        return $this->saveTab($request, 'tax-settings', 'Tax & FBR settings updated successfully');
    }

    /**
     * Printer: explicit field list so empty values are captured correctly.
     * (Stays a traditional POST — no AJAX needed for this one.)
     */
    public function websitePrinterUpdate(Request $request): JsonResponse|RedirectResponse
    {
        $data = [
            'receipt_printer' => $request->input('receipt_printer'),
            'tag_printer'     => $request->input('tag_printer'),
        ];

        foreach ($data as $key => $value) {
            writeConfig($key, $value);
        }

        $msg = 'Printer settings updated.';
        if ($data['receipt_printer']) $msg .= ' Receipt: ' . $data['receipt_printer'] . '.';
        if ($data['tag_printer'])     $msg .= ' Tag: '     . $data['tag_printer']     . '.';

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return to_route('backend.admin.settings.website.general', ['active-tab' => 'printer-settings'])
            ->with('success', $msg);
    }

    /**
     * Style Settings: handles file uploads — must stay as multipart POST.
     * No AJAX (browser can't AJAX file uploads without FormData).
     */
    public function websiteStyleSettingsUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'site_logo'         => ['file', new ValidImageType],
            'favicon_icon'      => ['file', new ValidImageType],
            'favicon_icon_apple'=> ['file', new ValidImageType],
        ]);

        writeConfig('newsletter_subscribe', $request->newsletter_subscribe);

        if ($request->hasFile('site_logo')) {
            $this->fileHandler->securePublicUnlink(readConfig('site_logo'));
            writeConfig('site_logo', $this->fileHandler->uploadToPublic($request->file('site_logo'), '/assets/images/logo'));
        }
        if ($request->hasFile('favicon_icon')) {
            $this->fileHandler->securePublicUnlink(readConfig('favicon_icon'));
            writeConfig('favicon_icon', $this->fileHandler->uploadToPublic($request->file('favicon_icon'), '/assets/images/logo'));
        }
        if ($request->hasFile('favicon_icon_apple')) {
            $this->fileHandler->securePublicUnlink(readConfig('favicon_icon_apple'));
            writeConfig('favicon_icon_apple', $this->fileHandler->uploadToPublic($request->file('favicon_icon_apple'), '/assets/images/logo'));
        }

        return to_route('backend.admin.settings.website.general', ['active-tab' => 'style-settings'])
            ->with('success', 'Branding & visuals updated successfully');
    }

    // ─── API: Electron printer discovery ───────────────────────────────────────

    /**
     * API Endpoint for Electron to fetch remote printer configurations.
     */
    public function getPrinterSettings(): JsonResponse
    {
        return response()->json([
            'receipt_printer' => readConfig('receipt_printer'),
            'tag_printer'     => readConfig('tag_printer'),
            'receipt_cpl'     => readConfig('receipt_cpl', 42),
            'currency_symbol' => readConfig('currency_symbol', 'Rs.'),
            'site_name'       => readConfig('site_name'),
            'address'         => readConfig('address'),
        ]);
    }
}
