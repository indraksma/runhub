<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    public const DEFAULT_NAVBAR_LOGO = 'kdscr.png';

    public const DEFAULT_FAVICON = 'favicon_kds.png';

    protected $fillable = ['navbar_logo_path', 'favicon_path'];

    public static function current(): self
    {
        return static::query()->firstOrNew(['id' => 1]);
    }

    public function navbarLogoUrl(): string
    {
        return $this->navbar_logo_path
            ? Storage::disk('public')->url($this->navbar_logo_path)
            : asset(self::DEFAULT_NAVBAR_LOGO);
    }

    public function faviconUrl(): string
    {
        return $this->favicon_path
            ? Storage::disk('public')->url($this->favicon_path)
            : asset(self::DEFAULT_FAVICON);
    }
}
