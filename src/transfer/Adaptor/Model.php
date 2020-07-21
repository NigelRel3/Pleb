<?php
namespace Pleb\transfer\Adaptor;

use Pleb\transfer\Entity;
use Pleb\transfer\Lookup;
use Pleb\transfer\Sink;
use Pleb\transfer\Source;
use Pleb\transfer\Field\DateField;
use Pleb\transfer\Field\Field;
use Pleb\transfer\Updateable;

class Model extends Entity {
    use Source;
    use Sink;
    use Updateable;
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
    
    /**
     * @param string $name
     * @param string $type
     * @throws \InvalidArgumentException
     * @return Field
     */
    protected function decodeDataType ( string $name, string $type ) : Field  {
    	if ( ($fieldType = Field::getFieldFromType($type)) === false )	{
            throw new \InvalidArgumentException("Model::decodeDataType can't handle {$name} of type {$type}");
        }
        
        return $fieldType;
    }
    
    public function create()	{
    	$def = 'create table `'.$this->name.'` ('.PHP_EOL;
    	foreach ( $this->fields as $name => $column )   {
    		$def .= "    `".$name.'` '.$column->getDBDefinition().' DEFAULT NULL,'.PHP_EOL;
    	}
    	
    	$def = rtrim($def, ",".PHP_EOL).")";
    	
    	$this->db->query($def);
    	
    	return $this;
    }
    
    public function drop()	{
    	$def = 'drop table if exists `'.$this->name.'`';
    	
    	$this->db->query($def);
    	
    	return $this;
    }
    
    protected function loadColumns ()   {
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
    protected $queryInsert = null;
    protected $queryUpdate = null;
    
    public function open ( string $mode ) : void   {
        try {
        	if ( $mode == Entity::INPUT ) {
                $this->generateReadSQL();
            }
            else	{
                $this->generateWriteSQL();
            	$this->generateUpdateSQL();
            }
        }
        catch ( \PDOException $e )  {
            $this->query = null;
           	throw new \RuntimeException("Open source failed - {$e->getMessage()}",
           			Entity::OPEN_FAILED);
        }
    }
    
    protected function buildEntity ( array $fields )	{
    	// Default date formats
    	$localFields = $fields;
    	foreach ( $localFields as $key => $field )	{
    		if ( $field instanceof DateField )	{
    			$lField = clone($field);
    			$lField->setFormat(["Y-m-d"]);
    			$localFields[$key] = $lField;
    		}
    	}
    	$this->setFields($localFields);
    	$this->create();
    	// Retry
    	$this->open(Entity::OUTPUT);
    	
    }
    
    protected function generateReadSQL()    {
        // If SQL statement not already set (though setSQL())
        if ( $this->sqlStatement == null )  {
        	
        	if ( empty($this->fields))	{
        		$this->loadColumns();
        	}
        	
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
    	if ( empty($this->fields) )	{
    		$this->loadColumns();
    	}
    	// Build SQL statement
        $sql = "INSERT INTO `{$this->name}`(`".
                implode("`, `", array_keys($this->fields))."`)".
                " VALUES (:".implode(", :", array_keys($this->fields)).")";
        
        $this->queryInsert = $this->db->prepare($sql);
    }
    
    protected function generateUpdateSQL()    {
    	if ( empty($this->fields))	{
    		$this->loadColumns();
    	}
    	// Build SQL statement
    	$sql = "UPDATE `{$this->name}` SET ";
    	foreach ( array_keys($this->fields) as $field )	{
    		$sql .= "`{$field}` = :{$field}, ";
    	}
    	$sql = rtrim( $sql, ", ");
    	if ( !empty($this->where) )	{
   			$sql .= " WHERE {$this->where}";
    	}
    	
      	$this->queryUpdate = $this->db->prepare($sql);
    }
    
    protected function read() {
        if ( $this->query == null ) {
            throw new \RuntimeException("Source not open to read");
        }
        $data = $this->query->fetch(\PDO::FETCH_ASSOC);
        
        return $data;
    }

    public function write ( array $data, bool $insert = true )   {
    	if ( $this->queryInsert == null ) {
            throw new \RuntimeException("Source not open");
        }
        if ( $this->exportFormat != null )  {
            $data = ($this->exportFormat)($data );
        }
        if ( $data )	{
        	try	{
	        	if ( $insert )	{
	        		$this->queryInsert->execute($data);
	        	}
	        	else	{
	        		$this->queryUpdate->execute($data);
	        	}
        	}
        	catch ( \PDOException $e )	{
        		if ( $this->errorFile )	{
	        		$errorData = $data;
	        		$errorData['#Message'] = $e->getMessage();
	        		$this->errorFile->push($errorData);
        		}
        	}
        }
    }
    
    public function close() : void   {}

    protected $indexBy = null;
    
    public function indexBy ( $fields )   {
        $this->indexBy = $fields;
        return $this;
    }
    
    //protected function configure()  {}
    
    protected $fetchQuery = null;
    
    protected function generateFetchSQL()    {
        // If SQL statement not already set (though setSQL())
        if ( $this->fetchQuery == null )  {
        	if ( empty($this->fields))	{
        		$this->loadColumns();
        	}
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
    
}