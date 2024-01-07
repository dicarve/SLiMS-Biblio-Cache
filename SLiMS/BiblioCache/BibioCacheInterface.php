<?php
/**
 * Class Name: Abstract class for cache implementation
 * Description: This abstract class provides template for cache mechanism.
 * Version: 0.0.1
 * Author: Ari Nugraha
 * Author URI: https://github.com/dicarve
 */
namespace SLiMS\BiblioCache;

abstract class BibioCacheInterface {
    protected $cache_server = null;
    protected $cache_host = '127.0.0.1';
    protected $cache_port = 6379;
    protected $cache_auth = null;
    protected $cache_lifetime = 60;
    protected $cache_options = array();
    protected $db = null;

    /**
     * 
     * Set database connection object to the origin database server.
     * @param object $db : the database connection object such as PDO.
     * @return : void
     * 
     */
    public function setDBConn($db) {
        $this->db = $db;
    }

    /**
     * 
     * Connect to the cache server. This method should set the cache_server property
     * @param string $host  : the cache server address.
     * @param integer $port : the port number of host.
     * @param string $auth  : the authentication key/password for connection.
     * @param array $options: the options to the server connection.
     * @return void 
     * 
     */
    abstract public function connectServer($host = '127.0.0.1', $port = 9999, $auth = '', $options = array());

    /**
     * 
     * Set cache.
     * @param string $key_name  : the key name to hold the cache value.
     * @param mixed $value      : the value to store. We recommend to serialize the value into JSON string.
     * @param integer $lifetime : the lifetime of cache, in seconds.
     * @return void
     * 
     */
    abstract public function setCache($key_name, $value, $lifetime = 60);

    /**
     * 
     * Get cache based on the key name.
     * @param string $key_name : the key name to hold the cache value.
     * @return array
     * 
     */
    abstract public function getCache($key_name);

    /**
     * 
     * Delete cache based on the key name.
     * @param string $key_name : the key name to delete.
     * @return void
     * 
     */
    abstract public function removeCache($key_name);

    /**
     * 
     * Purge all caches registered.
     * @return void
     * 
     */
    abstract public function purgeCaches();

    /**
     * 
     * Set cache for a single biblio data.
     * @param integer $biblio_id        : the database ID of biblio data.
     * @param array $biblio_data_array  : the array of biblio data to cache.
     * @param integer $lifetime         : the lifetime of cache, in seconds.
     * @return void
     * 
     */
    public function cacheBiblioDetail($biblio_id, $biblio_data_array, $lifetime = 60) {
        $this->setCache( 'biblio_detail_'.$biblio_id, json_encode($biblio_data_array), $lifetime );
    }

    /**
     * 
     * Get cache data for a single biblio data.
     * @param integer $biblio_id        : the database ID of biblio data.
     * @return array/object
     * 
     */
    public function getBiblioDetailCache($biblio_id) {
        return $this->getCache( 'biblio_detail_'.$biblio_id );
    }

    /**
     * 
     * This method overrides the simbio_datagrid implementation so it can store and output the data from the cache as well.
     * @return void 
     * 
     */
    public function reCreateDatagrid() {
        global $datagrid, $dbs, $sysconf, $can_read, $can_write, $biblio_result_num;

        $this->connectServer();
        if ($this->cache_server) {
            $datagrid = new \SLiMS\BiblioCache\CachedDatagrid();
            $datagrid->setCacheServer($this);
            $datagrid->setCacheLifetime($this->cache_lifetime);
            
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

