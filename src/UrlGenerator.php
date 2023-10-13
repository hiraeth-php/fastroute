<?php

namespace Hiraeth\FastRoute;

use SplFileInfo;
use Hiraeth\Routing;
use Hiraeth\Routing\Route;
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

		if ($location instanceof Route) {
			$provider = new class($location->getParameters()) implements ParamProvider
			{
				protected $params = array();

				public function __construct($params)
				{
					$this->params = $params;
				}

				public function getRouteParameter($name)
				{
					return $this->params[$name] ?? NULL;
				}
			};

			return $this($location->getTarget(), array(), $provider);
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

		if (!parse_url($location, PHP_URL_QUERY)) {
			$leftover = $this->filter($params);

			if ($leftover) {
				$location = sprintf(
					'%s?%s',
					$location,
					preg_replace('/%5B\d+\%5D=/', '%5B%5D=', http_build_query($leftover))
				);
			}
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
			if (is_array($sub_value)) {
				$value[$key] = $this->filter($sub_value);

				if (empty($value[$key])) {
					$value[$key] = NULL;
				}

			} else {
				$value[$key] = trim((string) $sub_value);

				if ($value[$key] === '') {
					$value[$key] = NULL;
				}
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
