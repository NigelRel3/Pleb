<?php
namespace Pleb\transfer\Adaptor;

use Pleb\transfer\Sink;
use Pleb\transfer\Source;
use Pleb\transfer\Lookup;
use Pleb\transfer\Entity;

// Transient is a resource which is just in memory
class Transient extends Entity  {
    use Source;
    use Sink;
    use Lookup;
    
    protected function configure()  {}
    
    public function open ( string $mode ) : void   {
        if ( $mode == "r" ) {
            reset($this->data);
        }
        else    {
            $this->data = [];
        }
    }
    protected function read() {
        $return = current($this->data);
        next($this->data);
        return $return;
    }
    
    public function indexBy ( $fields )   {
        $this->indexBy = $fields;
        $this->indexByTemplate = array_fill_keys($fields, null);
        return $this;
    }
 
    protected function write ( array $data )   {
        if ( $this->indexBy == null )   {
            $this->data[] = $data;
        }
        else    {
            $key = json_encode(array_intersect_key($data, $this->indexByTemplate));
            $this->data[$key] = $data;
        }
    }
    
    protected function fetch ( array $data, array $key )    {
        $keyThis = array_intersect_key($data, $key);
        $lookupKey = array_combine($this->indexBy, array_values($keyThis));
        $key = json_encode($lookupKey);
        
        return isset($this->data[$key]) ? $this->data[$key] : false;
    }
    
}

