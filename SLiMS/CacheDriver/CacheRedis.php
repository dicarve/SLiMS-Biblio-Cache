<?php
/**
 * Class Name: Cache biblio data into Redis server
 * Description: This class handle biblio data caching mechanism into using Redis server.
 * Version: 0.0.1
 * Author: Ari Nugraha
 * Author URI: https://github.com/dicarve
 */
namespace SLiMS\BiblioCache\CacheDriver;

class CacheRedis extends \SLiMS\BiblioCache\BibioCacheInterface {
    public function setCache($key_name, $value, $lifetime = 60) {
        $this->cache_server->set($key_name, $value);
        $this->cache_server->expire($key_name, $lifetime);
    }

    public function getCache($key_name) {
        return $this->cache_server->get($key_name);
    }

    public function removeCache($key_name) {
        $this->cache_server->del($key_name);
    }

    public function purgeCaches() {
        $this->cache_server->flushAll();
    }

    public function connectServer($host = '127.0.0.1', $port = 9999, $auth = '', $options = array()) {
        global $sysconf;

        if (isset($sysconf['cache_host']) && !empty($sysconf['cache_host'])) {
            $this->cache_host = $sysconf['cache_host'];
        } else if (defined('CACHE_SERVER_HOST')) {
            $this->cache_host = CACHE_SERVER_HOST;
        } else {
            $this->cache_host = $host;
        }

        if (isset($sysconf['cache_port']) && !empty($sysconf['cache_port'])) {
            $this->cache_port = $sysconf['cache_port'];
        } else if (defined('CACHE_SERVER_PORT')) {
            $this->cache_port = CACHE_SERVER_PORT;
        } else {
            $this->cache_port = $port;
        }

        if (isset($sysconf['cache_lifetime']) && !empty($sysconf['cache_lifetime'])) {
            $this->cache_lifetime = $sysconf['cache_lifetime'];
        } else if (defined('CACHE_SERVER_CACHE_LIFETIME')) {
            $this->cache_lifetime = CACHE_SERVER_CACHE_LIFETIME;
        }

        if (class_exists("\Redis")) {
            // connect to Redis
            $this->cache_server = new \Redis();

            try {
                $this->cache_server->connect($this->cache_host, $this->cache_port);
                // authentication
                if (isset($sysconf['cache_auth']) && !empty($sysconf['cache_auth'])) {
                    $this->cache_auth = $sysconf['cache_auth'];
                } else if (defined('CACHE_SERVER_AUTH')) {
                    $this->cache_auth = CACHE_SERVER_AUTH;
                }
                if ($this->cache_auth) {
                    $this->cache_server->auth($this->cache_auth);
                }
            } catch (Exception $error) {
                debug($error->getMessage());
                return false;
            }
        }
    }
}
