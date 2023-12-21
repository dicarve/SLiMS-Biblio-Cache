<?php
/**
 * Class Name: Cache biblio data into Redis server
 * Description: This class handle biblio data caching mechanism into using Redis server.
 * Version: 0.0.1
 * Author: Ari Nugraha
 * Author URI: https://github.com/dicarve
 */
namespace SLiMS\BiblioCache;

class CacheRedis {
    protected $redis = null;
    protected $redis_host = '127.0.0.1';
    protected $redis_port = 6379;
    protected $redis_auth = null;
    protected $redis_cache_lifetime = 60;
    protected $db = null;

    public function setDBConn($db) {
        $this->db = $db;
    }

    public function setCache($key_name, $key_value, $lifetime = 60) {
        $this->redis->set($key_name, $key_value);
        $this->redis->expire($key_name, $lifetime);
    }

    public function cacheBiblioDetail($biblio_id, $biblio_data_array, $lifetime = 60) {
        $this->setCache( 'biblio_detail_'.$biblio_id, json_encode($biblio_data_array), $lifetime );
    }

    public function connectRedis() {
        global $sysconf;

        if (isset($sysconf['redis_host']) && !empty($sysconf['redis_host'])) {
            $this->redis_host = $sysconf['redis_host'];
        } else if (defined('REDIS_HOST')) {
            $this->redis_host = REDIS_HOST;
        }

        if (isset($sysconf['redis_port']) && !empty($sysconf['redis_port'])) {
            $this->redis_port = $sysconf['redis_port'];
        } else if (defined('REDIS_PORT')) {
            $this->redis_port = REDIS_PORT;
        }

        if (isset($sysconf['redis_cache_lifetime']) && !empty($sysconf['redis_cache_lifetime'])) {
            $this->redis_cache_lifetime = $sysconf['redis_cache_lifetime'];
        } else if (defined('REDIS_CACHE_LIFETIME')) {
            $this->redis_cache_lifetime = REDIS_CACHE_LIFETIME;
        }

        if (class_exists("\Redis")) {
            // connect to Redis
            $this->redis = new \Redis();

            try {
                $this->redis->connect($this->redis_host, $this->redis_port);
                // authentication
                if (isset($sysconf['redis_auth']) && !empty($sysconf['redis_auth'])) {
                    $this->redis_auth = $sysconf['redis_auth'];
                } else if (defined('REDIS_AUTH')) {
                    $this->redis_auth = REDIS_AUTH;
                }
                if ($this->redis_auth) {
                    $this->redis->auth($this->redis_auth);
                }
            } catch (Exception $error) {
                debug($error->getMessage());
                return false;
            }
        }
    }

    public function reCreateDatagrid() {
        global $datagrid, $dbs, $sysconf, $can_read, $can_write, $biblio_result_num;
        // echo 'Cache Plugin Active';
        // exit();

        $this->connectRedis();
        if ($this->redis) {
            $datagrid = new CachedDatagrid();
            $datagrid->setRedis($this->redis);
            $datagrid->setRedisCacheLifetime($this->redis_cache_lifetime);
            
            // index choice
            if ($sysconf['index']['type'] != 'default') {
                // table spec
                $table_spec = 'search_biblio AS `index` LEFT JOIN item ON `index`.biblio_id=item.biblio_id';
                $str_criteria = 'index.biblio_id IS NOT NULL';
                if ($can_read AND $can_write) {
                    $datagrid->setSQLColumn('index.biblio_id', 'index.title AS \'' . __('Title') . '\'', 'index.labels', 'index.image',
                        'index.author',
                        'index.isbn_issn AS \'' . __('ISBN/ISSN') . '\'',
                        'IF(COUNT(item.item_id)>0, COUNT(item.item_id), \'<strong style="color: #f00;">' . __('None') . '</strong>\') AS \'' . __('Copies') . '\'',
                        'index.last_update AS \'' . __('Last Update') . '\'');
                    $datagrid->modifyColumnContent(1, 'callback{showTitleAuthors}');
                } else {
                    $datagrid->setSQLColumn('index.title AS \'' . __('Title') . '\'', 'index.author', 'index.labels', 'index.image',
                        'index.isbn_issn AS \'' . __('ISBN/ISSN') . '\'',
                        'IF(COUNT(item.item_id)>0, COUNT(item.item_id), \'<strong style="color: #f00;">' . __('None') . '</strong>\') AS \'' . __('Copies') . '\'',
                        'index.last_update AS \'' . __('Last Update') . '\'');
                    $datagrid->modifyColumnContent(1, 'callback{showTitleAuthors}');
                }
                $datagrid->invisible_fields = array(1, 2, 3);
                $datagrid->setSQLorder('index.last_update DESC');
        
                // set group by
                $datagrid->sql_group_by = 'index.biblio_id';
        
            } else {    
                // table spec
                $table_spec = 'biblio LEFT JOIN item ON biblio.biblio_id=item.biblio_id';
                $str_criteria = 'biblio.biblio_id IS NOT NULL';
                if ($can_read AND $can_write) {
                    $datagrid->setSQLColumn('biblio.biblio_id', 'biblio.biblio_id AS bid',
                        'biblio.title AS \'' . __('Title') . '\'',
                        'biblio.isbn_issn AS \'' . __('ISBN/ISSN') . '\'',
                        'IF(COUNT(item.item_id)>0, COUNT(item.item_id), \'<strong style="color: #f00;">' . __('None') . '</strong>\') AS \'' . __('Copies') . '\'',
                        'biblio.last_update AS \'' . __('Last Update') . '\'');
                    $datagrid->modifyColumnContent(2, 'callback{showTitleAuthors}');
                } else {
                    $datagrid->setSQLColumn('biblio.biblio_id AS bid', 'biblio.title AS \'' . __('Title') . '\'',
                        'biblio.isbn_issn AS \'' . __('ISBN/ISSN') . '\'',
                        'IF(COUNT(item.item_id)>0, COUNT(item.item_id), \'<strong style="color: #f00;">' . __('None') . '</strong>\') AS \'' . __('Copies') . '\'',
                        'biblio.last_update AS \'' . __('Last Update') . '\'');
                    // modify column value
                    $datagrid->modifyColumnContent(1, 'callback{showTitleAuthors}');
                }
                $datagrid->invisible_fields = array(0);
                $datagrid->setSQLorder('biblio.last_update DESC');

                // set group by
                $datagrid->sql_group_by = 'biblio.biblio_id';
            }

            $stopwords = "@\sAnd\s|\sOr\s|\sNot\s|\sThe\s|\sDan\s|\sAtau\s|\sAn\s|\sA\s@i";

            // is there any search
            $str_criteria = '1';
            if (isset($_GET['keywords']) AND $_GET['keywords']) {
                $keywords = $dbs->escape_string(trim($_GET['keywords']));
                $keywords = preg_replace($stopwords, ' ', $keywords);
                $searchable_fields = array('title', 'author', 'subject', 'isbn', 'publisher');
                if ($_GET['field'] != '0' AND in_array($_GET['field'], $searchable_fields)) {
                    $field = $_GET['field'];
                    $search_str = $field . '=' . $keywords;
                } else {
                    $search_str = '';
                    foreach ($searchable_fields as $search_field) {
                        $search_str .= $search_field . '=' . $keywords . ' OR ';
                    }
                    $search_str = substr_replace($search_str, '', -4);
                }
                $biblio_list = new \biblio_list($dbs, $biblio_result_num);
                $criteria = $biblio_list->setSQLcriteria($search_str);
                $str_criteria .= ' AND (' . $criteria['sql_criteria'] . ')';
            }
        
            if (isset($_GET['opac_hide']) && $_GET['opac_hide'] != '') {
                $opac_hide = $dbs->escape_string($_GET['opac_hide']);
                $str_criteria .= ' AND opac_hide =' . $opac_hide;
            }
        
            if (isset($_GET['promoted']) && $_GET['promoted'] != '') {
                $promoted = $dbs->escape_string($_GET['promoted']);
                $str_criteria .= ' AND promoted =' . $promoted;
            }
        
            debug($str_criteria);
            $datagrid->setSQLcriteria($str_criteria);
        
            // set table and table header attributes
            $datagrid->table_attr = 'id="dataList" class="s-table table"';
            $datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
            // set delete proccess URL
            $datagrid->chbox_form_URL = $_SERVER['PHP_SELF'];
            $datagrid->debug = true;
        }
    }
}

