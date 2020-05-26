<?php
declare(strict_types=1);

namespace Pleb\core\Model;

abstract class ModelStatic extends Model {
    
    /**
     * TODO decide what update/insert/delete should do
     * Also should list fetch from the database?
     */
    /**
     * The static data to be used for this class.
     * @var array
     */
    protected static $dataLookup = [];
    
    /**
     * @return bool
     */
    public function  insert (): bool  {
        return false;
    }

    /**
     * @return bool
     */
    public function  update (): bool  {
        return false;
    }

    /**
     * @return bool
     */
    public function save (): bool {
        return false;
    }

    /**
     * @param array - key values
     * @return bool
     */
    public function fetch( array $keys ):bool    {
        return $this->fetchInt(json_encode($keys));
    }
    
    // TODO 
    public function fetchList( array $keys,
        string $selectSQL = null,
        string $where = null ):array    {
        
    }
    
    protected function fetchInt ( string $combinedKey ): bool   {
        $found = true;
        // TODO check this is table/schema safe
        if ( isset(static::$dataLookup[$combinedKey]) )  {
            $this->data = static::$dataLookup[$combinedKey];
        }
        else    {
            $found = false;
        }
        return $found;
    }
}