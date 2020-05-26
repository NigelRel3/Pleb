<?php
namespace Pleb\core\Model;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Monolog\Logger;
/**
 * TODO
 * Need to think about many to many relationship.
 */


abstract class Model {
    /**
     * Database connection
     * @var \PDO
     */
    protected $db;
    /**
     * @var Logger
     */
    protected $log;

    protected static $insertSQL = null;
    protected static $selectSQL = null;
    protected static $selectSQLWhere = null;
    protected static $updateSQL = null;
    protected static $deleteSQL = null;
    
    /**
     * Which of the fields is the auto-index (if any)
     * @var int
     */
    protected static $autoIndexField = null;
    /**
     * Which fields make up the key
     * @var array int
     */
    protected static $keyFields = [];
    /**
     * Key used to load record.
     * @var array mixed
     */
    protected $loadedKey = [];
    /**
     * The data for this entity
     * @var array mixed
     */
    protected $data = [];
    /**
     * Data returned when fetching more than 1 table.
     * @var array
     */
    protected  $extraData = [];
    /**
     * Data for sub records
     * @var array Model
     */
    protected $subData = [];
    /**
     * Is this active data.
     * @var bool
     */
    protected $active = false;
    /**
     * @param \PDO $db
     * @param \Monolog\Logger $log
     */
    public function __construct( \PDO $db = null, Logger $log = null )   {
        $this->db = $db;
        $this->log = $log;
    }

    /**
     * @param string $msg
     * @param \PDOStatement $handle
     */
    protected function log ( string $msg, $handle = null )    {
        if ( $handle != null )  {
            $msg .=':'.$handle->errorInfo()[0]."(".$handle->errorInfo()[1].")"
                    .$handle->errorInfo()[2];
        }
        $this->log->error($msg);
    }

    /**
     * @return bool
     */
    public function  insert (): bool  {
        $this->active = false;

        try {
            $new = $this->db->prepare(static::$insertSQL);
            $new->execute($this->data);
            if ( static::$autoIndexField != null )    {
                $this->data[static::$autoIndexField] = $this->db->lastInsertId();
            }
            $this->active = true;
        }
        catch ( \PDOException $e )  {
            $this->log("Error in insert ".static::class."-".$e);
        }

        return $this->active;
    }

    /**
     * @return bool
     */
    public function  update (): bool  {
        $updated = false;
        try {
            $update = $this->db->prepare(static::$updateSQL);
            $data = $this->data;
            // Add in loaded keys to binds...
            foreach ( $this->loadedKey as $key=>$value )    {
                $data[$key."_old"] = $value;
            }
            $update->execute($data);
            $updated = true;
        }
        catch ( \PDOException $e )  {
            $this->log("Error in update ".static::class."-".$e);
        }
        
        return $updated;
    }

    /**
     * @return bool
     */
    public function save (): bool {
        if ( $this->active )    {
            $ok = $this->update();
        }
        else    {
            $ok = $this->insert();
        }

        return $ok;
    }

    /**
     * @param array - key values
     * @return bool
     */
    public function fetch( array $keys ):bool    {
        $selected = false;
        try {
            $sql = static::$selectSQL. " where ".static::$selectSQLWhere;
            $select = $this->db->prepare($sql);
            $select->execute($keys);
            $this->data = $select->fetch(\PDO::FETCH_ASSOC);
            if ( $this->data )    {
                $selected = true;
                $this->loadedKey = $keys;
                }
            else    {
                $selected = false;
                $this->data = [];
            }
            $this->active = $selected;
        }
        catch ( \PDOException $e )  {
            
            echo "Error {$e}".PHP_EOL;
            
            $this->log("Error in fetch ".static::class."-".$e);
        }

        return $selected;
    }

    /**
     * @param array - key values
     * @return array
     */
    public function fetchList( array $keys, 
            string $selectSQL = null, 
            string $where = null ):array    {
        $selected = [];
        
        try {
            $sql = $selectSQL ?? self::$selectSQL;
            if ( !empty($where) )   {
                $sql.= " where ".$where;
            }
            $select = $this->db->prepare($sql);
            $select->execute($keys);
            $class = get_called_class();
            while ( $data = $select->fetch(\PDO::FETCH_ASSOC) ) {
                $newObject = new $class;
                $newObject->data = $data;
                $selected[] = $newObject;
            }
        }
        catch ( \PDOException $e )  {
            $this->log("Error in fetch ".static::class."-".$e);
            $selected = [];
        }
        
        return $selected;
    }
    
    /**
     * @param array - key values
     * @return bool
     */
    public function delete( $keys ):bool    {
        $deleted = false;
        try {
            $select = $this->db->prepare(static::$deleteSQL);
            $select->execute($keys);
            $deleted = true;
            $this->active = false;
        }
        catch ( \PDOException $e )  {
            $this->log("Error in delete ".static::class."-".$e);
        }
        
        return $deleted;
    }
    
    public function set( array $data ) : void   {
        $this->data = $data;
        $this->active = true;
    }
}