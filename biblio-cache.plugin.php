<?php
/**
 * Plugin Name: Biblio Cache
 * Plugin URI: https://github.com/slims/slims9_bulian
 * Description: Plugin to cache biblio data.
 * Version: 0.0.1
 * Author: Ari Nugraha
 * Author URI: https://github.com/dicarve
 */

require 'SLiMS/BiblioCache/BibioCacheInterface.php';
require 'NeoSimbio/GUI/SimbioTable.php';
require 'NeoSimbio/GUI/SimbioPaging.php';
require 'NeoSimbio/GUI/FormMaker/SimbioFormElement.php';
require 'NeoSimbio/GUI/FormMaker/SimbioFormMaker.php';
require 'NeoSimbio/Utils/SimbioSecurity.php';
require 'SLiMS/BiblioCache/CachedDatagrid.php';

use SLiMS\Plugins;

define( 'CACHE_DRIVER', 'CacheNative' );
define( 'CACHE_SERVER_HOST', '172.18.0.2' );
define( 'CACHE_SERVER_PORT', 6379 );
define( 'CACHE_SERVER_AUTH', null );
define( 'CACHE_SERVER_CACHE_LIFETIME', 60 );

// load the cache driver
try {
    if (isset($GLOBALS['sysconf']['cache_driver'])) {
        $cache_driver = $GLOBALS['sysconf']['cache_driver'];
        require 'SLiMS/CacheDriver/' . $GLOBALS['sysconf']['cache_driver'] . '.php';
    } else if (defined('CACHE_DRIVER'))  {
        $cache_driver = CACHE_DRIVER;
        require 'SLiMS/CacheDriver/' . CACHE_DRIVER . '.php';
    } else {
        $cache_driver = 'CacheNative';
        require 'SLiMS/CacheDriver/'.$cache_driver.'.php';
    }
} catch (Exception $error) {
    debug('Error on loading the cache driver for '. $cache_driver);
}

// echo $cache_driver;

/**
 * Register function to hook
 */
Plugins::use('SLiMS\BiblioCache\CacheDriver\\'.$cache_driver)->hook( Plugins::BIBLIOGRAPHY_BEFORE_DATAGRID_OUTPUT, 'reCreateDatagrid', 10 );
