<?php
namespace Pleb\transfer;

trait Sink {
	public function isWritable() : bool	{
		return true;
	}
}
