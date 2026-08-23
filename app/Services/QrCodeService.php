<?php

namespace App\Services;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\Image\EpsImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Log;

class QrCodeService
{
    /**
     * Generate QR Code image
     * 
     * @param string $content
     * @param int $size
     * @param string $format
     * @return string
     */
    public function generate(string $content, int $size = 300, string $format = 'png'): string
    {
        try {
            if ($format === 'svg') {
                return $this->generateSvg($content, $size);
            }
            
            return $this->generatePng($content, $size);
        } catch (\Exception $e) {
            Log::error('QR Code generation error', [
                'content' => $content,
                'size' => $size,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate PNG QR Code using GD with QR matrix
     */
    private function generatePng(string $content, int $size): string
    {
        // Get the QR matrix by using the encoder directly
        $qrCode = \BaconQrCode\Encoder\Encoder::encode(
            $content, 
            \BaconQrCode\Common\ErrorCorrectionLevel::L(), 
            'UTF-8'
        );
        $matrix = $qrCode->getMatrix();
        
        $width = $matrix->getWidth();
        $height = $matrix->getHeight();
        
        // Calculate module size
        $moduleSize = floor($size / max($width, $height));
        $actualSize = $moduleSize * max($width, $height);
        
        // Create image
        $image = imagecreatetruecolor($actualSize, $actualSize);
        
        // Set white background
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);
        
        // Draw QR code
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle(
                        $image,
                        $x * $moduleSize,
                        $y * $moduleSize,
                        ($x + 1) * $moduleSize - 1,
                        ($y + 1) * $moduleSize - 1,
                        $black
                    );
                }
            }
        }
        
        // Output to string
        ob_start();
        imagepng($image);
        $pngData = ob_get_clean();
        imagedestroy($image);
        
        return $pngData;
    }

    /**
     * Generate SVG QR Code
     */
    private function generateSvg(string $content, int $size): string
    {
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle($size, 0),
                new SvgImageBackEnd()
            )
        );

        return $writer->writeString($content, 'UTF-8');
    }
}
