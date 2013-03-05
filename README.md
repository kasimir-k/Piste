# PHP-ized Style Sheets - Piste

[Piste](http://en.wiktionary.org/wiki/piste#Finnish), Finnish noun: point (something tiny, zero-dimensional object)

With one tiny PHP script you'll get most of the power of [LESS](http://lesscss.org/) and [Sass](http://sass-lang.com/), but in much simpler package. You'll also get automatically refreshed cache of combined and minified CSS-files. And if you want something more than simple variables or functions, you can get it, if it can be done in PHP.

As an extra bonus, Piste doesn't introduce a new language to learn: it's all plain old CSS and PHP.

If you want to KISS, choose Piste! 

## Combining, minifying and caching style sheets
First put the Piste's index.php in the directory containing your CSS-files - the config.php file should go there too.  

Even if you use just regular CSS without any PHP code, Piste gives you automatic combining and minifying of the CSS-files. You can specify the files you want to combine in the querystring, or let Piste use all CSS-files in the directroy in alphabetic order.

Examples:

	<link rel="stylesheet" type="text/css" href="css/" />
	<link rel="stylesheet" type="text/css" href="css/?foo,bar" />

Whatever combinations you choose Piste will cache them. It automatically rebuilds the cached files, when config.php or any of the component CSS-files is updated.

If you want variables or functions, declare them in config.php, and use PHP echo in the CSS-files. You could of course declare them in the CSS-files themselves, but most of the time it's clearer and easier to maintain them in config.php.

## Variables
Just echo variables declared in config.php:

	#header {
		color: <?= $header_color ?>;
	}

## Functions, mixins and operations
Just use PHP to your heart's content!

In config.php:

	function rounded_corners($radius = '5px') {
		return "
		-webkit-border-radius: $radius;
		-moz-border-radius: $radius;
		-ms-border-radius: $radius;
		-o-border-radius: $radius;
		border-radius: $radius;";
	}

	$common_head_foot_styles = "
		background: #DAD url('../img/head_foot_bg.png');
		font: 0.7em bold sans-serif;		
	";

In style sheets: 

	#header {
		<?= $common_head_foot_styles ?>
		<?= rounded_corners() ?>
	}
	#footer {
		<?= $common_head_foot_styles ?>
		font-weight: normal;
		<?= rounded_corners('0.7em') ?>
	}

Piste doesn't have its own functions for manipulatin colors etc. just use your old PHP favorites or [search the fine Google](http://www.google.com/search?q=php+color+manipulation) for new ones.

## Nested rules
Nesting rules does not help simplicity. 

You loose control over selectors and their specifity. They may become too long and too specific, overriding things you don't want to. 

Selectors created by nesting also perform badly. You end up easily with many levels of descendant tag selectors - the most expensive type of CSS selectors. 

And really - they are "Cascading Style Sheets", not "Nested Style Sheets"! Cascade might have its drawbacks, but IMHO nesting is bad way to address them.

## Limitations and requirements
Piste reserves filenames index.php, config.php and directory .cache/ for its own use. The CSS-files should have extension ".css" and they should not have ".css" elsewhere in their name to ensure uniquenes of cache files. 

As Piste will output with "; charset=UTF-8" in the Content-Type header, all CSS-files should be encoded with UTF-8, ISO-8859-1 or ASCII.

No IE-hacks are respected in minification. Having that said, Piste minifies in a rather conservative manner, and comments can optionally be left intact, so you might get away with some IE-hacks. But you really should never use them anyway!

## Thanks to:
- [LESS](http://lesscss.org/), [Sass](http://sass-lang.com/), [Minify](http://code.google.com/p/minify/) and many other projects for inspiration 
- Barney Carroll for good comments and coming up with perfect name
