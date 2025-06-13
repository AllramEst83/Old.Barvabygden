
##### How to add one.com varnish file into vcaching plugin?

## Include one.com file
Add following lines:

if( ! class_exists( 'OCVCaching' ) ) {
    include_once 'onecom-addons/onecom-inc.php';
}
if( ! class_exists( 'ONECOMUPDATER' ) ) {
    require_once plugin_dir_path( __FILE__ ).'/onecom-addons/inc/update.php';
}

Where?
vcaching.php, below to "$vcaching = new VCachingOC();"

===============

## Add filters
Add following lines:

$purgeme = apply_filters( 'ocvc_purge_url', $url, $path, $pregex );
$headers = apply_filters( 'ocvc_purge_headers', $url, $headers );

Where? 
vcaching.php, in "purge_url()" function, just before "wp_remote_request" for purge

===============

## Add filter
Add following line:
apply_filters( 'ocvc_purge_notices', $response, $purgeme );

Where?
vcaching.php, in "purge_url()" function, just after "wp_remote_request" for purge

===============

## Stop admin notice from main file
Add below line:
return;

Where?
vcaching.php
	1) Begining of "function purge_message()"
	2) Begining of "function purge_post_page()"

===============

## Permalink message filter
Replace all with below lines:
$message = '<div id="message" class="error"><p>' . __('Varnish Caching requires you to use custom permalinks. Please go to the <a href="options-permalink.php">Permalinks Options Page</a> to configure them.', $this->plugin) . '</p></div>';
echo apply_filters( 'ocvc_permalink_notice', $message );

Where?
vcaching.php, in "pretty_permalinks_message()" function

Note: If there is change here, get statement into $message variable and apply filter

===============

## Copy "onecom-addons" directory to plugin

===============

## Replace translations directory

===============

## ocver.php
This file helps you check if plugin or theme is allowed in the hosting package or not.

How to use?
1. Include ocver.php into your theme or plugin
2. Create new object with as below:
$OCVer = new OCVer( 
	$is_plugin = true, //if it is for plugin or theme
	$slug = 'onecom-varnish', //slug of plugin or theme
	$duration = 8  //duration of transient in hours
);

===============

## Add icon to admin menu item
Replace below line,
'title' => __('Purge ALL Varnish Cache', $this->plugin),

With,
'title' => '<span class="ab-icon dashicons dashicons-randomize"></span>' . __('Purge ALL Varnish Cache', $this->plugin),

Where?
vcaching.php, in "purge_varnish_cache_all_adminbar()" function

===============

## Include plugin info
1. Copy info directory, update changelog.html
2. Copy banner.svg and thumbnail.svg
3. Update author information, plugin information

===============

## Skip "varnish-conf" directory
Already added into .gitignore