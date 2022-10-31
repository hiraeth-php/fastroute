<?php

namespace Hiraeth\FastRoute;

use SplFileInfo;
use Hiraeth\Routing;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 *
 */
class UrlGenerator implements Routing\UrlGenerator
{
	/**
	 * @var Router
	 */
	protected $router;


	/**
	 * @param Router $router
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
		$mapping  = array();
		$fragment = NULL;

		if (!$location) {
			$location = '';
		}

		if ($location instanceof Request) {
			return $this($location->getUri()->getPath(), $params + $location->getQueryParams());
		}

		if ($location instanceof SplFileInfo) {
			return $this($location->getPathName());
		}

		if ($fragment = parse_url($location, PHP_URL_FRAGMENT)) {
			$location = str_replace('#' . $fragment, '', $location);
		}

		if ($mask) {
			foreach ($this->router->getMasks() as $from => $to) {
				$location = $this->router->mask($location, $from, $to);
			}
		}

		if (preg_match_all('/{([^:}]+)(?::([^}]+))?}/', $location, $matches)) {
			$mapping = array_combine($matches[1], $matches[2]) ?: array();
		}

		if ($provider) {
			foreach (array_diff(array_keys($mapping), array_keys($params)) as $name) {
				$params[$name] = $provider->getRouteParameter($name);
			}
		}

		foreach (array_intersect(array_keys($mapping), array_keys($params)) as $name) {
			$type  = $mapping[$name];
			$value = $params[$name];

			if (isset($this->router->getTransformers()[$type])) {
				$value = $this->router->getTransformers()[$type]->toUrl($name, $value, $params);
			}

			$location = str_replace(
				$type ? '{' . $name . ':' . $type . '}' : '{' . $name . '}',
				urlencode($value),
				$location
			);

			unset($params[$name]);
		}

		if ($query = $this->filter($params)) {
			$location .= '?' . preg_replace('/%5B\d+\%5D=/', '%5B%5D=', http_build_query($query));
		}

		return (string) $location . rtrim('#' . $fragment, '#');
	}


	/**
	 * @param mixed[] $value The parameters to convert to URL values
	 * @return mixed[] The string values of the parameters in the URL, null/empty strings stripped
	 */
	protected function filter($value)
	{
		foreach ($value as $key => $sub_value) {
			$value[$key] = (string) $sub_value;

			if (!strlen($value[$key])) {
				$value[$key] = NULL;
			}
		}

		$value = array_filter($value, function ($value) {
			return !is_null($value);
		});

		return count($value)
			? $value
			: NULL;
	}
}
