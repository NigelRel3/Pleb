<?php
namespace Pleb\transfer;

trait Lookup {
    protected $indexBy = null;
    
    abstract public function indexBy ( array $fields );
    
    abstract protected function fetch ( array $data, array $key );

    public function isLookupable() : bool	{
    	return true;
    }
}
