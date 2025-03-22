<?php

namespace Hiraeth\FastRoute;

use SplFileInfo;
use Hiraeth\Http;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

/**
 *
 */
class UrlGenerator implements Http\UrlGenerator
{
	/**
	 * @var Collector
	 */
	protected $collector;


	/**
	 * @param Collector $collector
	 */
	public function  __construct(Collector $collector)
	{
		$this->collector = $collector;
	}


	/**
	 * @param Request|SplFileInfo|string $location
	 * @param array<mixed> $params
	 */
	public function __invoke($location, array $params = [], ?ParamProvider $provider = NULL, ?bool $mask = TRUE): string
	{
		if ($location instanceof Request) {
			$params += $location->getQueryParams();

			return $this->call($location->getUri()->getPath(), $params);
		}

		if ($location instanceof SplFileInfo) {
			return $this->call($location->getPathName());
		}

		$fragment = parse_url($location, PHP_URL_FRAGMENT);
		$mapping  = $this->collector->link($location, $params);

		if (!$location) {
			$location = '';
		}

		if ($fragment) {
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

			if ($mapping) {
				$mapping = $this->collector->link($location, $params);
			}

			if ($mapping) {
				throw new RuntimeException(sprintf(
					'Location "%s" has unresolved parameters',
					$location
				));
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

		$value = array_filter($value, fn($value) => !is_null($value));

		return count($value)
			? $value
			: NULL;
	}
}
