<?php

namespace Hiraeth\FastRoute;

use Doctrine\Inflector\Inflector;

/**
 *
 */
class StringTransformer implements Transformer
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
		return $value;
	}


	/**
	 * {@inheritDoc}
	 */
	public function toUrl($name, $value, array $context = array()): string
	{
		$value = preg_replace_callback('#\b[A-Z]+\b#', [$this, 'acronymToTitle'], $value);
		$value = str_replace('_', '-', $this->inflector->tableize($value));
		$value = preg_replace('/[\/]/', '', $value);
		$value = preg_replace('/[^A-Z^a-z^0-9]+/', '-', $value);
		$value = preg_replace('/-+/', '-', $value);

		return strtolower(trim($value, '-'));
	}


	/**
	 * Convert acronyms to otherwise convertable text
	 *
	 * @param string[] $results
	 * @return string
	 */
	protected function acronymToTitle($results): string
	{
		return ucfirst(strtolower($results[0]));
	}
}
