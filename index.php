<?php 
/**
* Copyright © 2013, Kasimir Pihlasviita, http://kasimir.pihlasviita.fi/
*
* Permission to use, copy, modify, and/or distribute this software for any
* purpose with or without fee is hereby granted, provided that the above
* copyright notice and this permission notice appear in all copies.
*
* THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
* REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
* AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
* INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
* LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
* OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
* PERFORMANCE OF THIS SOFTWARE. 
*/

/**
* PHP enhanced style sheets - Psst
* Script for combining, minifying and caching CSS-files.
* Just use any PHP in the CSS-files: variables, expressions, functions...
* Put variable and function declarations in config.php for automatic inclusion
*/


$components = array(); // the CSS-files
if (!empty($_SERVER['QUERY_STRING'])) {
	// the component CSS-files comma separated in querystring
	$components = explode(',', $_SERVER['QUERY_STRING']);
	for ($i = 0, $ii = count($components); $i < $ii; $i++) {
		// components in querystring optionally postfixed with ".css"
		if (substr($components[$i], -4) != '.css') {
			$components[$i] .= '.css';
		}
		// remove components that don't exist
		if (!file_exists(__DIR__ . '/' . $components[$i])) {
			unset($components[$i]);
		}
	}
}
else {
	// if no components in querystring, use all in current directory
	// in alphabetical order
	$components = glob('*.css');
}
if (empty($components)) {
	exit;
}

$config_file = __DIR__ . '/config.php';
$cache_file = __DIR__ . '/.cache/' . implode('-', $components);

if (file_exists($cache_file)) {
	// rebuild the cache file if any of the CSS-files or config file
	// is more recent than cache file
	$cache_file_time = filemtime($cache_file);
	$rebuild = false;
	if (file_exists($config_file) && filemtime($config_file) > $cache_file_time) {
		$rebuild = true;
	}
	else {
		foreach ($components as $i) {
			if (filemtime(__DIR__ . '/' . $i) > $cache_file_time) {
				$rebuild = true;
				break;
			}
		}
	}
	if (!$rebuild) {
		if (
			!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])
			&&
			strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $cache_file_time
		) {
			header('HTTP/1.1 304 Not Modified');
			exit();
		}
		header('Content-Type: text/css; charset=UTF-8');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $cache_file_time) . ' GMT');
		header('Content-Length: ' . filesize($cache_file));
		readfile($cache_file);
		exit();
	}
	unlink($cache_file);
}

// options may be overridden in config.php
$options = array(
	'include_component_names' => true,
	'minify' => true);

if (file_exists($config_file)) {
	include $config_file;
}
ob_start();
foreach ($components as $i) {
	if ($options['include_component_names']) {
		echo "/*!\n$i\n*/";
	}
	include __DIR__ . '/' . $i;
}
$css = ob_get_contents();
ob_end_clean();

// fairly conservative minification
if ($options['minify']) {
	$css = trim($css);
	$css = str_replace("\r\n", "\n", $css);
	// remove all comments, except important ones
	$css = preg_replace('#\s*/\*[^!].*?\*/\s*#s', '', $css);
	// remove whitespace around braces and semicolons
	$css = preg_replace('/\s*([{};])\s*/', '$1', $css);
	// multiple semicolons to one
	$css = preg_replace('/;+/', ';', $css);
	// remove block's last semicolon
	$css = str_replace(';}', '}', $css);
	// remove whitespace between rules and colons
	$css = preg_replace('/
		([{;])		# start of block or end of previous rule
		([^\s:]+)	# property (not whitespace nor colon)
		\s*
		:
		\s*
	/x', '$1$2:', $css);
	// remove whitespace around commas in selector groups
	$css = preg_replace('/\s*,\s*(?=[^}]+{)/', ',', $css);
}

if (!file_exists(__DIR__ . '/.cache')) {
	mkdir(__DIR__ . '/.cache');
}
file_put_contents($cache_file, $css);

header('Content-Type: text/css; charset=UTF-8');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($cache_file)) . ' GMT');
header('Content-Length: ' . filesize($cache_file));
readfile($cache_file);