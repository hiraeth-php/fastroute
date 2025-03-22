<?php

namespace Hiraeth\FastRoute;

use Doctrine\Inflector\Inflector;

/**
 *
 */
class MethodTransformer implements Transformer
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
	public function fromUrl($name, $value, array $context = [])
	{
		return lcfirst((string) $this->inflector->camelize($value));
	}


	/**
	 * {@inheritDoc}
	 */
	public function toUrl($name, $value, array $context = []): string
	{
		return strtolower(str_replace('_', '-', $this->inflector->tableize($value)));
	}
}
