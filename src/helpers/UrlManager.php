<?php

namespace SmartGrid\helpers;

class UrlManager
{

	public static function getActiveUrl()
	{
		$pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

	public static function getQueryParams($url = '')
	{
		if ($url === '') {
			$url = self::getActiveUrl();
		}
		$parse = self::parse($url);

		if (empty($parse['query'])) {
			return [];
		} else {
			return $parse['query'];
		}

	}

	public static function route($route, $params = [])
	{
		$url = self::parse($route);
		if (!empty($params)) {
			$url['query'] = isset($url['query']) ? $url['query'] : [];
			$url['query'] = ValueFormatter::extend($url['query'], $params);
		}
		/*
		  if (!empty($removeParams)) {
		  foreach ($removeParams as $key => $value) {
		  if (isset($url['query'][$value])) {
		  unset($url['query'][$value]);
		  }
		  }
		  }
		 */
		$url = self::build($url);
		return $url;

	}

	public static function parse($url)
	{
		$url = parse_url($url);
		if (!empty($url['query'])) {
			parse_str($url['query'], $query);
			$url['query'] = $query;
		}
		return $url;

	}

	public static function build($parsed_url)
	{
		$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
		$host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
		$port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
		$user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
		$pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
		$pass = ($user || $pass) ? "$pass@" : '';
		$path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$query = isset($parsed_url['query']) ? '?' . http_build_query($parsed_url['query']) : '';
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
		return "$scheme$user$pass$host$port$path$query$fragment";

	}
}