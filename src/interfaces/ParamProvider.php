<?php

namespace Hiraeth\FastRoute;

/**
 * An interface for parameter providers used during link generation
 */
interface ParamProvider
{
	/**
	 * Get the value of a route parameter given the name of the parameter
	 *
	 * @access public
	 * @param string $name The name of the parameter for which to get a value
	 * @return mixed The value of the route parameter
	 */
	public function getRouteParameter($name);
}
