<?php

namespace WpGlide;

use League\Glide\ServerFactory;

/**
 * WordPress Glide wrapper
 *
 * @package WP_Webpack
 */
class WpGlide
{
    /**
     * @var WpGlide
     */
    protected static $instance = null;

    /**
     * Server config
     *
     * @var array
     */
    private $config = [];

    /**
     * Base URL for generated Glide images
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * List of all sizes defined
     *
     * @var array
     */
    protected $sizes = [];

    /**
     * Upload path
     *
     * @var string
     */
    protected $uploadsPath;

    /**
     * Cache path
     *
     * @var string
     */
    protected $cachePath;

    /**
     * Disable creating instances
     */
    protected function __construct()
    {
        //
    }

    /**
     * Disable object clone
     */
    protected function __clone()
    {
        //
    }

    /**
     * Get instance
     *
     * @return $this
     */
    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Init rewrites
     *
     * @param array       $config
     * @param string      $baseUrl
     * @param string|null $uploadsPath
     * @param string|null $cachePath
     */
    public function init(array $config = [],
                         string $baseUrl = 'img/',
                         string $uploadsPath = null,
                         string $cachePath = null
    )
    {
        $this->baseUrl = sprintf('%s/', ltrim(rtrim($baseUrl, '/'), '/'));
        $this->uploadsPath = $uploadsPath ?? (WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads');
        $this->cachePath = $cachePath ?? (WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cache');

        $this->config['source'] = $this->uploadsPath;
        $this->config['cache'] = $this->cachePath;
        $this->config['baseUrl'] = $this->baseUrl;
        $this->config = array_merge($this->config, $config);

        add_action('init', [$this, 'addRewrites']);
        add_action('parse_query', [$this, 'handle']);
    }

    /**
     * Add Glide images rewrite rules
     */
    public function addRewrites()
    {
        add_rewrite_tag('%glide-size%', '(.*?)');
        add_rewrite_tag('%glide-path%', '(.*)');
        add_rewrite_rule(sprintf('%s([^/]*)/(.*)', $this->baseUrl), 'index.php?glide-size=$matches[1]&glide-path=$matches[2]', 'top');
    }

    /**
     * Handle Glide image requests
     */
    public function handle()
    {
        global $wp_query;

        if (!is_object($wp_query)) {
            return;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (strpos($_SERVER['REQUEST_URI'], $this->baseUrl) === false) {
            return;
        }

        $this->serveImage($_SERVER['REQUEST_URI']);
    }

    /**
     * Serve as image
     *
     * @param $url
     */
    protected function serveImage(string $url)
    {
        $url = parse_url($url);
        $path = ltrim(str_replace($this->baseUrl, '', $url['path']), '/');

        list($size, $uploadFile) = explode('/', $path, 2);

        $uploadFilePath = $this->uploadsPath . DIRECTORY_SEPARATOR . $uploadFile;
        $config = $this->getSize($size);

        if (file_exists($uploadFilePath) && $config) {
            status_header(200);

            $config['server'] = array_merge($this->config, $config['server']);

            $server = ServerFactory::create($config['server']);
            $server->outputImage($uploadFile, $config['image']);

            die();
        }

        status_header(404);
        echo 'Image not found.';
        die(1);
    }

    /**
     * Image Glide URL
     *
     * @param string|int $url String URL or integer attachment ID
     * @param string     $slug
     *
     * @return mixed
     */
    public function imageUrl($url, string $slug)
    {
        if (is_int($url)) {
            $url = wp_get_attachment_url($url);
        }

        if (!$this->hasSize($slug)) {
            // Fallback to passed image URL
            return $url;
        }

        $relativeUrl = $this->baseUrl . "{$slug}/" . $this->relativeUploadUrl($url);

        return self::removeUrlProtocol(site_url($relativeUrl));
    }

    /**
     * Serve image as base64 encoded string
     *
     * @param $url
     *
     * @return string
     */
    protected function serveBase64(string $url)
    {
        $url = parse_url($url);
        $path = ltrim(str_replace($this->baseUrl, '', $url['path']), '/');

        list($size, $uploadFile) = explode('/', $path, 2);

        $uploadFilePath = $this->uploadsPath . DIRECTORY_SEPARATOR . $uploadFile;
        $config = $this->getSize($size);

        if (file_exists($uploadFilePath) && $config) {
            $config['server'] = array_merge($this->config, $config['server']);

            $server = ServerFactory::create($config['server']);

            return $server->getImageAsBase64($uploadFile, $config['image']);
        }

        return '';
    }

    /**
     * Serve image as base64 encoded string
     *
     * @param string|int $url String URL or integer attachment ID
     * @param string     $slug
     *
     * @return string
     */
    public function imageBase64($url, string $slug)
    {
        if (is_int($url)) {
            $url = wp_get_attachment_url($url);
        }

        $relativeUrl = str_replace(wp_upload_dir()['baseurl'], '', $url);
        $relativeUrl = ltrim($relativeUrl, '/');

        $uploadFilePath = $this->uploadsPath . DIRECTORY_SEPARATOR . $relativeUrl;
        $config = $this->getSize($slug);

        if (file_exists($uploadFilePath) && $config) {
            $config['server'] = array_merge($this->config, $config['server']);
            $server = ServerFactory::create($config['server']);

            return $server->getImageAsBase64($relativeUrl, $config['image']);
        }

        return '';
    }

    /**
     * Add image size
     *
     *
     * @param string $slug
     * @param array  $imageConfig
     * @param array  $serverConfig
     *
     * @return $this
     */
    public function addSize(string $slug, array $imageConfig, array $serverConfig = [])
    {
        $imageConfig['fm'] = $imageConfig['fm'] ?: 'pjpg';
        $imageConfig['q'] = $imageConfig['q'] ?: 75;

        $this->sizes[$slug] = [
            'image'  => $imageConfig,
            'server' => $serverConfig,
        ];

        return $this;
    }

    /**
     * Get image size
     *
     * @param string $slug
     *
     * @return mixed
     */
    public function getSize(string $slug)
    {
        return $this->sizes[$slug];
    }

    /**
     * If size exists
     *
     * @param string $slug
     *
     * @return bool
     */
    public function hasSize(string $slug)
    {
        return array_key_exists($slug, $this->sizes);
    }

    /**
     * Extract relative media upload URL. Will be used when building Glide media URLs
     *
     * @param string $url
     *
     * @return string
     */
    public function relativeUploadUrl(string $url)
    {
        $uploadsBaseUrl = wp_upload_dir()['baseurl'];

        return ltrim(str_replace($uploadsBaseUrl, '', $url), '/');
    }

    /**
     * Remove http(s) from URLs
     *
     * @param string $url
     *
     * @return string
     */
    public static function removeUrlProtocol(string $url): string
    {
        $find = ['http:', 'https:'];
        $replace = '';

        return str_replace($find, $replace, $url);
    }
}