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
	 * {@inheritDoc}
	 */
	static public function getClass(): string
	{
		return Collector::class;
	}


	/**
	 * {@inheritDoc}
	 */
	public function __invoke(Hiraeth\Application $app): object
	{
		$collector = new Collector(
			$app->get(FastRoute\RouteParser::class),
			$app->get(FastRoute\DataGenerator::class)
		);

		foreach (array_keys($app->getAllConfigs('fastroute', array())) as $collection) {
			$transformers = $app->getConfig($collection, 'fastroute.transformers', array());
			$patterns     = $app->getConfig($collection, 'fastroute.patterns', array());
			$masks        = $app->getConfig($collection, 'fastroute.masks', array());


			foreach ($transformers as $type => $transformer) {
				$collector->addTransformer($type, $app->get($transformer));
			}

			foreach ($patterns as $type => $pattern) {
				$collector->addPattern($type, $pattern);
			}

			foreach ($masks as $from => $to) {
				$collector->addMask($from, $to);
			}
		}

		foreach ($app->getAllConfigs('routing', array()) as $collection => $config) {
			$prefix = rtrim($config['prefix'] ?? '/', '/');

			$collector->addGroup($prefix, function($collector) use ($config) {
				foreach ($config['routes'] ?? array() as $route) {
					$collector->addRoute($route['methods'], $route['route'], $route['target']);
				}
			});
		}

		return $app->share($collector);
	}
}
