<?php

namespace Hiraeth\FastRoute;

use Doctrine\Inflector\Inflector;

/**
 *
 */
class ClassTransformer implements Transformer
{
	/**
	 * @var Inflector|null
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
	 * {@inheritDoc}
	 */
	public function fromUrl($name, $value, array $context = array())
	{
		return ucfirst($this->inflector->camelize($value));
	}


	/**
	 * {@inheritDoc}
	 */
	public function toUrl($name, $value, array $context = array()): string
	{
		return strtolower(str_replace('_', '-', $this->inflector->tableize($value)));
	}
}
