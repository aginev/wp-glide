# PHP Glide (http://glide.thephpleague.com/) for WordPress

## Install

```bash
composer require aginev/wp-glide
```

## Usage

General configuration could be made at your function.php file in your theme.


### Get instance of the WpGlide.

It's a singleton instance, so you will get just the same object everywhere in your application.

```php
$wpGlide = \WpGlide\WpGlide::getInstance();
// Or
$wpGlide = wp_glide();
```

### Glide server config

You should config WpGlide at least once in your application. The init method could have four parameters and all of them are not required.

```php
$wpGlide->init(
    // Glide server config. See: http://glide.thephpleague.com/1.0/config/setup/
    [
        // Image driver
        'driver'     => 'imagick',
        // Watermarks path
        'watermarks' => new \League\Flysystem\Filesystem(new \League\Flysystem\Adapter\Local(get_template_directory() . '/assets/img')),
    ],
    
    // Base path. By default set to 'img/' and the final URL will look like so: http://example.com/BASE-PATH/SIZE-SLUG/image.jpg.
    'img/',
    
    // Path to WordPress upload directory. If not set the default upload directory will be used.
    'upload_path',
    
    // Cache path. If not set the cache will be placed in cache directory at the root of the default upload path.
    'cache_path'
);

// Or
$wpGlide = wp_glide()->init([...]);
```

### Register image sizes

You should register image sizes that will be handled by Glide like so:

```php
$wpGlide->addSize('w128', [
    'w'  => 128,
    'q'  => 75,
    'fm' => 'pjpg',

    'mark'      => 'watermark.png',
    'markw'     => 512,
    'markh'     => 512,
    'markalpha' => 75,
    'markfit'   => 'fill',
    'markpos'   => 'center',
])->addSize('w512', [
    'w'  => 512,
    'q'  => 75,
    'fm' => 'pjpg',
])->addSize('16x9', [
    'w'   => 16 * 10 * 2,
    'h'   => 9 * 10 * 2,
    'fit' => 'crop',
    'q'   => 75,
    'fm'  => 'pjpg',
]);
```

### Usage in templates

Then you can get Glide image URL in your views/templates like so: 

```html
<!-- Get Glide image URL by it's original URL -->
<img src="<?php echo wp_glide_image('http://example.com/wp-content/2018/01/image.jpg', 'w128'); ?>" />

<!-- Get Glide image URL by it's ID in the WordPress media library -->
<img src="<?php echo wp_glide_image(1, 'w128'); ?>" />

<!-- Result: <img src="//example.com/img/w128/2018/01/image.jpg" /> -->
<!-- Note that protocol will be stripped and you do not need to care about it. -->
```

You can get Glide image base64 encoded version in your views/templates like so: 

```html
<!-- Get Glide image URL by it's original URL -->
<img src="<?php echo wp_glide_base64('http://example.com/image.jpg', 'w128'); ?>" />

<!-- Get Glide image URL by it's ID in the WordPress media library -->
<img src="<?php echo wp_glide_base64(1, 'w128'); ?>" />

<!-- Result: <img src="data:image/jpeg;base64..." /> -->
```

