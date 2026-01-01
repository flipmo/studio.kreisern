<?php
/**
 * Image Helper Functions
 * Handles image resizing and compression
 */

/**
 * Resize and compress an image
 * 
 * @param string $source Source image path
 * @param string $destination Destination image path
 * @param int $maxWidth Maximum width in pixels
 * @param int $maxHeight Maximum height in pixels
 * @param int $quality JPEG quality (1-100, default 85)
 * @return bool Success status
 */
function resizeImage($source, $destination, $maxWidth, $maxHeight, $quality = 85) {
    // Get image info
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        return false;
    }
    
    list($origWidth, $origHeight, $imageType) = $imageInfo;
    
    // Calculate new dimensions maintaining aspect ratio
    $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
    
    // Don't upscale if image is smaller than max dimensions
    if ($ratio > 1) {
        $ratio = 1;
    }
    
    $newWidth = (int)($origWidth * $ratio);
    $newHeight = (int)($origHeight * $ratio);
    
    // Create source image resource based on type
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) {
        return false;
    }
    
    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize
    imagecopyresampled(
        $newImage, $sourceImage,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $origWidth, $origHeight
    );
    
    // Create destination directory if it doesn't exist
    $destDir = dirname($destination);
    if (!file_exists($destDir)) {
        mkdir($destDir, 0755, true);
    }
    
    // Save based on type
    $success = false;
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($newImage, $destination, $quality);
            break;
        case IMAGETYPE_PNG:
            // PNG quality is 0-9 (compression level), convert from percentage
            $pngQuality = (int)(9 - ($quality / 100 * 9));
            $success = imagepng($newImage, $destination, $pngQuality);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($newImage, $destination);
            break;
        case IMAGETYPE_WEBP:
            $success = imagewebp($newImage, $destination, $quality);
            break;
    }
    
    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    return $success;
}

/**
 * Get image extension from file
 * 
 * @param string $filename Original filename
 * @return string Extension (jpg, png, gif, webp)
 */
function getImageExtension($filename) {
    $imageInfo = getimagesize($filename);
    if (!$imageInfo) {
        return 'jpg'; // default
    }
    
    switch ($imageInfo[2]) {
        case IMAGETYPE_JPEG:
            return 'jpg';
        case IMAGETYPE_PNG:
            return 'png';
        case IMAGETYPE_GIF:
            return 'gif';
        case IMAGETYPE_WEBP:
            return 'webp';
        default:
            return 'jpg';
    }
}
?>