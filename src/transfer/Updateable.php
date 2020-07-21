<?php
namespace Pleb\transfer;

/**
 * Trait to flag that the particular entity is updatedable.
 * @author nigel
 *
 */
trait Updateable {
	/**
	 * Flag as updateable.
	 * @return bool
	 */
	public function isUpdatable() : bool	{
		return true;
	}
}
