<?php
declare(strict_types=1);
namespace Pleb\core\Model;

use Pleb\core\Util\Cache;


require_once __DIR__ . '/../../../vendor/autoload.php';

abstract class ModelCache extends Model {
    /**
     * TODO have cache specific to a class
     * @var Cache
     */
    protected static $cache;
    
    public static function setCache ( Cache $cache )   {
        self::$cache = $cache;
    }
    
    public function  insert (): bool  {
        if ( $ret = parent::insert() ) {
            $ret = self::$cache->set( $this->encodeKey($this->getKeyValues()), 
                    json_encode($this->data));
        }
        
        return $ret;
    }
    
    public function  update (): bool  {
        if ( $ret = parent::update() ) {
            $ret = self::$cache->set( $this->encodeKey($this->getKeyValues()),
                json_encode($this->data));
        }
        
        return $ret;
    }
    
    public function fetch( $keys ): bool    {
        $key = $this->encodeKey($keys);
        $cached = self::$cache->get($key);
        if ( $cached == false )   {
            $ret = parent::fetch($keys);
            self::$cache->set( $key, json_encode($this->data));
        }
        else    {
            $this->data = json_decode($cached, true);
            $ret = true;
        }
        return $ret;
    }

    public function delete( $keys ): bool    {
        $key = $this->encodeKey($keys);
        if ( $ret = parent::delete($keys) ) {
            $ret = self::$cache->delete( $key );
        }
        
        return $ret;
    }
    
    protected function getKeyValues (): array  {
        $keys = [];
        foreach ( static::$keyFields as $field )  {
            $keys[$field] = $this->data[$field];
        }
        return $keys;
    }
    
    protected function encodeKey( array $keys ): string {
        array_unshift($keys, get_called_class());
        return json_encode($keys);
    }
}