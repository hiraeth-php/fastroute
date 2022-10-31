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
class RouterDelegate implements Hiraeth\Delegate
{
	/**
	 * {@inheritDoc}
	 */
	static public function getClass(): string
	{
		return Router::class;
	}


	/**
	 * {@inheritDoc}
	 */
	public function __invoke(Hiraeth\Application $app): object
	{
		$router = new Router($app->get(FastRoute\Dispatcher::class));

		foreach (array_keys($app->getAllConfigs('fastroute', array())) as $collection) {
			$transformers = $app->getConfig($collection, 'fastroute.transformers', array());
			$masks        = $app->getConfig($collection, 'fastroute.masks', array());

			foreach ($transformers as $type => $transformer) {
				$router->addTransformer($type, $app->get($transformer));
			}

			foreach ($masks as $from => $to) {
				$router->addMask($from, $to);
			}
		}

		return $app->share($router);
	}
}
