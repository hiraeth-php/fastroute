<?php

namespace Hiraeth\FastRoute;

use Hiraeth\Routing;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 *
 */
class UrlGenerator implements Routing\UrlGenerator
{
	/**
	 *
	 */
	protected $router;


	/**
	 *
	 */
	public function  __construct(Router $router)
	{
		$this->router = $router;
	}


	/**
	 *
	 */
	public function __invoke($location, array $params = array(), ?ParamProvider $provider = NULL, ?bool $mask = TRUE): string
	{
		$query   = array();
		$mapping = array();

		if ($location instanceof Request) {
			return $this($location->getUri()->getPath(), $params + $location->getQueryParams());
		}

		if ($location instanceof SplFileInfo) {
			return $this($location->getPathName());
		}

		if ($mask) {
			foreach ($this->router->getMasks() as $from => $to) {
				$location = $this->router->mask($location, $from, $to);
			}
		}

		if (preg_match_all('/{([^:}]+)(?::([^}]+))?}/', $location, $matches)) {
			$mapping = array_combine($matches[1], $matches[2]);
		}

		if ($provider) {
			foreach ($mapping as $name => $type) {
				if (isset($params[$name])) {
					continue;
				}

				$params[$name] = $provider->getRouteParameter($name);
			}
		}

		foreach ($params as $name => $value) {
			if (isset($mapping[$name])) {
				$type = $mapping[$name];

				if (isset($this->router->getTransformers()[$type])) {
					$value = $this->router->getTransformers()[$type]->toUrl($name, $value, $params);
				}

				$location = str_replace(
					$type ? '{' . $name . ':' . $type . '}' : '{' . $name . '}',
					urlencode($value),
					$location
				);

			} else {
				$query[$name] = $this->filter($value);
			}
		}

		if (count(array_filter($query))) {
			$location .= '?' . preg_replace('/%5B\d+\%5D=/', '%5B%5D=', http_build_query($query));
		}

		return (string) $location;
	}


	/**
	 *
	 */
	protected function filter($value)
	{
		if (is_array($value)) {
			foreach ($value as $key => $subvalue) {
				$value[$key] = $this->filter($subvalue);
			}

			$value = array_filter($value);

			if (count($value)) {
				return $value;
			}
		}

		if (empty($value)) {
			return NULL;
		}

		return (string) $value;
	}
}
