<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SiteSettingService
{
    public function update(
        SiteSetting $setting,
        ?UploadedFile $navbarLogo,
        ?UploadedFile $favicon,
        bool $resetNavbarLogo,
        bool $resetFavicon,
    ): SiteSetting {
        $oldPaths = [
            'navbar_logo_path' => $setting->navbar_logo_path,
            'favicon_path' => $setting->favicon_path,
        ];
        $newPaths = [];
        $changes = [];

        try {
            if ($navbarLogo) {
                $changes['navbar_logo_path'] = $this->store($navbarLogo, 'site-branding/logos');
                $newPaths[] = $changes['navbar_logo_path'];
            } elseif ($resetNavbarLogo) {
                $changes['navbar_logo_path'] = null;
            }

            if ($favicon) {
                $changes['favicon_path'] = $this->store($favicon, 'site-branding/favicons');
                $newPaths[] = $changes['favicon_path'];
            } elseif ($resetFavicon) {
                $changes['favicon_path'] = null;
            }

            DB::transaction(function () use ($setting, $changes): void {
                $setting->forceFill($changes);
                $setting->save();
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPaths);

            throw $exception;
        }

        foreach ($changes as $attribute => $newPath) {
            $oldPath = $oldPaths[$attribute];

            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return $setting->refresh();
    }

    private function store(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, 'public');

        if (! $path) {
            throw new RuntimeException('Aset branding gagal disimpan.');
        }

        return $path;
    }
}
