<?php 
namespace Pleb\transfer;

use Pleb\transfer\Field\Field;
use Pleb\transfer\Adaptor\Transient;

abstract class Entity  {
    protected $name = null;
    protected $methodChain = [];
    protected $endChain = [];
    
    protected const INPUT = "r";
    protected const OUTPUT = "w";
    protected const UPDATE = "u";
    
    protected $fields = [];
    // Orginal fields
    protected $sourceFields = [];
    
    protected $data = null;
    protected $groupData = [];
    
    protected $limit = null;
    protected $offset = null;
    
    protected $importFormat = null;
    protected $exportFormat = null;
    
    protected $groupBy = null;
    
    public const ASC = 0;
    public const DESC = 1;
    
    protected const OPEN_FAILED = -1;
    
    protected $errorFile = null;
    
    public const CONTINUE_PROCESSING = 0;
    public const SKIP_PROCESSING = 1;
    public const END_PROCESSING = 2;
    
    protected $recordNumber = 0;
    
    protected $templateRecord = null;
    
    public function __construct( $name = null)   {
        if ( $name != null )    {
            $this->setName($name);
        }
    }
    
    /**
     * 
     * @param string|callable $name
     */
    protected function setName( $name ) : void   {
        $this->name = $name;
    }
    
    /**
     * 
     * @param array<mixed> $data
     * @param array<string> $fieldList
     * @param bool $import
     * @return array|boolean
     */
    protected function validate ( array $data, array $fieldList, bool $import )	{
    	$errors = [];
    	foreach ( $fieldList as $fieldName => $field )   {
    		if ( isset($data[$fieldName]) )	{
    			try	{
	    			$field->set($data[$fieldName]);
	    			$data[$fieldName] = $import ? $field->get() : (string)$field;
	    		}
	    		catch ( \InvalidArgumentException $e )	{
	    			$errors[] = $fieldName.":".$e->getMessage();
	    		}
    		}
    		else	{
    			$data[$fieldName] = null;
    		}
    	}
    	if ( !empty($errors) )	{
    		if ( $this->errorFile != null )	{
    			$errorData = $data;
    			$errorData['#Message'] = implode(", ", $errors);
    			$this->errorFile->push($errorData);
    		}
    		$data = false;
    	}
    	return $data;
    }
    
    /**
     * 
     * @param array $fieldList
     * @return \Pleb\transfer\Entity
     */
    public function setFormatedFields( array $fieldList )    {
    	$this->importFormat = function ( $data ) use ($fieldList) {
    		return $this->validate ($data, $fieldList, true);
    	};
    	$this->exportFormat = function ( $data ) use ($fieldList) {
    		return $this->validate ($data, $fieldList, false);
    	};
        
        return $this;
    }
    
    /**
     * 
     * @param array $fieldList
     * @throws \RuntimeException
     * @return \Pleb\transfer\Entity
     */
    public function setFields( array $fieldList )  {
    	$this->sourceFields = $fieldList;
        $formatFields = [];
        foreach ( $fieldList as $fieldName => $field )   {
            if ( ! $field instanceof Field )  {
                throw new \RuntimeException("Field {$fieldName} must be an instance of Field.");
            }
            $this->fields[$fieldName] = $field;
            if ( $field->requiresFormatting() )    {
                $formatFields[$fieldName] = $field;
            }
        }
        
        if ( !empty($formatFields) )    {
            $this->setFormatedFields($formatFields);
        }
        
        return $this;
    }
    
    public function setLog() : bool {}
    
    public function open ( string $mode ) : void   {}
    public function close () : void   {}
    
    /**
     * Methods to modify the data
     */
    
    /**
     * 
     * @param array $fields
     * @return \Pleb\transfer\Entity
     */
    public function extract( array $fields )   {
        $this->methodChain[] = function ( &$data ) use ($fields) {
            $data = $this->processFilter ($data, $fields);
            return self::CONTINUE_PROCESSING;
        };
        return $this;
    }
    
    /**
     * 
     * @param string|callable $filter
     * @throws \InvalidArgumentException
     * @return \Pleb\transfer\Entity
     */
    public function filter( $filter )   {
        if ( ! is_callable($filter) )   {
            throw new \InvalidArgumentException("Entity::filter must be passed a callable");
        }
        $this->methodChain[] = function ($data) use ($filter) {
            $return = self::CONTINUE_PROCESSING;
            if ( $filter($data) == false )  {
                $return = self::SKIP_PROCESSING;
            }
            return $return;
        };
        return $this;
    }
    
    /**
     * 
     * @param callable $modify
     * @return \Pleb\transfer\Entity
     */
    public function modify( callable $modify )   {
        $this->methodChain[] = $modify;
        return $this;
    }
    
    /**
     * 
     * @param array<string> $fields
     * @return \Pleb\transfer\Entity
     */
    public function map ( array $fields )   {
        $this->methodChain[] = function ( &$data ) use ($fields) {
            $output = $data;
            foreach ( $fields as $from => $to )   {
            	unset ($output[$from]);
            }
            foreach ( $fields as $from => $to )   {
                $output[ $to ] = $data [ $from ];
            }
            $data = $output;
            return self::CONTINUE_PROCESSING;
        };
        return $this;
    }
 
    /**
     * 
     * @param array<string> $fields
     * @return \Pleb\transfer\Entity
     */
    public function set ( array $fields )   {
    	$this->methodChain[] = function ( &$data ) use ($fields) {
    		foreach ( $fields as $name => $to )   {
    			$data[ $name ] = $to;
    		}
    		return self::CONTINUE_PROCESSING;
    	};
    	return $this;
    }
    
    /**
     * 
     * @param array $sum
     * @return \Pleb\transfer\Entity
     */
    public function sum ( array $sum )   {
        $this->methodChain[] = function ( $data, $groupBy ) use ($sum) {
            $key = json_encode($groupBy);
            if ( ! isset($this->groupData[$key]) )    {
                $this->groupData[$key] = array_merge($groupBy,
                    array_fill_keys(array_values($sum), 0));
            }
            
            foreach ( $sum as $sumField => $sumResult )   {
                $this->groupData[$key][$sumResult] += $data[$sumField];
            }
            return self::CONTINUE_PROCESSING;
        };
        
        return $this;
    }

    /**
     * 
     * @param string $count
     * @return \Pleb\transfer\Entity
     */
    public function count ( string $count )   {
        $this->methodChain[] = function ( $data, $groupBy ) use ($count) {
            $key = json_encode($groupBy);
            if ( ! isset($this->groupData[$key][$count]) )    {
                $this->groupData[$key][$count] = 0;
            }
            
            $this->groupData[$key][$count] += 1;
            return self::CONTINUE_PROCESSING;
        };
        return $this;
    }
    
    /**
     * 
     * @param array<string> $groupBy
     * @return \Pleb\transfer\Adaptor\Transient
     */
    public function groupBy ( array $groupBy )  {
        $this->groupBy = array_flip($groupBy);
        
        $new = new Transient();
        $this->transfer();
        
        $new->data = array_values($this->groupData);
        
        return $new;
    }
     
    /**
     * 
     * @param array<string> $order
     * @throws \InvalidArgumentException
     */
    protected function validateOrderBy ( array $order ):void {
        foreach ( $order as $field => $type )   {
            if ( $type != Entity::ASC &&  $type != Entity::DESC ){
                throw new \InvalidArgumentException("Order by {$field} should be either Entity::ASC or Entity::DESC");
            }
            if ( !isset ( $this->fields[$field] ))   {
                throw new \InvalidArgumentException("Order by {$field} not found");
            }
        }
    }
    
    /**
     * 
     * @param array $orderBy
     * @throws \RuntimeException
     * @return \Pleb\transfer\Adaptor\Transient
     */
    public function orderBy ( array $orderBy )    {
        $this->validateOrderBy($orderBy);
        // clone so that original method stack is not changed
        $temp = clone $this;
        $new = new Transient();
        $temp->saveTo($new)
            ->transfer();
        $params = [];
        foreach ( $orderBy as $name => $order ) {
            $params[] = array_column($new->data, $name);
            $params[] = ( $order == Entity::ASC ) ? SORT_ASC : SORT_DESC;
        }
        $params[] = &$new->data;
        
        if ( array_multisort(...$params) === false )    {
            throw new \RuntimeException("Failed to apply orderBy.");
        }
        return $new;
    }
    
    
    public function create()	{}
    public function drop()	{}
    protected function buildEntity ( array $fields )	{}
    
    /**
     * 
     * @param Entity $outputTo
     * @throws \InvalidArgumentException
     * @return \Pleb\transfer\Entity
     */
    public function saveTo( Entity $outputTo ) {
    	if ( $outputTo->isWritable() == false )	{
    		throw new \InvalidArgumentException("Parameter must use the Sink trait");
    	}
    	
    	try	{
        	$outputTo->open(Entity::OUTPUT);
    	}
    	catch( \RuntimeException $e )	{
    		if ( $e->getCode() === Entity::OPEN_FAILED  
    					&& count($outputTo->fields) == 0 )	{
    			$outputTo->buildEntity($this->sourceFields);
    		}
    	}
        $this->methodChain[] = function ( $data ) use ($outputTo) {
            $outputTo->write ( $data );
            return self::CONTINUE_PROCESSING;
        };
        
        if ( $shutdown = $outputTo->getShutdown() )   {
            $this->endChain[] = $shutdown;
        }
        
        return $this;
    }
 
    /**
     * 
     * @param Entity $output
     * @throws \InvalidArgumentException
     * @return \Pleb\transfer\Entity
     */
    public function update ( Entity $output )	{
    	if ( $output->isUpdatable() == false )	{
    		throw new \InvalidArgumentException("Parameter must use the Updateable trait");
    	}
    	
    	$output->open(Entity::UPDATE);
    	$this->methodChain[] = function ( $data ) use ($output) {
    		$output->write ( $data, false );
    		return self::CONTINUE_PROCESSING;
    	};
    	
    	if ( $shutdown = $output->getShutdown() )   {
    		$this->endChain[] = $shutdown;
    	}
    	
    	return $this;
    }
    
    /**
     * 
     * @param int $limit
     * @param int $offset
     * @return \Pleb\transfer\Entity
     */
    public function setLimit( int $limit, int $offset = 0 )   {
        $this->methodChain[] = function ( $data ) use ($limit, $offset) {
            $return = self::CONTINUE_PROCESSING;
            if ( $offset > $this->recordNumber ) {
                $return = self::SKIP_PROCESSING;
            }
            if ( $offset + $limit <= $this->recordNumber  )    {
                $return = self::END_PROCESSING;
            }
            return $return;
        };
        
        return $this;
    }
    
    /**
     * 
     * @param Entity $source
     * @param array<string> $fields
     * @param string $prefix
     * @return \Pleb\transfer\Entity
     */
    public function join ( Entity $source, array $fields, string $prefix = '' )	{
    	return $this->lookup ( $source, $fields, $prefix );
    }
    
    /**
     * 
     * @param Entity $source
     * @param array<string> $fields
     * @param string $prefix
     * @return \Pleb\transfer\Entity
     */
    public function leftJoin ( Entity $source, array $fields, string $prefix = '' )	{
    	return $this->lookup ( $source, $fields, $prefix, false);
    }
    
    /**
     * Default processing for joining two Entities, used by join and leftJoin.
     * @param Entity $source
     * @param array<string> $fields
     * @param string $prefix
     * @param bool $fullJoin
     * @throws \InvalidArgumentException
     * @return \Pleb\transfer\Entity
     */
    protected function lookup ( Entity $source, array $fields, string $prefix = '',
    		bool $fullJoin = true )    {
    	if ( $source->isLookupable() == false )	{
            throw new \InvalidArgumentException("Parameter must use the Lookup trait");
        }
        $fieldIndex = array_flip($fields);
        $this->methodChain[] = function ( &$data )
        		use ($source, $fieldIndex, $prefix, $fullJoin) {
            if ( $lookupData = $source->fetch($data, $fieldIndex) )   {
                $return = self::CONTINUE_PROCESSING;
                foreach ( $lookupData as $name => $value ) {
                    $data[$prefix.$name] = $value;
                }
            }
            else    {
            	$return = $fullJoin ? self::SKIP_PROCESSING: self::CONTINUE_PROCESSING;
            }
            return $return;
        };
        
        return $this;
    }
    
    /**
     * Adds a file to send any validation errors to.
     * @param Entity $errorFile
     * @throws \InvalidArgumentException
     * @return \Pleb\transfer\Entity
     */
    public function validationErrors( Entity $errorFile )	{
    	if ( $errorFile->isWritable() == false )	{
    		throw new \InvalidArgumentException("Output must use the Sink trait");
    	}
    	$this->errorFile = (new Transient())->saveTo($errorFile);
    	$this->errorFile->open(Entity::OUTPUT);
    	return $this;
    }
    
    /**
     * Reads the source into the data for the current Entity.
     * @param Entity $source
     * @throws \InvalidArgumentException
     * @return \Pleb\transfer\Entity
     */
    public function loadFrom ( Entity $source )    {
    	if ( $source->isReadable() == false )	{
    		throw new \InvalidArgumentException("Input must use the Source trait");
        }
        // Clone source
        $from = clone $source;
        $from->saveTo($this) 
             ->transfer();
        return $this;
    }
    
    /**
     * Allow processing and then output data to new output as well.
     * @param callable $filter
     * @param Entity $subProcess
     * @param bool $update - flag to indicate if update should update original data
     * @throws \InvalidArgumentException
     * @return \Pleb\transfer\Entity
     */
    public function process ( callable $filter, Entity $subProcess )	{
    	if ( $subProcess->isWritable() == false )	{
    		throw new \InvalidArgumentException("Entity::process subProcess must use the Sink trait");
    	}
    	$this->methodChain[] = function (&$data) use ($filter, $subProcess) {
    		$return = self::CONTINUE_PROCESSING;
    		if ( $filter($data) == true )  {
    			$return = $subProcess->push( $data );
    		}
    		return $return;
    	};
    	if ( $shutdown = $subProcess->getShutdown() )   {
    		$this->endChain[] = $shutdown;
    	}
    	
    	return $this;
    }
    
    /**
     * Allow processing and then output data only to new here.
     * @param callable $filter
     * @param Entity $output
     * @throws \InvalidArgumentException
     * @return \Pleb\transfer\Entity
     */
    public function split ( callable $filter, Entity $output )  {
    	if ( $output->isWritable() == false )	{
    		throw new \InvalidArgumentException("Entity::split output must use the Sink trait");
    	}
    	$this->methodChain[] = function ($data) use ($filter, $output) {
    		$return = self::CONTINUE_PROCESSING;
    		if ( $filter($data) == true )  {
    			$return = $output->push( $data );
    			$return = self::SKIP_PROCESSING;
    		}
    		return $return;
    	};
    	
    	if ( $shutdown = $output->getShutdown() )   {
    		$this->endChain[] = $shutdown;
    	}
    	
    	return $this;
    }
    
    /**
     * 
     * @param array<mixed> $data
     * @return string
     */
    public function push ( array &$data )  {
        if ( $this->importFormat != null )  {
        	if (($data = ($this->importFormat)( $data )) === false)	{
        		return self::SKIP_PROCESSING;
        	}
        }
        // If grouping by anything, extract key values
        if ( $this->groupBy != null ) {
            $groupBy = array_intersect_key($data, $this->groupBy);
        }
        else    {
            $groupBy = null;
        }
        $return = self::CONTINUE_PROCESSING;
        // Apply any processing
        foreach ( $this->methodChain as $chain )    {
            $return = $chain( $data, $groupBy);
            if ( $return !== self::CONTINUE_PROCESSING )  {
                break;
            }
        }
        $this->recordNumber++;
        return $return;
    }
    
    /**
     * 
     * @return \Pleb\transfer\Entity
     */
    public function transfer () {
        $this->recordNumber = 0;
        $this->open(Entity::INPUT);
        
        while ( ($data = $this->read()) !== false ) {
            if ( $this->push($data) === self::END_PROCESSING )  {
                break;
            }
        }
        
        $this->shutdown();
        
        return $this;
    }
    
    /**
     * 
     */
    public function shutdown() : void  {
        foreach ( $this->endChain ?? [] as $shutDown )    {
            $shutDown();
        }
    }
    
    /**
     * 
     * @return boolean
     */
    public function getShutdown()   {
    	return false;
    }
    
    /**
     * 
     * @param array<mixed> $data
     * @param array $filter
     * @return array
     */
    protected function processFilter ( array $data, array $filter ) : array {
        $output = [];
        foreach ( $filter as $key ) {
            $output[$key] = $data[$key]??null;
        }
        
        return $output;
    }
        
    public function isWritable() : bool	{
    	return false;
    }
    
    public function isReadable() : bool	{
    	return false;
    }
    
    public function isUpdatable() : bool	{
    	return false;
    }
    
    public function isLookupable() : bool	{
    	return false;
    }
    
    //     abstract public function getStats() : Stats;
}

