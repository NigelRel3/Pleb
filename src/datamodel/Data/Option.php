<?php
namespace Pleb\core\Data;

abstract class Option extends DataElement     {
    public function getColumns() : array    {
        return $this->details['columns'];
    }
}


