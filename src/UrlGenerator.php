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
	 * @var Collector
	 */
	protected $collector;


	/**
	 * @param Router $router
	 */
	public function  __construct(Collector $collector)
	{
		$this->collector = $collector;
	}


	/**
	 *
	 */
	public function __invoke($location, array $params = array(), ?ParamProvider $provider = NULL, ?bool $mask = TRUE): string
	{
		if ($location instanceof Request) {
			$params = $params + $location->getQueryParams();

			return $this->make($location->getUri()->getPath(), $params);
		}

		if ($location instanceof SplFileInfo) {
			return $this->make($location->getPathName());
		}

		$fragment = NULL;
		$mapping  = $this->link($location, $params);

		if (!$location) {
			$location = '';
		}

		if ($fragment = parse_url($location, PHP_URL_FRAGMENT)) {
			$location = str_replace('#' . $fragment, '', $location);
		}

		if ($mask) {
			foreach ($this->collector->getMasks() as $from => $to) {
				$location = $this->collector->mask($location, $from, $to);
			}
		}

		if ($provider) {
			foreach (array_diff(array_keys($mapping), array_keys($params)) as $name) {
				$params[$name] = $provider->getRouteParameter($name);
			}
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
	 *
	 */
	public function call(): string
	{
		return $this(...func_get_args());
	}


	/**
	 *
	 */
	public function link(string &$location, array &$params = array())
	{
		$mapping = array();

		if (preg_match_all('/{([^:}]+)(?::([^}]+))?}/', $location, $matches)) {
			$mapping = array_combine($matches[1], $matches[2]) ?: array();
		}

		foreach (array_intersect(array_keys($mapping), array_keys($params)) as $name) {
			$type  = $mapping[$name];
			$value = $params[$name];

			if (isset($this->collector->getTransformers()[$type])) {
				$value = $this->collector->getTransformers()[$type]->toUrl($name, $value, $params);
			}

			$location = str_replace(
				$type ? '{' . $name . ':' . $type . '}' : '{' . $name . '}',
				urlencode($value),
				$location
			);

			unset($params[$name]);
		}

		return $mapping;
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
