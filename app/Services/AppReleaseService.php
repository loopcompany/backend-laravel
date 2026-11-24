<?php
namespace App\Services;

use App\Models\AppRelease;
use Illuminate\Validation\ValidationException;

class AppReleaseService
{
    public function inspect(string $artifact, bool $hash = true): array
    {
        $name = basename(str_replace('\\', '/', $artifact));
        if ($name !== $artifact) $this->invalid();
        if (preg_match('/^(LoopUser|LoopTech)-(\d+\.\d+\.\d+)\.apk$/D', $name, $m)) {
            $app = $m[1] === 'LoopUser' ? 'customer' : 'technician'; $platform = 'android'; $version = $m[2]; $variant = 'universal'; $folder = 'android';
        } elseif (preg_match('/^Loop-(\d+\.\d+\.\d+)-Windows-(10-11|7-8)-(32|64)bit-Setup\.exe$/D', $name, $m)) {
            $app = 'desktop'; $platform = 'windows'; $version = $m[1]; $variant = ($m[2] === '10-11' ? 'win10-' : 'win7-').($m[3] === '64' ? 'x64' : 'ia32'); $folder = 'windows';
        } else $this->invalid();
        $base = realpath(public_path('downloads/'.$folder));
        $file = $base ? realpath($base.DIRECTORY_SEPARATOR.$name) : false;
        if (! $file || dirname($file) !== $base || ! is_file($file) || filesize($file) < 1024) $this->invalid();
        return ['app' => $app, 'platform' => $platform, 'version' => $version, 'variant' => $variant, 'bytes' => filesize($file), 'sha256' => $hash ? hash_file('sha256', $file) : null];
    }

    public function check(array $input): array
    {
        $variant = $input['platform'] === 'android' ? 'universal' : ($input['variant'] ?? 'win10-x64');
        $release = $this->latestDownloads($input['platform'])[$input['app'].':'.$variant] ?? null;
        $available = $release && ($input['platform'] === 'android'
            ? $release->version_code > (int) ($input['version_code'] ?? 0)
            : version_compare($release->version, $input['version'] ?? '0.0.0', '>'));
        $folder = $input['platform'] === 'android' ? 'android' : 'windows';
        return ['available' => (bool) $available, 'release' => $release ? [
            'id' => $release->id, 'app' => $release->app, 'platform' => $release->platform, 'variant' => $release->variant,
            'version' => $release->version, 'version_code' => $release->version_code, 'bytes' => $release->bytes,
            'sha256' => $release->sha256, 'notes' => $release->notes,
            'url' => rtrim(config('app.url'), '/').'/downloads/'.$folder.'/'.$release->artifact,
        ] : null, 'realtime' => [
            'enabled' => (bool) config('realtime.enabled'), 'key' => config('broadcasting.connections.reverb.key'),
            'host' => config('realtime.host'), 'port' => config('realtime.port'), 'scheme' => config('realtime.scheme'), 'channels' => ['app-updates'],
        ]];
    }

    /** @return array<string, AppRelease> */
    public function latestDownloads(string $platform): array
    {
        return AppRelease::where('platform', $platform)->where('is_published', true)->get()
            ->sort(fn (AppRelease $a, AppRelease $b) => $platform === 'android'
                ? $a->version_code <=> $b->version_code
                : version_compare($a->version, $b->version))
            ->keyBy(fn (AppRelease $release) => $release->app.':'.$release->variant)
            ->all();
    }

    public function artifacts(): array
    {
        $items = [];
        foreach (['android/*.apk', 'windows/*.exe'] as $pattern) foreach (glob(public_path('downloads/'.$pattern)) ?: [] as $file) {
            try { $name = basename($file); $this->inspect($name, false); $items[$name] = $name; } catch (ValidationException) { }
        }
        return $items;
    }

    private function invalid(): never { throw ValidationException::withMessages(['artifact' => 'فایل نصب معتبر باید قبلاً در پوشهٔ دانلودهای همین سرور قرار گرفته باشد.']); }
}
