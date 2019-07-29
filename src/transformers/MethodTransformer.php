<?php

namespace Hiraeth\FastRoute;

use Doctrine\Common\Inflector\Inflector;

/**
 *
 */
class MethodTransformer implements Transformer
{
	/**
	 *
	 */
	protected $inflector = NULL;


	/**
	 *
	 */
	public function __construct(Inflector $inflector)
	{
		$this->inflector = $inflector;
	}


	/**
	 *
	 */
	public function fromUrl($name, $value, array $context = array())
	{
		return $this->inflector->classify($value);
	}


	/**
	 *
	 */
	public function toUrl($name, $value, array $context = array()): string
	{
		return str_replace('_', '-', $this->inflector->tableize($value));
	}
}
