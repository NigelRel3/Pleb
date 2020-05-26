<?php
namespace Pleb\transfer;

trait Lookup {
    protected $indexBy = null;
    
    public function indexBy ( array $fields )   {
        return $this;
    }
    
    protected function fetch ( array $data, array $key ) {}
}
