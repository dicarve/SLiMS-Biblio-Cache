<?php
/**
 * Plugin Name: Biblio Cache Redis
 * Plugin URI: https://github.com/slims/slims9_bulian
 * Description: Plugin to cache biblio data into Redis server.
 * Version: 0.0.1
 * Author: Ari Nugraha
 * Author URI: https://github.com/dicarve
 */

require 'SLiMS/BiblioCache/CacheRedis.php';
require 'NeoSimbio/GUI/SimbioTable.php';
require 'NeoSimbio/GUI/SimbioPaging.php';
require 'NeoSimbio/GUI/FormMaker/SimbioFormElement.php';
require 'NeoSimbio/GUI/FormMaker/SimbioFormMaker.php';
require 'NeoSimbio/Utils/SimbioSecurity.php';
require 'SLiMS/BiblioCache/CachedDatagrid.php';

use SLiMS\Plugins;
use SLiMS\BiblioCache\CacheRedis;

define( 'REDIS_HOST', '172.18.0.2' );
define( 'REDIS_PORT', 6379 );
define( 'REDIS_AUTH', null );
define( 'REDIS_CACHE_LIFETIME', 60 );

/**
 * Register function to hook
 */
Plugins::use(CacheRedis::class)->hook( Plugins::BIBLIOGRAPHY_BEFORE_DATAGRID_OUTPUT, 'reCreateDatagrid', 10 );
