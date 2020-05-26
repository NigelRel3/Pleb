<?php
namespace Pleb\core\Util;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Monolog\Logger;
use Predis\Client;
use Predis\Collection\Iterator\Keyspace;

class Cache {
    /**
     * @var Client    */
    private $cache = null;
    /**
     * @var Logger
     */
    private $log;

    public function __construct( string $host, Logger $log = null )    {
        $this->log = $log;
        try {
            $cache = new Client('tcp://'.$host);
            $cache->connect();
            if ( $cache->isConnected() )    {
                $this->cache = $cache;
            }
            else    {
                $this->log->error("Connection failed:".$host);
                $cache->disconnect();
            }
        }
        catch ( \Exception $e ) {
            $this->log->error("Connection failed:".$e->getMessage());
        }
    }

    public function incr ( string $key, int $expireIn = null ) {
        $this->cache->incr($key);
        if ( $expireIn != null )  {
            $this->cache->expire($key, $expireIn);
        }
    }

    public function get ( string $key ) {
        return $this->cache->get($key);
    }

    public function delete ( string $pattern ) {
        $ret = true;
        // Use redis SCAN to create a Keyspace iterator and the convert to array.
        $keys = iterator_to_array(new Keyspace($this->cache, $pattern));
        if ( count($keys) > 0 ) {
            $ret = $this->cache->del($keys);
        }
        return $ret;
    }

    public function set ( string $key, $value, int $expireIn = null )   {
        if ( $expireIn != null )    {
            $ret = $this->cache->set( $key, $value, "ex", $expireIn );
        }
        else    {
            $ret = $this->cache->set( $key, $value );
        }
         
        return ($ret == "OK");
    }
}