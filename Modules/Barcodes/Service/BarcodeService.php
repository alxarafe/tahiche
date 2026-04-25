<?php

declare(strict_types=1);

namespace Modules\Barcodes\Service;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class BarcodeService
{
    /**
     * Valida el dígito de control EAN-13.
     */
    public function validateEan13(string $code): bool
    {
        if (strlen($code) !== 13 || !ctype_digit($code)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $code[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit === (int) $code[12];
    }

    /**
     * Genera un código QR en formato data URI (base64).
     */
    public function generateQrCode(string $data): string
    {
        $options = new QROptions([
            'version'      => 5,
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_L,
            'scale'        => 5,
            'imageBase64'  => true,
        ]);

        $qrcode = new QRCode($options);
        return $qrcode->render($data);
    }
}
