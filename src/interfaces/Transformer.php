<?php

namespace Hiraeth\FastRoute;

/**
 *
 */
interface Transformer
{
	/**
	 * Convert a parameter from a URL into a PHP value
	 *
	 * @access public
	 * @param string $name The name of the parameter
	 * @param string $value The value of the parameter as derived from the URL
	 * @param mixed[] $context Additional context (other parameter values, enviornment, etc)
	 * @return mixed
	 */
	public function fromUrl(string $name, string $value, array $context = []);


	/**
	 * Convert a parameter from a PHP value into a string partial for a URL
	 *
	 * @access public
	 * @param string $name The name of the parameter
	 * @param mixed $value The value of the parameter to be made URL safe
	 * @param mixed[] $context Additional context (other parameter values, enviornment, etc)
	 * @return string
	 */
	public function toUrl(string $name, $value, array $context = []): string;
}
