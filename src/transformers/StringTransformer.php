<?php

namespace Hiraeth\FastRoute;

use Doctrine\Common\Inflector\Inflector;

/**
 *
 */
class StringTransformer implements Transformer
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
		return $value;
	}


	/**
	 *
	 */
	public function toUrl($name, $value, array $context = array()): string
	{
		$value = preg_replace_callback('#\b[A-Z]+\b#', [$this, 'acronymToTitle'], $value);
		$value = str_replace('_', '-', $this->inflector->tableize($value));
		$value = preg_replace('/[^A-Z^a-z^0-9^\/]+/', '-', $value);
		$value = preg_replace('/-+/', '-', $value);

		return strtolower(trim($value, '-'));
	}


	/**
	 *
	 */
	protected function acronymToTitle($results)
	{
		return ucfirst(strtolower($results[0]));
	}
}

