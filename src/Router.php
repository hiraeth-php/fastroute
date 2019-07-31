<?php

namespace Hiraeth\FastRoute;

use Hiraeth;
use FastRoute;
use RuntimeException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/*
 *
 */
class Router implements Hiraeth\Routing\RouterInterface, Hiraeth\Routing\UrlGeneratorInterface
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
			throw new RuntimeException(
				'Transformer %s is already registered.  Cannot register %s for type "%s"',
				get_class($this->transformers[$type]),
				get_class($transformer),
				$type
			);
		}

		$this->transformers[$type] = $transformer;

		return $this;
	}


	/**
	 *
	 */
	public function anchor($location, array $params = array(), ?ParamProvider $provider = NULL): string
	{
		$query   = array();
		$mapping = array();
		$domain  = NULL;

		foreach ($this->masks as $from => $to) {
			$location = str_replace($from, $to, $location);
		}

		if (preg_match_all('/{([^:}]+)(?::([^}]+))?}/', $location, $matches)) {
			$mapping = array_combine($matches[1], $matches[2]);
		}

		if ($provider) {
			foreach ($mapping as $name => $type) {
				$params[$name] = $provider->getRouteParameter($name);
			}
		}

		foreach ($params as $name => $value) {
			if (!isset($mapping[$name])) {
				$query[$name] = $value;
				continue;
			}

			$type = $mapping[$name];

			if (isset($this->transformers[$type])) {
				$value = $this->transformers[$type]->toUrl($name, $value, $params);
			}

			$location = str_replace(
				$type ? '{' . $name . ':' . $type . '}' : '{' . $name . '}',
				urlencode($value),
				$location
			);
		}

		return $location . (count($query) ? '?' . http_build_query($query) : NULL);
	}


	/**
	 *
	 */
	public function match(Request $request, Response $response): Hiraeth\Routing\Route
	{
		$params = array();
		$method = $request->getMethod();
		$url    = str_replace(
			array_values($this->masks),
			array_keys($this->masks),
			$request->getURI()->getPath()
		);

		//
		// If any masks are in play and the URL was translated, redirect to the canonical URL.
		//

		$mask_url = $this->anchor($url);
		$result   = $this->dispatcher->dispatch($method, $url);

		if ($mask_url != $request->getURI()->getPath()) {
			$target = $response->withStatus(301)->withHeader('Location', $mask_url);

		} elseif ($result[0] == $this->dispatcher::NOT_FOUND) {
			$target = $response->withStatus(404);

		} elseif ($result[0] == $this->dispatcher::METHOD_NOT_ALLOWED) {
			$target = $response->withHeader('Allowed', join(',', $result[1]))->withStatus(405);

		} else {
			$params  = $result[2];
			$target  = $result[1]['target'];

			foreach ($params as $name => $value) {
				$type = $result[1]['mapping'][$name];

				if (!isset($this->transformers[$type])) {
					continue;
				}

				$params[$name] = $this->transformers[$type]->fromUrl($name, $value, $result[2]);
			}
		}

		return new Hiraeth\Routing\Route($target, $params);
	}
}
