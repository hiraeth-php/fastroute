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
	public function fromUrl(string $name, string $value, array $context = []): mixed
	{
		return lcfirst((string) $this->inflector->camelize($value));
	}


	/**
	 * {@inheritDoc}
	 */
	public function toUrl(string $name, mixed $value, array $context = []): string
	{
		return strtolower(str_replace('_', '-', $this->inflector->tableize($value)));
	}
}
