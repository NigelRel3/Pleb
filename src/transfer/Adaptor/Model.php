<?php
namespace Pleb\transfer\Adaptor;

use Pleb\transfer\Entity;
use Pleb\transfer\Lookup;
use Pleb\transfer\Sink;
use Pleb\transfer\Source;
use Pleb\transfer\Field\Field;

class Model extends Entity {
    use Source;
    use Sink;
    use Lookup;
    
    /**
     * Database connection
     * @var \PDO
     */
    protected $db;

    protected static $insertSQL = null;
    protected static $selectSQL = null;
    protected static $selectSQLWhere = null;
    protected static $updateSQL = null;
    protected static $deleteSQL = null;
    
    protected $orderBy = null;
    protected $where = null;
    protected $bindData = [];
    /**
     * Override default to use database LIMIT processing.
     */
    protected $dblimit = null;
    protected $dboffset = null;
    
    protected function decodeDataType ( string $name, string $type ) : Field  {
        // Decode data types...
        if ( substr($type, 0, 3) == 'int' )   {
            $type = Field::INT();
        }
        elseif ( substr($type, 0, 7) == 'varchar' )   {
            $type = Field::STRING();
        }
        elseif ( $type == 'date' )   {
            $type = Field::Date("Y-m-d");
        }
        elseif ( substr($type, 0, 4) == 'enum' )   {
            $type = Field::STRING();
        }
        else    {
            throw new \InvalidArgumentException("Model::decodeDataType can't handle {$name} of type {$type}");
        }
        
        return $type;
    }
    
    public function loadColumns ()   {
        if ( $this->db == null ) {
            throw new \RuntimeException("Database not configured");
        }
        $query = $this->db->query("describe `{$this->name}`");
        $formatFields = [];
        while ( $data = $query->fetch(\PDO::FETCH_ASSOC) )    {
            $type = $this->decodeDataType($data['Field'], $data['Type']);
            
            $this->fields[$data['Field']] = $type;
            if ( $type->requiresFormatting() )    {
                $formatFields[$data['Field']] = $type;
            }
        }
        if ( !empty($formatFields) )    {
            $this->setFormatedFields($formatFields);
        }
        return $this;
    }
    
    protected $sqlStatement = null;
    
    public function setSQL ( string $sql )  {
        $this->sqlStatement = $sql;
        
        return $this;
    }
    
    public function filter( $where )   {
        if ( is_string($where) )    {
            $this->where = $where;
        }
        else    {
            // If not a string, see if it can be handled by the normal route
            try {
                parent::filter($where);
            }
            catch ( \InvalidArgumentException $e )   {
                throw new \InvalidArgumentException("Model::filter must be passed a string or a callable");
            }
        }
        return $this;
    }
    
    public function setBindData( array $data ) : void   {
        $this->bindData = $data;
    }
    
    public function setDB ( \PDO $db )  {
        $this->db= $db;
        return $this;
    }
    
    public function setLimit( int $limit, int $offset = 0 )   {
        $this->dblimit = $limit;
        $this->dboffset = $offset;
        return $this;
    }
    
    public function orderBy($orderBy)   {
        $this->orderBy = $orderBy;
        return $this;
    }
    
    protected $query = null;
    
    public function open ( string $mode )   {
        try {
            if ( $mode == "r" ) {
                $this->generateReadSQL();
            }
            else    {
                $this->generateWriteSQL();
            }
        }
        catch ( \PDOException $e )  {
            $this->query = null;
            throw new \RuntimeException("Open source failed - {$e->getMessage()}");
        }
    }

    protected function generateReadSQL()    {
        // If SQL statement not already set (though setSQL())
        if ( $this->sqlStatement == null )  {
            // Build SQL statement
            $sql = "SELECT `".implode("`, `", array_keys($this->fields)).
                    "` FROM `{$this->name}`";
            if ( !empty($this->where) )  {
                $sql .= " WHERE {$this->where}";
            }
            if ( !empty( $this->orderBy ) )     {
                $sql .= " ORDER BY ";
                foreach ( $this->orderBy as $field => $type )  {
                    $sql .= $field." ". (($type == Entity::ASC) ? "ASC" : "DESC").", ";
                }
                $sql = substr($sql, 0, -2);
            }
            if ( !empty( $this->dblimit ) )     {
                $sql .= " LIMIT {$this->dblimit}";
                if ( !empty( $this->dboffset ) )     {
                    $sql .= ", {$this->dboffset}";
                }
            }
            //         echo $sql;
            $this->sqlStatement = $sql;
        }
        
        $this->query = $this->db->prepare($this->sqlStatement);
        $this->query->execute($this->bindData);
    }

    protected function generateWriteSQL()    {
        // Build SQL statement
        $sql = "INSERT INTO `{$this->name}`(`".
                implode("`, `", array_keys($this->fields))."`)".
                " VALUES (:".implode(", :", array_keys($this->fields)).")";
        
//         echo $sql;
        
        $this->query = $this->db->prepare($sql);
    }
    

    protected function read() {
        if ( $this->query == null ) {
            throw new \RuntimeException("Source not open to read");
        }
        $data = $this->query->fetch(\PDO::FETCH_ASSOC);
        
        return $data;
    }

    public function write ( array $data )   {
        if ( $this->query == null ) {
            throw new \RuntimeException("Source not open");
        }
        if ( $this->exportFormat != null )  {
            $data = ($this->exportFormat)($data );
        }
        $this->query->execute($data);
    }
    
    public function close() : void   {}

    protected $indexBy = null;
    
    public function indexBy ( $fields )   {
        $this->indexBy = $fields;
        return $this;
    }
    
    protected function configure()  {}
    
    protected $fetchQuery = null;
    
    protected function generateFetchSQL()    {
        // If SQL statement not already set (though setSQL())
        if ( $this->fetchQuery == null )  {
            // Build SQL statement
            $sql = "SELECT `".implode("`, `", array_keys($this->fields)).
                "` FROM `{$this->name}`";
            if ( !empty($this->indexBy) )  {
                $sql .= " WHERE {$this->indexBy}";
            }
            if ( !empty( $this->orderBy ) )     {
                $sql .= " ORDER BY ";
                foreach ( $this->orderBy as $field => $type )  {
                    $sql .= $field." ". (($type == Entity::ASC) ? "ASC" : "DESC").", ";
                }
                $sql = substr($sql, 0, -2);
            }
            if ( !empty( $this->dblimit ) )     {
                $sql .= " LIMIT {$this->dblimit}";
                if ( !empty( $this->dboffset ) )     {
                    $sql .= ", {$this->dboffset}";
                }
            }
        }
        
        $this->fetchQuery = $this->db->prepare($sql);
    }
    
    protected function fetch ( array $data, array $key )    {
        if ( $this->fetchQuery == null )    {
            $this->generateFetchSQL();
        }
        $this->fetchQuery->execute(array_intersect_key($data, $key));
        
        $data = $this->fetchQuery->fetch(\PDO::FETCH_ASSOC);
        if ( $data && $this->importFormat != null )  {
            $data = ($this->importFormat)( $data );
        }
        
        return $data;
    }
    
//     /**
//      * @return bool
//      */
//     public function  update (): bool  {
//         $updated = false;
//         try {
//             $update = $this->db->prepare(static::$updateSQL);
//             $data = $this->data;
//             // Add in loaded keys to binds...
//             foreach ( $this->loadedKey as $key=>$value )    {
//                 $data[$key."_old"] = $value;
//             }
//             $update->execute($data);
//             $updated = true;
//         }
//         catch ( \PDOException $e )  {
//             $this->log("Error in update ".static::class."-".$e);
//         }
        
//         return $updated;
//     }

}