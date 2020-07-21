<?php
namespace Pleb\transfer;

/**
 * Flag Entity to show it can be used as a source of data.
 * @author nigel
 *
 */
trait Source {
	/**
	 * Flag as readable.
	 * @return bool
	 */
	public function isReadable() : bool	{
		return true;
	}
}
