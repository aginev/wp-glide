<?php
use WpGlide\WpGlide;

if (!function_exists('wp_glide')) {
    /**
     * WpGlide instance
     *
     * @return WpGlide
     */
    function wp_glide(): WpGlide
    {
        return WpGlide::getInstance();
    }
}

if (!function_exists('wp_glide_image')) {
    /**
     * WpGlide image URL
     *
     * @param string|int $url String URL or integer attachment ID
     * @param string     $slug
     *
     * @return string
     */
    function wp_glide_image($url, string $slug): string
    {
        return WpGlide::getInstance()->imageUrl($url, $slug);
    }
}

if (!function_exists('wp_glide_base64')) {
    /**
     * WpGlide image URL
     *
     * @param string|int $url String URL or integer attachment ID
     * @param            $slug
     *
     * @return string
     */
    function wp_glide_base64($url, string $slug): string
    {
        return WpGlide::getInstance()->imageBase64($url, $slug);
    }
}