<?php
/**
 * Class Name: Cache biblio data into JSON file
 * Description: This class handle biblio data caching mechanism using JSON.
 * Version: 0.0.1
 * Author: Ari Nugraha
 * Author URI: https://github.com/dicarve
 */
namespace SLiMS\BiblioCache\CacheDriver;

class CacheNative extends \SLiMS\BiblioCache\BibioCacheInterface {
    protected $loaded_cache_data = [];
    protected $cache_file = SB.'files'.DS.'cache'.DS.'cache_biblio_data.json';

    public function load_cache_file() {
        global $sysconf;

        if ( file_exists( $this->cache_file ) ) {
            // load the json file
            $json_string = file_get_contents( $this->cache_file );
            $this->loaded_cache_data = json_decode( $json_string, true );
            $this->cache_server = true;
        } else {
            if (is_writable(dirname($this->cache_file))) {
                // create the cache file
                $file = fopen( $this->cache_file, 'w' );
                fwrite( $file, '{}' );
                fclose( $file );
                $this->cache_server = true;
            } else {
                debug('Could not create cache file because the directory '.dirname($this->cache_file).' is not writable!');
                $this->cache_server = false;
            }
        }
    }

    // write the cache at the end of script execution
    public function __destruct() {
        if (is_writable(dirname($this->cache_file))) {
            file_put_contents( $this->cache_file, json_encode($this->loaded_cache_data) );
        }
    }

    public function setCache($key_name, $value, $lifetime = 60) {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $this->loaded_cache_data[$key_name]['lifetime'] = $lifetime;
        $this->loaded_cache_data[$key_name]['register_time'] = time();
        $this->loaded_cache_data[$key_name]['data'] = $value;
    }

    public function getCache($key_name) {
        if (isset($this->loaded_cache_data[$key_name])) {
            // check if the cache expired
            $diff = time() - $this->loaded_cache_data[$key_name]['register_time'];
            if ($this->loaded_cache_data[$key_name]['lifetime'] < $diff) {
                unset($this->loaded_cache_data[$key_name]);
            } else {
                return $this->loaded_cache_data[$key_name]['data'];
            }
        }
    }

    public function removeCache($key_name) {
        unset($this->loaded_cache_data[$key_name]);
    }

    public function purgeCaches() {
        $this->loaded_cache_data = [];
    }

    public function connectServer($host = '', $port = 0, $auth = '', $options = array()) {
        $this->load_cache_file();
    }
}
