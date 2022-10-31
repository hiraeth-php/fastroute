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
	protected $patterns = array();


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
		$params  = array();
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
	 * Add a route for any supported method type
	 *
	 * @param string $route
	 * @param mixed $target
	 */
	public function any($route, $target): Collector
	{
		$this->addRoute(static::$methods, $route, $target);

		return $this;
	}
}
