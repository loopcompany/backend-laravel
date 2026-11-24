<?php

namespace App\Services;

use App\Models\Technician;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TechnicianIdentityService
{
    public function issue(Technician $technician, bool $regenerate = false): Technician
    {
        return DB::transaction(function () use ($technician, $regenerate) {
            $record = Technician::lockForUpdate()->findOrFail($technician->id);
            abort_unless($this->eligible($record), 422, 'ابتدا تکنسین تأیید و کد پرسنلی صادر شود.');
            if (! $record->identity_badge_uuid || $regenerate) {
                $record->forceFill(['identity_badge_uuid' => (string) Str::uuid()])->save();
            }
            return $record;
        });
    }

    public function verify(string $payload): ?array
    {
        $uuid = trim($payload);
        if (! Str::isUuid($uuid)) {
            $url = parse_url($uuid);
            $host = parse_url(config('app.url'), PHP_URL_HOST);
            if (! is_array($url) || ($url['scheme'] ?? '') !== 'https' || ($url['host'] ?? '') !== $host
                || ! preg_match('~^/technician/identity/([a-fA-F0-9-]{36})$~D', $url['path'] ?? '', $match)) {
                return null;
            }
            $uuid = $match[1];
        }
        $technician = Technician::where('identity_badge_uuid', $uuid)->first();
        if (! $technician || ! $this->eligible($technician)) {
            return null;
        }
        return ['verified' => true, 'name' => $technician->name, 'personnel_code' => str_replace(['-', '.'], '', $technician->referral_code), 'technician_type' => $technician->technician_type];
    }

    public function card(Technician $technician): string
    {
        $technician = $this->issue($technician);
        $url = rtrim(config('app.url'), '/').'/technician/identity/'.$technician->identity_badge_uuid;
        $qr = app(QrCodeService::class)->generate($url, 380, 'svg');
        $qrData = 'data:image/svg+xml;base64,'.base64_encode($qr);
        $logoFile = public_path('assets/images/logo.png');
        $logo = is_file($logoFile) ? '<image href="data:image/png;base64,'.base64_encode(file_get_contents($logoFile)).'" x="235" y="22" width="130" height="58" preserveAspectRatio="xMidYMid meet"/>' : '';
        $name = htmlspecialchars($technician->name, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $code = htmlspecialchars(str_replace(['-', '.'], '', $technician->referral_code), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="760" viewBox="0 0 600 760"><rect x="4" y="4" width="592" height="752" rx="32" fill="#0c2037" stroke="#ddb94f" stroke-width="4"/>'.$logo.'<rect x="80" y="104" width="440" height="440" rx="20" fill="white"/><image href="'.$qrData.'" x="110" y="134" width="380" height="380"/><text x="300" y="602" text-anchor="middle" direction="rtl" fill="white" font-family="Tahoma,Arial" font-size="28">'.$name.'</text><text x="300" y="648" text-anchor="middle" fill="#f4d368" font-family="Arial" font-size="25">'.$code.'</text><text x="300" y="710" text-anchor="middle" direction="rtl" fill="#b6c8dd" font-family="Tahoma,Arial" font-size="18">احراز هویت تکنسین لوپ</text></svg>';
    }

    private function eligible(Technician $record): bool
    {
        return $record->isApproved() && $record->has_access && ! $record->suspended_at && RegistrationCodeService::isIssued($record->referral_code);
    }
}
