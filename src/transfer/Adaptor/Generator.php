<?php
namespace Pleb\transfer\Adaptor;

use Pleb\transfer\Entity;
use Pleb\transfer\Source;

class Generator extends Entity	{
	use Source;
	
	public function open ( string $mode ) : void   {
		if ( is_callable($this->name) === false )    {
			throw new \RuntimeException("Callable name needed for a Generator.");
		}
		
		foreach (  ($this->name)() as $data )	{
			$this->push($data);
		}
	}
	
	protected function read() {
		return false;
	}
	
}