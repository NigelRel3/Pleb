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
    
    protected $fields = [];
    
    protected $data = null;
    protected $groupData = [];
    
    protected $limit = null;
    protected $offset = null;
    
    protected $importFormat = null;
    protected $exportFormat = null;
    
    public function __construct( $name = null)   {
        if ( $name != null )    {
            $this->setName($name);
        }
        $this->configure();
    }
    
    public function setName( string $name ) : void   {
        $this->name = $name;
    }
    
    abstract protected function configure();
    
    public function setFormatedFields( array $fieldList )    {
        $this->importFormat = function ( $data ) use ($fieldList) {
            foreach ( $fieldList as $fieldName => $field )   {
                $field->set($data[$fieldName]);
                $data[$fieldName] = $field->get();
            }
            return $data;
        };
        $this->exportFormat = function ( $data ) use ($fieldList) {
            foreach ( $fieldList as $fieldName => $field )   {
                $field->set($data[$fieldName]);
                $data[$fieldName] = (string)$field;
            }
            return $data;
        };
        
        return $this;
    }
    
    public function setFields( array $fields )  {
        $formatFields = [];
        foreach ( $fields as $fieldName => $field )   {
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
    
    public function open ( string $mode )   {}
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
    
    public function modify( callable $modify )   {
        $this->methodChain[] = $modify;
        return $this;
    }
    
    public function map ( array $fields )   {
        $this->methodChain[] = function ( &$data ) use ($fields) {
            $output = $data;
            foreach ( $fields as $from => $to )   {
                $output[ $to ] = $data [ $from ];
                unset ($output[$from]);
            }
            $data = $output;
            return self::CONTINUE_PROCESSING;
        };
        return $this;
    }
    
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
    
    protected $groupBy = null;
    
    public function groupBy ( array $groupBy )  {
        
        $this->groupBy = array_flip($groupBy);
        
        $new = new Transient();
        $this->transfer();
        
        $new->data = array_values($this->groupData);
        
        return $new;
    }
     
    public const ASC = 0;
    public const DESC = 1;
        
    protected function validateOrderBy ( array $order ) {
        foreach ( $order as $field => $type )   {
            if ( $type != Entity::ASC &&  $type != Entity::DESC ){
                throw new \InvalidArgumentException("Order by {$field} should be either Entity::ASC or Entity::DESC");
            }
            if ( !isset ( $this->fields[$field] ))   {
                throw new \InvalidArgumentException("Order by {$field} not found");
            }
        }
    }
    
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
    
    public function saveTo( $outputTo ) {       // Return Stats
        if ( !in_array(Sink::class, $this->getTraits($outputTo)) ){
            throw new \InvalidArgumentException("Parameter must use the Sink trait");
        }
        $outputTo->open(Entity::OUTPUT);
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
     * @param callable $filter
     * @param Entity $output
     * @throws \InvalidArgumentException
     * @return \Pleb\transfer\Entity
     */
    public function split ( callable $filter, Entity $output )  {
        if ( !in_array(Sink::class, $this->getTraits($output)) ){
            throw new \InvalidArgumentException("Parameter must use the Sink trait");
        }
        $output->open(Entity::OUTPUT);
        $this->methodChain[] = function ( $data ) use ($filter, $output) {
            $return = self::CONTINUE_PROCESSING;
            if ( $filter($data) )  {
                $output->write ( $data );
                $return = self::SKIP_PROCESSING;
            }
            return $return;
        };
        
        if ( $shutdown = $output->getShutdown() )   {
            $this->endChain[] = $shutdown;
        }
        
        return $this;
    }
    
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
    
    public function lookup ( $source, array $fields, string $prefix = '' )    {
        if ( !in_array(Lookup::class, $this->getTraits($source)) ){
            throw new \InvalidArgumentException("Parameter must use the Lookup trait");
        }
        $fieldIndex = array_flip($fields);
        $this->methodChain[] = function ( &$data )
                    use ($source, $fieldIndex, $prefix) {
            if ( $lookupData = $source->fetch($data, $fieldIndex) )   {
                $return = self::CONTINUE_PROCESSING;
                foreach ( $lookupData as $name => $value ) {
                    $data[$prefix.$name] = $value;
                }
            }
            else    {
                $return = self::SKIP_PROCESSING;
            }
            return $return;
        };
        
        return $this;
    }
    
    public function loadFrom ( $source )    {
        if ( !in_array(Source::class, $this->getTraits($source)) ){
            throw new \InvalidArgumentException("Parameter must use the Source trait");
        }
        // Clone source
        $from = clone $source;
        $from->saveTo($this);
        $from->transfer();
        return $this;
    }
    
    public function process ( callable $filter, Entity $subProcess )	{
    	
    	
    	// TODO ?????????????????????????????????????????????????
    	/**
    	 * Work out how can use a process as a sub routine
    	 */
    	
    }
    
    // -------------------------------------------------------------
    public const CONTINUE_PROCESSING = 0;
    public const SKIP_PROCESSING = 1;
    public const END_PROCESSING = 2;
 
    protected $recordNumber = 0;
    
    public function push ( $data )  {
        if ( $this->importFormat != null )  {
            $data = ($this->importFormat)( $data );
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
    
    public function transfer () {
        $this->recordNumber = 0;
        $this->open(Entity::INPUT);
        
        while ( ($data = $this->read()) !== false ) {
            $return = $this->push($data);
            if ( $return === self::END_PROCESSING )  {
                break;
            }
        }
        
        $this->shutdown();
        
        return $this;
    }
    
    public function shutdown()  {
        foreach ( $this->endChain ?? [] as $shutDown )    {
            $shutDown();
        }
    }
    
    public function getShutdown()   {
    	return false;
    }
    
    protected function processFilter ( $data, array $filter ) : array {
        $output = [];
        foreach ( $filter as $key ) {
            $output[$key] = $data[$key]??null;
        }
        
        return $output;
    }
    
    protected function getTraits($class)   {
    	$traits = [];
    	
    	// Get traits of all parent classes
    	do {
    		$traits = array_merge(class_uses($class), $traits);
    	} while ($class = get_parent_class($class));
    	
    	return array_unique($traits);
    }
    
    //     abstract public function getStats() : Stats;
}

