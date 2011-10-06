<?php
interface JComponentRouterInterface {
	/**
	 * Build method for URLs
	 *
	 * @param array $query Array of query elements
	 *
	 * @return array Array of URL segments
	 */
	function build(&$query);

	/**
	 * Parse method for URLs
	 *
	 * @param array $segments Array of URL string-segments
	 *
	 * @return array Associative array of query values
	 */
	function parse(&$segments);
}
