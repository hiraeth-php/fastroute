<?php

namespace Hiraeth\FastRoute;

use RuntimeException;
use FastRoute;

/**
 *
 */
class Collector extends FastRoute\RouteCollector
{
	/**
	 * @var string[]
	 */
	static protected $methods = [
		'GET',
		'PUT',
		'PATCH',
		'POST',
		'DELETE',
		'HEAD',
	];

	/**
	 * @var array<string, string>
	 */
	protected $masks = [];

	/**
	 * @var array<string, string>
	 */
	protected $patterns = [];

	/**
	 * @var array<string, Transformer>
	 */
	protected $transformers = [];


	/**
	 * @param string $from
	 * @param string $to
	 */
	public function addMask(string $from, string $to): Collector
	{
		$this->masks[$from] = $to;

		return $this;
	}


	/**
	 * @param string $type The pattern type
	 * @param string $pattern A # delimetered regex for pattern matching
	 */
	public function addPattern(string $type, string $pattern): Collector
	{
		if (preg_match('#' . $pattern . '#', '') === FALSE) {
			throw new RuntimeException(sprintf(
				'Invalid pattern %s supplied for type %s',
				$pattern,
				$type
			));
		}

		$this->patterns[$type] = $pattern;

		return $this;
	}


	/**
	 * @param string[] $methods
	 * @param string $route
	 * @param mixed $target
	 */
	public function addRoute($methods, $route, $target): Collector
	{
		$params  = [];
		$pattern = $route;

		if (in_array('*', $methods)) {
			$methods = static::$methods;
		}

		if (preg_match_all('/{([^:]+)}/', $route, $matches)) {
			foreach ($matches[0] as $i => $token) {
				$name    = $matches[1][$i];
				$pattern = str_replace($token, '{' . $name . ':[^/]+}', $pattern);
			}
		}

		if (preg_match_all('/{([^:]+):([^}]+)}/', $pattern, $matches)) {
			$params = array_combine($matches[1], $matches[2]);

			foreach ($matches[0] as $i => $token) {
				$name = $matches[1][$i];
				$type = $matches[2][$i];

				if (!isset($this->patterns[$type])) {
					continue;
				}

				$pattern = str_replace($token, '{' . $name . ':' . $this->patterns[$type] . '}', $pattern);
			}
		}

		parent::addRoute($methods, $pattern, [
			'target'  => $target,
			'mapping' => $params
		]);

		return $this;
	}


	/**
	 *
	 */
	public function addTransformer(string $type, Transformer $transformer): Collector
	{
		if (isset($this->transformers[$type])) {
			throw new RuntimeException(sprintf(
				'Transformer %s is already registered.  Cannot register %s for type "%s"',
				$this->transformers[$type]::class,
				$transformer::class,
				$type
			));
		}

		$this->transformers[$type] = $transformer;

		return $this;
	}


	/**
	 * @return array<string, string>
	 */
	public function getMasks(): array
	{
		return $this->masks;
	}


	/**
	 * @return array<string, string>
	 */
	public function getPatterns(): array
	{
		return $this->patterns;
	}


	/**
	 * @return array<string, Transformer>
	 */
	public function getTransformers(): array
	{
		return $this->transformers;
	}


	/**
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 */
	public function link(string &$url, array &$params = [], bool $transform = TRUE, bool $encode = TRUE): array
	{
		$mapping = [];

		if (preg_match_all('/{([^:}]+)(?::([^}]+))?}/', $url, $matches)) {
			$mapping = array_combine($matches[1], $matches[2]) ?: [];
		}

		foreach (array_intersect(array_keys($mapping), array_keys($params)) as $name) {
			$type  = $mapping[$name];
			$value = $params[$name];

			if ($transform && isset($this->getTransformers()[$type])) {
				$value = $this->getTransformers()[$type]->toUrl($name, $value, $params);
			}

			$url = str_replace(
				$type ? '{' . $name . ':' . $type . '}' : '{' . $name . '}',
				$encode ? urlencode((string) $value) : $value,
				$url
			);

			unset($params[$name]);
			unset($mapping[$name]);
		}

		return $mapping;
	}


	/**
	 *
	 */
	public function mask(string $url, string $from, string $to): string
	{
		if (str_starts_with($url, $from) && (!str_starts_with($to, $from) || !str_starts_with($url, $to))) {
			$url = substr_replace($url, $to, 0, strlen($from));
		}

		return $url;
	}
}
