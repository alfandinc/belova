<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Typography\FontFactory;
use Illuminate\Support\Facades\Log;

class BirthdayImageService
{
    public function generateBirthdayImage($name, $age, $prefix = '', $klinikId = null)
    {
        // Set template based on klinik_id
        $templatePath = $this->getTemplateByKlinik($klinikId);
        
        try {
            // Create image from template
            $img = Image::read(public_path($templatePath));
            
            // Format name with prefix if available
            $displayName = $prefix ? "$prefix $name" : $name;

            // Choose text color based on klinik id (white for klinik 1)
            $textColor = ($klinikId == 1) ? '#ffffff' : '#6c3483';
            
            // Get image dimensions
            $width = $img->width();
            $height = $img->height();
            
            // Text font settings via callable functions
            // Move text more to the left (subtract 200px from center)
            $x = $width / 2 - 200;
            // Move text further down by increasing Y values
            $nameY = $height / 2 - 15;
            $ageY = $height / 2 + 55;
            $dateY = $height - 45;
            // Name text
            $img->text($displayName, $x, $nameY, function ($font) use ($textColor) {
                $font->filename(public_path('fonts/Poppins-Bold.ttf'));
                $font->size(60);
                $font->color($textColor);
                $font->align('center');
                $font->valign('middle');
            });
            // Age text
            $img->text("$age tahun", $x, $ageY, function ($font) use ($textColor) {
                $font->filename(public_path('fonts/Poppins-Regular.ttf'));
                $font->size(40);
                $font->color($textColor);
                $font->align('center');
                $font->valign('middle');
            });
            // Date text
            $img->text(now()->format('d F Y'), $x, $dateY, function ($font) use ($textColor) {
                $font->filename(public_path('fonts/Poppins-Italic.ttf'));
                $font->size(20);
                $font->color($textColor);
                $font->align('center');
                $font->valign('bottom');
            });
            
            // Generate unique filename
            $fileName = 'birthday_' . strtolower(str_replace(' ', '_', $name)) . '_' . uniqid() . '.jpg';
            $path = 'birthday_cards/' . $fileName;
            
            // Create directory if it doesn't exist
            if (!Storage::disk('public')->exists('birthday_cards')) {
                Storage::disk('public')->makeDirectory('birthday_cards');
            }
            
            // Save image to storage
            Storage::disk('public')->put($path, $img->toJpeg(90));
            
            return $path;
        } 
        catch (\Throwable $e) {
            Log::error("Birthday image generation error: " . $e->getMessage());
            return null;
        }
    }
    
    private function getTemplateByKlinik($klinikId)
    {
        // Different templates based on klinik_id
        switch ($klinikId) {
            case 1:
                return 'img/templates/birthday_premierebelova.jpg';
            case 2:
                return 'img/templates/birthday_belovaskin.jpg';
            default:
                return 'img/templates/birthday_default.jpg';
        }
    }
}