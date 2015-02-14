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
* Piste - PHP-ized Stylesheets
*
* Script for combining, minifying and caching CSS-files.
* Just use any PHP in the CSS-files: variables, expressions, functions...
* Use PHP-files for declaring varibles and functions.
*
* Specify with querystring what CSS- and PHP-files to include, or leave it out
* to include all from current directory.
*/

$css_includes = array(); // CSS-files for output
$php_includes = array(); // PHP-files for configuration

// all CSS- and PHP-files in current directory are available, except index.php
// glob orders them alphabetically
$css_available = glob('*.css');
$php_available = array_diff(glob('*.php'), array('index.php'));

// options can be overridden in included PHP-files
$options = array(
	'include_component_names' => true,
	'minify' => true);


if (!empty($_SERVER['QUERY_STRING'])) {
	// the component files comma separated in querystring
	$components = explode(',', $_SERVER['QUERY_STRING']);
	foreach ($components as $i) {
		$found = false;
		$ext = substr($i, -4);
		if ($ext == '.css') {
			if (in_array($i, $css_available)) {
				$css_includes[] = $i;
				$found = true;
			}
		}
		else if ($ext == '.php') {
			if (in_array($i, $php_available)) {
				$php_includes[] = $i;
				$found = true;
			}
		}
		else { 
			if (in_array($i . '.css', $css_available)) {
				$css_includes[] = $i . '.css';
				$found = true;
			}
			if (in_array($i . '.php', $php_available)) {
				$php_includes[] = $i . '.php';
				$found = true;
			}
		}
		if (!$found) {
			header('HTTP/1.1 404 Not Found');
			exit();
		}
 	}
}
else {
	// if no querystring, include all available files
	$css_includes = array_merge($css_includes, $css_available);
	$php_includes = array_merge($php_includes, $php_available);
}

// make a hash of cache file name lest it get too long
$cache_file = 
	__DIR__ . '/.cache/'
	. substr(
		str_replace(
			array('+', '/'), // '+' and '/' are not good in filenames,
			array('-', '_'), // repalce them with '-' and '_'
			base64_encode(
				hash('sha512', 	implode($php_includes) . implode($css_includes), true)
			)
		), 0, -2); // 512 bit makes '==' at the tail	

if (file_exists($cache_file)) {
	// rebuild the cache file if any of the files include
	// is more recent than cache file
	$cache_file_time = filemtime($cache_file);
	$rebuild = false;
	foreach ($css_includes as $i) {
		if (filemtime(__DIR__ . '/' . $i) > $cache_file_time) {
			$rebuild = true;
			break;
		}
	}
	if (!$rebuild) {		
		foreach ($php_includes as $i) {
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

foreach ($php_includes as $i) {
	include __DIR__ . '/' . $i;
}

ob_start();
foreach ($css_includes as $i) {
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