<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingRequest;
use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'siteSetting' => SiteSetting::current(),
        ]);
    }

    public function update(UpdateSiteSettingRequest $request, SiteSettingService $service): RedirectResponse
    {
        $service->update(
            SiteSetting::current(),
            $request->file('navbar_logo'),
            $request->file('favicon'),
            $request->boolean('reset_navbar_logo'),
            $request->boolean('reset_favicon'),
        );

        return back()->with('success', 'Pengaturan branding diperbarui.');
    }
}
