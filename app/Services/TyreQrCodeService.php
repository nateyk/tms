<?php

namespace App\Services;

use App\Models\Tyre;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TyreQrCodeService
{
    public function generateForTyre(Tyre $tyre): string
    {
        $url = route('tyres.scan', ['tyre_code' => $tyre->tyre_code]);

        $relativePath = "tyres/qr/{$tyre->tyre_code}.svg";

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $disk->put(
            $relativePath,
            QrCode::format('svg')->size(220)->margin(1)->generate($url)
        );

        $tyre->update(['qr_code_path' => $relativePath]);

        return $relativePath;
    }

    public function publicUrl(Tyre $tyre): ?string
    {
        if (! $tyre->qr_code_path) {
            return null;
        }

        return asset('storage/'.$tyre->qr_code_path);
    }
}
