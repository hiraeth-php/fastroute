<?php

namespace Hiraeth\FastRoute;

use FastRoute;
use RuntimeException;

use Hiraeth;
use Hiraeth\Routing;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/*
 *
 */
class Router implements Hiraeth\Routing\Router
{
	/**
	 *
	 */
	protected $dispatcher = NULL;


	/**
	 *
	 */
	protected $masks = array();


	/**
	 *
	 */
	protected $transformers = array();


	/**
	 *
	 */
	public function __construct(FastRoute\Dispatcher $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}


	/**
	*
	*/
	public function addMask($from, $to): Router
	{
		$this->masks[$from] = $to;

		return $this;
	}


	/**
	 *
	 */
	public function addTransformer($type, Transformer $transformer): Router
	{
		if (isset($this->transformers[$type])) {
			throw new RuntimeException(sprintf(
				'Transformer %s is already registered.  Cannot register %s for type "%s"',
				get_class($this->transformers[$type]),
				get_class($transformer),
				$type
			));
		}

		$this->transformers[$type] = $transformer;

		return $this;
	}


	/**
	 *
	 */
	public function getMasks(): array
	{
		return $this->masks;
	}


	/**
	 *
	 */
	public function getTransformers(): array
	{
		return $this->transformers;
	}


	/**
	 *
	 */
	public function mask(string $url, string $from, string $to): string
	{
		if (strpos($url, $from) === 0 && (strpos($to, $from) !== 0 || strpos($url, $to) !== 0)) {
			$url = substr_replace($url, $to, 0, strlen($from));
		}

		return $url;
	}


	/**
	 *
	 */
	public function match(Request $request, Response $response): Hiraeth\Routing\Route
	{
		$params = array();
		$method = $request->getMethod();
		$in_url = $request->getURI()->getPath();

		//
		// We get the internal URL by reversing our known masks.
		//

		foreach ($this->masks as $to => $from) {
			$in_url = $this->mask($in_url, $from, $to);
		}

		//
		// We then get the external URL by applying our masks to computed internal URL
		//

		$ex_url = $in_url;

		foreach ($this->masks as $from => $to) {
			$ex_url = $this->mask($ex_url, $from, $to);
		}

		//
		// See if we can get a result with the internal review, then decide how to respond
		// based on everything we know.
		//

		$result = $this->dispatcher->dispatch($method, $in_url);

		if ($ex_url != $request->getURI()->getPath()) {
			$target = $response->withStatus(301)->withHeader('Location', $ex_url);

		} elseif ($result[0] == $this->dispatcher::METHOD_NOT_ALLOWED) {
			$target = $response->withHeader('Allowed', join(',', $result[1]))->withStatus(405);

		} elseif ($result[0] == $this->dispatcher::NOT_FOUND) {
			$alt_url = substr($in_url, -1) == '/'
				? substr($in_url, 0, -1)
				: $in_url . '/';

			if ($this->dispatcher->dispatch($method, $alt_url)[0] != $this->dispatcher::NOT_FOUND) {
				$target = $response->withStatus(301)->withHeader('Location', $alt_url);
			} else {
				$target = $response->withStatus(404);
			}

		} else {
			$params  = $result[2];
			$target  = $result[1]['target'];

			foreach ($params as $name => $value) {
				$type = $result[1]['mapping'][$name] ?? NULL;

				if (!isset($this->transformers[$type])) {
					continue;
				}

				$params[$name] = $this->transformers[$type]->fromUrl($name, $value, $result[2]);
			}
		}

		return new Routing\Route($target, $params);
	}
}
