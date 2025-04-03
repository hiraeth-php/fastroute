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
	public function fromUrl(string $name, string $value, array $context = []): mixed
	{
		return $value;
	}


	/**
	 * {@inheritDoc}
	 */
	public function toUrl(string $name, mixed $value, array $context = []): string
	{
		$value = preg_replace_callback('#\b[A-Z]+\b#', [$this, 'acronymToTitle'], (string) $value);
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
