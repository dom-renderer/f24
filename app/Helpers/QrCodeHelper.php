<?php

namespace App\Helpers;

use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class QrCodeHelper
{
    /**
     * Generate a QR code with text below it and save to storage.
     *
     * @param string $upiHandle The text to encode in the QR code and display below it.
     * @param string $filename The filename to save the image as (e.g., 'store_123.png').
     * @return bool|string Returns the relative path on success, or false on failure.
     */
    public static function generateQrCode($upiHandle, $filename)
    {
        try {
            if (empty($upiHandle)) {
                return false;
            }

            $upiUrl = "upi://pay?pa={$upiHandle}&pn=Farki&cu=INR";

            // Ensure directory exists in storage/app/public/qr-codes
            $directoryName = 'public/qr-codes'; 
            $absoluteDirectoryPath = storage_path('app/' . $directoryName);

            if (!file_exists($absoluteDirectoryPath)) {
                mkdir($absoluteDirectoryPath, 0755, true);
            }

            // Generate QR Code Matrix using BaconQrCode Encoder directly
            // This avoids the dependency on imagick which simple-qrcode v4 seems to enforce for PNG
            $encoder = new Encoder();
            $qrCode = Encoder::encode($upiUrl, ErrorCorrectionLevel::L(), 'UTF-8');
            
            $matrix = $qrCode->getMatrix();
            $width = $matrix->getWidth();
            $height = $matrix->getHeight();

            // Calculate pixel size for a target size of roughly 300px
            $targetSize = 300;
            $pixelSize = intval($targetSize / $width);
            $realQrSize = $pixelSize * $width;

            // Create GD Image for the QR code
            $qrImg = Image::canvas($realQrSize, $realQrSize, '#ffffff');

            // Draw Matrix
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    if ($matrix->get($x, $y) === 1) {
                        // Draw black square
                        $qrImg->rectangle(
                            $x * $pixelSize, 
                            $y * $pixelSize, 
                            ($x + 1) * $pixelSize, 
                            ($y + 1) * $pixelSize, 
                            function ($draw) {
                                $draw->background('#000000');
                            }
                        );
                    }
                }
            }

            // Create Final Canvas for Image + Text
            $canvasWidth = $realQrSize + 20; // Add some padding
            $canvasHeight = $realQrSize + 50; // Add space for text
            $img = Image::canvas($canvasWidth, $canvasHeight, '#ffffff');

            // Insert QR Code into Canvas
            $img->insert($qrImg, 'top-center', 0, 10);

            // Add Text below QR Code
            $img->text($upiHandle, $canvasWidth / 2, $realQrSize + 25, function ($font) {
                $font->file(storage_path('fonts/Roboto-Regular.ttf'));
                $font->size(24); // Internal GD font size (1-5)
                $font->color('#000000');
                $font->align('center');
                $font->valign('top');
            });

            // Save Image
            $savePath = $absoluteDirectoryPath . '/' . $filename;
            $img->save($savePath);

            return $directoryName . '/' . $filename;

        } catch (\Exception $e) {
            Log::error("QR Code Generation Error: " . $e->getMessage());
            return false;
        }
    }
}
