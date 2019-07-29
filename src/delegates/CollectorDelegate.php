<?php

namespace Hiraeth\FastRoute;

use Hiraeth;
use FastRoute;

/**
 * Delegates are responsible for constructing dependencies for the dependency injector.
 *
 * Each delegate operates on a single concrete class and provides the class that it is capable
 * of building so that it can be registered easily with the application.
 */
class CollectorDelegate implements Hiraeth\Delegate
{
	/**
	 * Get the class for which the delegate operates.
	 *
	 * @static
	 * @access public
	 * @return string The class for which the delegate operates
	 */
	static public function getClass(): string
	{
		return Collector::class;
	}


	/**
	 * Get the instance of the class for which the delegate operates.
	 *
	 * @access public
	 * @param Hiraeth\Application $app The application instance for which the delegate operates
	 * @return object The instance of the class for which the delegate operates
	 */
	public function __invoke(Hiraeth\Application $app): object
	{
		$collector = new Collector(
			$app->get(FastRoute\RouteParser::class),
			$app->get(FastRoute\DataGenerator::class)
		);

		foreach (array_keys($app->getConfig('*', 'fastroute', array())) as $collection) {
			foreach ($app->get($collection, 'fastroute.patterns', array()) as $type => $pattern) {
				$collector->addPattern($type, $pattern);
			}
		}

		foreach ($app->getConfig('*', 'routing', array()) as $collection => $config) {
			$prefix = $config['prefix'] ?? '/';

			$collector->addGroup($config['prefix'] ?? '/', function($collector) use ($config) {
				foreach ($config['routes'] ?? array() as $route) {
					$collector->addRoute($route['methods'], $route['route'], $route['target']);
				}
			});
		}

		return $collector;
	}
}
