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
class GCBDispatcherDelegate implements Hiraeth\Delegate
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
		return FastRoute\Dispatcher\GroupCountBased::class;
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
		$caching    = $app->getEnvironment('CACHING', TRUE);
		$cache_file = $app->getConfig(
			'packages/fastroute',
			'fastroute.cache_file',
			'storage/cache/' . md5(__CLASS__)
		);

		if ($caching && $app->hasFile($cache_file)) {
			$data = require($app->getFile($cache_file)->getRealPath());

			if (!is_array($data)) {
				throw new RuntimeException(sprintf(
					'Invalid cache file, routing.cache in "%s" is corrupted, delete the file.',
					$cache_dir
				));
			}

		} else {
			$data = $app->get(Collector::class)->getData();

			if ($caching) {
				$app->getFile($cache_file)->openFile('w+')->fwrite(sprintf(
					'<?php return %s;',
					var_export($data, TRUE)
				));
			}
		}

		return new FastRoute\Dispatcher\GroupCountBased($data);
	}
}
