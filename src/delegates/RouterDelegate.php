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
	 * Get the class for which the delegate operates.
	 *
	 * @static
	 * @access public
	 * @return string The class for which the delegate operates
	 */
	static public function getClass(): string
	{
		return Router::class;
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
		$router = new Router($app->get(FastRoute\Dispatcher::class));

		foreach (array_keys($app->getConfig('*', 'fastroute', array())) as $collection) {
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
