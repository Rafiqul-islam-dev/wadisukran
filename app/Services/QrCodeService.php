<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generateQrCodeWithInvoice($invoice)
    {
        $logoPath = public_path(company_setting()?->logo);
        $qrCode = QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            // ->merge($logoPath, 0.25, true)
            ->generate($invoice);

        $file_name = $invoice . '_' . rand(10000, 99999) . '.png';
        $relativePath = 'uploads/qr_codes/' . $file_name;
        Storage::disk('public')->put($relativePath, $qrCode);

        return $relativePath;
    }
}
