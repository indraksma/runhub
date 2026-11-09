<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_uses_default_branding_when_settings_have_not_been_saved(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(asset(SiteSetting::DEFAULT_NAVBAR_LOGO), false)
            ->assertSee(asset(SiteSetting::DEFAULT_FAVICON), false)
            ->assertSee('alt="Logo situs"', false);

        $this->assertDatabaseCount('site_settings', 0);
    }

    public function test_admin_can_upload_branding_and_it_is_rendered_across_the_site(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'navbar_logo' => UploadedFile::fake()->image('logo.png', 600, 200),
            'favicon' => UploadedFile::fake()->image('favicon.png', 512, 512),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $setting = SiteSetting::query()->sole();
        Storage::disk('public')->assertExists($setting->navbar_logo_path);
        Storage::disk('public')->assertExists($setting->favicon_path);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($setting->navbarLogoUrl(), false)
            ->assertSee($setting->faviconUrl(), false);

        $this->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Branding')
            ->assertSee($setting->navbarLogoUrl(), false)
            ->assertSee($setting->faviconUrl(), false);
    }

    public function test_replacing_and_resetting_branding_removes_superseded_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        Storage::disk('public')->put('site-branding/logos/old.png', 'old logo');
        Storage::disk('public')->put('site-branding/favicons/old.png', 'old favicon');
        $setting = SiteSetting::create([
            'navbar_logo_path' => 'site-branding/logos/old.png',
            'favicon_path' => 'site-branding/favicons/old.png',
        ]);

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'navbar_logo' => UploadedFile::fake()->image('replacement.png', 600, 200),
            'favicon' => UploadedFile::fake()->image('replacement-favicon.png', 512, 512),
        ])->assertSessionHasNoErrors();

        $setting->refresh();
        $replacementLogo = $setting->navbar_logo_path;
        $replacementFavicon = $setting->favicon_path;
        Storage::disk('public')->assertMissing('site-branding/logos/old.png');
        Storage::disk('public')->assertMissing('site-branding/favicons/old.png');
        Storage::disk('public')->assertExists($replacementLogo);
        Storage::disk('public')->assertExists($replacementFavicon);

        $this->put(route('admin.settings.update'), [
            'reset_navbar_logo' => '1',
            'reset_favicon' => '1',
        ])->assertSessionHasNoErrors();

        $setting->refresh();
        $this->assertNull($setting->navbar_logo_path);
        $this->assertNull($setting->favicon_path);
        Storage::disk('public')->assertMissing($replacementLogo);
        Storage::disk('public')->assertMissing($replacementFavicon);

        $this->get(route('home'))
            ->assertSee(asset(SiteSetting::DEFAULT_NAVBAR_LOGO), false)
            ->assertSee(asset(SiteSetting::DEFAULT_FAVICON), false);
    }

    public function test_branding_upload_validation_rejects_invalid_files_and_conflicting_actions(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'navbar_logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
            'favicon' => UploadedFile::fake()->image('favicon.jpg'),
        ])->assertSessionHasErrors(['navbar_logo', 'favicon']);

        $this->put(route('admin.settings.update'), [
            'navbar_logo' => UploadedFile::fake()->image('large-logo.png')->size(4097),
            'favicon' => UploadedFile::fake()->image('wide-favicon.png', 256, 128),
        ])->assertSessionHasErrors(['navbar_logo', 'favicon']);

        $this->put(route('admin.settings.update'), [
            'navbar_logo' => UploadedFile::fake()->image('logo.png'),
            'reset_navbar_logo' => '1',
            'favicon' => UploadedFile::fake()->image('favicon.png'),
            'reset_favicon' => '1',
        ])->assertSessionHasErrors([
            'navbar_logo',
            'reset_navbar_logo',
            'favicon',
            'reset_favicon',
        ]);
    }

    public function test_only_admins_can_access_branding_settings(): void
    {
        $finance = User::factory()->create(['role' => 'finance']);

        $this->actingAs($finance)->get(route('admin.payments'))
            ->assertOk()
            ->assertDontSee(route('admin.settings.edit'), false);

        $this->get(route('admin.settings.edit'))->assertForbidden();
        $this->put(route('admin.settings.update'))->assertForbidden();

        $this->post(route('logout'));
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
    }
}
