<?php
/**
 * Featured Products Cache Helper
 * Provides caching functionality for featured products to improve performance
 */

class FeaturedProductsCache {
    private $cache_dir;
    private $cache_time = 300; // 5 minutes cache
    
    public function __construct($cache_dir = 'cache/featured_products/') {
        $this->cache_dir = $cache_dir;
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Generate cache key based on parameters
     */
    private function getCacheKey($page, $lang) {
        return "featured_products_page_{$page}_lang_{$lang}.json";
    }
    
    /**
     * Get cached data
     */
    public function get($page, $lang) {
        $cache_file = $this->cache_dir . $this->getCacheKey($page, $lang);
        
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $this->cache_time) {
            $cached_data = file_get_contents($cache_file);
            return json_decode($cached_data, true);
        }
        
        return null;
    }
    
    /**
     * Set cache data
     */
    public function set($page, $lang, $data) {
        $cache_file = $this->cache_dir . $this->getCacheKey($page, $lang);
        file_put_contents($cache_file, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cache_dir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * Clear cache for specific page and language
     */
    public function clearPage($page, $lang) {
        $cache_file = $this->cache_dir . $this->getCacheKey($page, $lang);
        if (file_exists($cache_file)) {
            unlink($cache_file);
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $files = glob($this->cache_dir . '*.json');
        $total_size = 0;
        $oldest_file = null;
        $newest_file = null;
        
        foreach ($files as $file) {
            $size = filesize($file);
            $mtime = filemtime($file);
            $total_size += $size;
            
            if (!$oldest_file || $mtime < filemtime($oldest_file)) {
                $oldest_file = $file;
            }
            if (!$newest_file || $mtime > filemtime($newest_file)) {
                $newest_file = $file;
            }
        }
        
        return [
            'total_files' => count($files),
            'total_size' => $total_size,
            'oldest_file' => $oldest_file ? filemtime($oldest_file) : null,
            'newest_file' => $newest_file ? filemtime($newest_file) : null
        ];
    }
    
    /**
     * Clean expired cache files
     */
    public function cleanExpired() {
        $files = glob($this->cache_dir . '*.json');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if ((time() - filemtime($file)) > $this->cache_time) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}
?>