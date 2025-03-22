<?php

namespace Hiraeth\FastRoute;

use Hiraeth;
use FastRoute;
use Hiraeth\Routing;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;

/*
 *
 */
class Router implements Hiraeth\Routing\Router
{
	/**
	 * @var Collector
	 */
	protected $collector;

	/**
	 * @var FastRoute\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var UrlGenerator
	 */
	protected $generator;


	/**
	 *
	 */
	public function __construct(Collector $collector, FastRoute\Dispatcher $dispatcher, Hiraeth\Http\UrlGenerator $generator)
	{
		$this->collector  = $collector;
		$this->dispatcher = $dispatcher;
		$this->generator  = $generator;
	}


	/**
	 *
	 */
	public function match(Request $request, Response $response): Hiraeth\Routing\Route
	{
		$params = [];
		$method = $request->getMethod();
		$in_url = $request->getURI()->getPath();

		//
		// We get the internal URL by reversing our known masks.
		//

		foreach ($this->collector->getMasks() as $to => $from) {
			$in_url = $this->collector->mask($in_url, $from, $to);
		}

		//
		// We then get the external URL by applying our masks to computed internal URL
		//

		$ex_url = $in_url;

		foreach ($this->collector->getMasks() as $from => $to) {
			$ex_url = $this->collector->mask($ex_url, $from, $to);
		}

		//
		// See if we can get a result with the internal review, then decide how to respond
		// based on everything we know.
		//

		$result = $this->dispatcher->dispatch($method, $in_url);

		if ($ex_url != $request->getURI()->getPath()) {
			$target = $response->withStatus(301)->withHeader('Location', $ex_url);

		} elseif ($result[0] == $this->dispatcher::METHOD_NOT_ALLOWED) {
			$target = $response->withHeader('Allowed', implode(',', $result[1]))->withStatus(405);

		} elseif ($result[0] == $this->dispatcher::NOT_FOUND) {
			$alt_url = str_ends_with((string) $in_url, '/')
				? substr((string) $in_url, 0, -1)
				: $in_url . '/';

			if ($this->dispatcher->dispatch($method, $alt_url)[0] != $this->dispatcher::NOT_FOUND) {
				$target = $response->withStatus(301)->withHeader('Location', $alt_url);
			} else {
				$target = $response->withStatus(404);
			}

		} else {
			$params  = array_map('urldecode', $result[2]);
			$target  = $result[1]['target'];

			foreach ($params as $name => $value) {
				$type = $result[1]['mapping'][$name] ?? NULL;

				if (!isset($this->collector->getTransformers()[$type])) {
					continue;
				}

				$params[$name] = $this->collector
					->getTransformers()[$type]
					->fromUrl($name, $value, $result[2])
				;
			}

			if ($this->collector->link($target, $params, FALSE, FALSE)) {
				throw new RuntimeException(sprintf(
					'Target "%s" has unresolved parameters',
					$target
				));
			}
		}

		return new Routing\Route($target, $params);
	}
}
