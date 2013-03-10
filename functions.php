<?php
/**
* Copyright © 2013, Kasimir Pihlasviita, http://kasimir.pihlasviita.fi/
* Licensed under ISC license, see LICENSE.txt
* 
* Piste - PHP-ized Stylesheets
* Helper functions
*/

$px_base = 16;
/**
* Helps with relations between sizes 
*/
function px_size($factor = 1) {
	global $px_base;
	return ($px_base * $factor) . 'px';
}


/**
* Converts css-gradient to base64 encoded data-URI svg-document for use as
* image in CSS's url()
* 
* @param integer $angle direction of the gradient in degrees, from 0 to 360 in
* 45 degree steps 
*
* @param array $stops each stop is an array, first value is color, second is 
* position; colors may be hex or rgba, position should be %-value
* e.g. array('#FF9900', '42%'), array('rgba(255,90,0,0.75)', '100%')
*/
function svg_linear_gradient($angle, $stops) {
	$coordinates = array(
		'0 100 0 0',    //   0 deg
		'0 100 100 0',  //  45 deg
		'0 0 100 0',    //  90 deg
		'0 0 100 100',  // 135 deg
		'0 0 0 100',    // 180 deg
		'100 0 0 100',  // 225 deg
		'100 0 0 0',    // 270 deg
		'100 100 0 0',  // 315 deg
		'0 100 0 0');   // 360 deg
	// convert angle to index of coordinate point array
	$c = $coordinates[round(($angle % 360) / 45)];
	$c = explode(' ', $c);
	$svg = 
		'<?xml version="1.0"?>'
		. '<svg xmlns="http://www.w3.org/2000/svg">'
		. '<defs>'
		. '<linearGradient id="g" x1="' . $c[0]
		. '%" y1="' . $c[1]
		. '%" x2="' . $c[2] 
		. '%" y2="' . $c[3] . '%" spreadMethod="pad">';
	foreach ($stops as $i) {
		$i[0] = trim($i[0]);
		$opa = 1;
		// convert rgba colors to rgb and stop-opacity
		if (strpos($i[0], 'rgba(') === 0) {
			$opa = substr($i[0], strrpos($i[0], ',')  + 1, -1);
			$i[0] = substr($i[0], 0, strrpos($i[0], ',')) . ')';
			$i[0] = str_replace('rgba', 'rgb', $i[0]);
		}
		$svg .= 
			'<stop offset="' . $i[1] 
			. '" stop-color="' . $i[0] 
			. '" stop-opacity="' . $opa . '"/>';
	}
	$svg .= 
		'</linearGradient>'
		. '</defs>'
		. '<rect width="100%" height="100%" style="fill:url(#g);" />'
		. '</svg>';
	return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
* Generates linear gradient background images as standard version together with
* vendor prefixed, legacy webkit and svg versions. While CSS allows leaving out
* the angle and positions of color stops, this function requires them.
*
* @param mixed $angle direction of the gradient
* either in degrees, from 0 to 360 
* 0 = "to top", 45 = "to top right", 90 = "to right" etc.
* or as a keyword or keyword pair with or without preciding "to"
* @param array $stops each stop is an array, first value is color, second is 
* position; colors may be hex or rgba, position should be %-value (for
* compatibility with svg)
* e.g. array('#FF9900', '42%'), array('rgba(255,90,0,0.75)', '100%')
*/
function linear_gradient($angle, $stops) {
	$invert = true;
	$degrees = array(
		'top' => 0,
		'top right' => 45,
		'right top' => 45,
		'right' => 90,
		'bottom right' => 135,
		'right bottom' => 135,
		'bottom' => 180,
		'bottom left' => 225,
		'left bottom' => 225,
		'left' => 270,
		'top left' => 315,
		'left top' => 315);
	$keywords = array_flip($degrees);
	if (!is_int($angle)) {
		$angle = trim(strtolower($angle));
		if (substr($angle, 0, 3) == 'to ') {
			$angle = substr($angle, 3);
			$invert = false;
		}
		$angle = $degrees[$angle];
		if ($invert) {
			$angle = ($angle > 180) ? $angle - 180 : $angle + 180;
		}
	}
	// legacy webkit and svg don't use angle but keywords, so angle needs to be
	// normalized to multiples of 45
	$normalized_angle = floor($angle / 45) * 45;
	$invert_angle = ($angle > 180) ? $angle - 180 : $angle + 180;
	$invert_normalized_angle = floor($invert_angle / 45) * 45;

	$css = '';

	// svg for IE9
	$css .= 
		'background-image: url('
		. svg_linear_gradient($normalized_angle, $stops)
		. ");\n"; 

	// for leagacy webkit syntax
	$css .=
		'background-image: -webkit-gradient(linear,'
		. $keywords[$invert_normalized_angle]
		. ','
		. $keywords[$normalized_angle];
	foreach ($stops as $i) {
		$css .= ',color-stop('. $i[1] . ',' . $i[0] .')';
	}		
	$css .= ");\n";
	
	// property value string for modern vendor versions and standard version
	$prop = $angle . 'deg';
	foreach ($stops as $i) {
		$prop .= ',' . $i[0] . ' ' . $i[1];
	}
	// vendor prefixed and standard versions
	foreach (array('-webkit-', '-moz-', '-ms-', '-o-', '') as $i) {
		$css .=  'background-image: ' . $i . 'linear-gradient(' . $prop . ");\n";
	}
	return $css;
}


