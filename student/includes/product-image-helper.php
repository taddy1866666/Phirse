<?php
/**
 * Product Image Helper Functions
 * Helper functions to handle multiple product images
 */

/**
 * Get all product images as an array
 * @param int $sellerId
 * @param string $orgName
 * @param string $rawCsv - Comma-separated image paths
 * @return array Array of valid image paths
 */
function getAllProductImages(int $sellerId, string $orgName, string $rawCsv): array {
    $parts = array_filter(array_map('trim', explode(',', $rawCsv)));
    
    if (empty($parts)) {
        return ["images/default-product.svg"];
    }
    
    $orgFolder = preg_replace('/[^A-Za-z0-9]/', '', $orgName);
    $validImages = [];
    
    foreach ($parts as $part) {
        $filename = basename($part);
        
        if (empty($filename)) continue;
        
        $paths = [
            __DIR__ . "/../uploads/products/{$filename}",
            __DIR__ . "/../seller/uploads/{$orgFolder}/{$filename}",
            __DIR__ . "/../seller/uploads/{$sellerId}/{$filename}",
            __DIR__ . "/../seller/uploads/{$filename}",
            __DIR__ . "/../uploads/{$filename}",
            __DIR__ . "/uploads/{$filename}",
            __DIR__ . "/images/{$filename}",
            $part
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                if (strpos($path, __DIR__ . '/..') === 0) {
                    $relativePath = str_replace(__DIR__ . '/..', '..', $path);
                    $validImages[] = str_replace('\\', '/', $relativePath);
                    break;
                } elseif (strpos($path, __DIR__) === 0) {
                    $relativePath = str_replace(__DIR__, '.', $path);
                    $validImages[] = str_replace('\\', '/', $relativePath);
                    break;
                } else {
                    $validImages[] = $path;
                    break;
                }
            }
        }
    }
    
    // If no valid images found, return default
    if (empty($validImages)) {
        return ["images/default-product.svg"];
    }
    
    return $validImages;
}

/**
 * Get the first product image
 * @param int $sellerId
 * @param string $orgName  
 * @param string $rawCsv
 * @return string First valid image path
 */
function getFirstProductImage(int $sellerId, string $orgName, string $rawCsv): string {
    $images = getAllProductImages($sellerId, $orgName, $rawCsv);
    return $images[0];
}
?>
