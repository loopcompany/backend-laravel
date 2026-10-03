<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * آخرین نسخه و حداقل نسخه‌ی پشتیبانی‌شده‌ی اپلیکیشن‌ها برای هر پلتفرم.
 */
class AppVersion extends Model
{
    public const APPS = [
        'user' => 'اپ کاربر / سازمانی',
        'technician' => 'اپ تکنسین',
    ];

    public const PLATFORMS = [
        'android' => 'Android',
        'ios' => 'iOS',
        'web' => 'Web',
    ];

    protected $fillable = ['app', 'platform', 'latest_version', 'min_supported_version', 'update_url', 'release_notes'];

    /**
     * @return array{latest_version: string, min_supported_version: ?string, update_available: bool, force: bool, update_url: ?string, release_notes: ?string}
     */
    public function checkFor(?string $currentVersion): array
    {
        $current = self::normalize($currentVersion);

        return [
            'latest_version' => $this->latest_version,
            'min_supported_version' => $this->min_supported_version,
            'update_available' => $current !== null && version_compare($current, self::normalize($this->latest_version), '<'),
            'force' => $current !== null
                && $this->min_supported_version !== null
                && version_compare($current, self::normalize($this->min_supported_version), '<'),
            'update_url' => $this->update_url,
            'release_notes' => $this->release_notes,
        ];
    }

    public static function normalize(?string $version): ?string
    {
        if ($version === null || !preg_match('/^v?(\d+(?:\.\d+){0,3})/i', trim($version), $m)) {
            return null;
        }

        return $m[1];
    }
}
