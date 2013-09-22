<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Copyright (C) 2011 by Joseph Wensley
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Img Resize Plugin
 *
 * @package    ExpressionEngine
 * @subpackage Addons
 * @category   Plugin
 * @author     Joseph Wensley
 * @link       http://josephwensley.com
 * @version    2.3.2
 */

$plugin_info = array(
	'pi_name'       => 'Img Resize',
	'pi_version'    => '2.3.2',
	'pi_author'     => 'Joseph Wensley',
	'pi_author_url' => 'http://josephwensley.com',
	'pi_description'=> 'Resizes images',
	'pi_usage'      => Img_resize::usage()
);

/**
 * < EE 2.6.0 backward compatibility
 */
if (! function_exists('ee'))
{
	function ee()
	{
		static $EE;
		if (! $EE) $EE = get_instance();
		return $EE;
	}
}

// Require our image library which does most of the work
require_once("library/img_resize_image.php");

class Img_resize {

	public $return_data;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		// Options
		$options = array(
			'base_path'     => FCPATH,
			'base_url'      => ee()->config->item('base_url'),
			'cache_path'    => FCPATH.'/images/resized',
			'cache_url'     => ee()->config->item('base_url').'/images/resized',
			'cache'         => TRUE,
			'handle_retina' => TRUE,
			'just_url'      => FALSE,
			'quality'       => 100,
			'sharpen'       => FALSE,
			'urldecode'     => TRUE,
		);

		foreach ($options AS $key => $value)
		{
			// Check for a config item
			if (ee()->config->item("img_resize:{$key}"))
			{
				$options[$key] = ee()->config->item("img_resize:{$key}");
			}

			// Check for a tag parameter
			if (ee()->TMPL->fetch_param($key))
			{
				$param = ee()->TMPL->fetch_param($key);

				switch ($param)
				{
					case 'yes':
						$options[$key] = TRUE;
						break;

					case 'no':
						$options[$key] = FALSE;
						break;

					default:
						$options[$key] = $param;
						break;
				}
			}
		}

		// Get Src and Width/Height Params
		$src        = ee()->TMPL->fetch_param('src');
		$height     = ee()->TMPL->fetch_param('height');
		$width      = ee()->TMPL->fetch_param('width');
		$max_height = ee()->TMPL->fetch_param('max_height');
		$max_width  = ee()->TMPL->fetch_param('max_width');

		// Is there a source?
		if ( ! $src)
		{
			ee()->TMPL->log_item("Img Resize: No source specified");
			$this->return_data = '';
			return;
		}

		// We need a width or height
		if ( ! $height AND ! $width AND ! $max_height AND ! $max_width)
		{
			ee()->TMPL->log_item("Img Resize: No width or height specified");
			$this->return_data = '';
			return;
		}

		// Try to create an image
		try {
			$img_options = $options;

			$image = new Img_resize_image($src, $img_options);
		} catch (Exception $e) {
			$message = $e->getMessage();

			ee()->TMPL->log_item("Img Resize: {$message}");
			$this->return_data = '';
			return;
		}

		// Tag Attributes
		$attr['alt']   = ee()->TMPL->fetch_param('alt');
		$attr['title'] = ee()->TMPL->fetch_param('title');
		$attr['class'] = ee()->TMPL->fetch_param('class');
		$attr['id']    = ee()->TMPL->fetch_param('id');

		// Resize the image
		$max    = ($max_width OR $max_height) ? TRUE : FALSE;
		$width  = ($max_width) ? $max_width : $width;
		$height = ($max_height) ? $max_height : $height;
		$image->resize($width, $height, $max);

		// Handle retina images
		if ($image->isRetina() AND $options['handle_retina'] === TRUE)
		{
			$rimg_options = $options;
			$rimg_options['retina'] = TRUE;

			if (ee()->TMPL->fetch_param('retina_quality'))
			{
				$rimg_options['quality'] = ee()->TMPL->fetch_param('retina_quality');
			}

			$d = $image->getDimensions();

			$retina = Img_resize_image::load($src, $rimg_options)->resize($d['out_w'] * 2, $d['out_h'] * 2, $max);

			$attr['data-retina'] = $retina->getURL();
		}

		// Set our output, either the img tag or the image url
		if ($options['just_url'] === TRUE)
		{
			$this->return_data = $image->getURL();
			return;
		}
		else
		{
			$this->return_data = $image->buildTag($attr);
			return;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Plugin Usage
	 */
	public static function usage()
	{
		ob_start();
?>
Requirements
============
- ExpressionEngine 2.x
- PHP 5+
- GD2 or Imagick

Parameters
==========

Required
--------
The src and at least 1 dimension parameter are required

**src:** Path to the image can be a full or relative (to the index.php) system path or a local url
**width and/or height:** Absolute width or height to resize to
**max_width and/or max_height:** Maximum width or height to resize to

Optional
---------
The following options can also be set globaly in a config file using like $config['img_resize:param'] = 'VALUE'

**quality:** The quality of the resized image between 0-100. Default is 100.
**retina_quality:** The quality of the resized retina image between 0-100. Defaults to whatever **quality** is.
**just_url:** Set this to 'no' to only return the URL to the image
**sharpen:** Setting this to 'no' will cause images to be sharpened after they are resized
**urldecode:** Setting to 'no' will disable decoding of the src url
**handle_retina:** Set to 'no' to disable

**These should only be set if you know what you are doing**
**base_url:** URL to where your images are stored, default is your base_url
**base_path:** The base path to where your images are stored, this is used to determine the path to your images when using relative image paths
**cache_path:** Full path to where your images are cached, default is FCPATH/images/resized
**cache_url:** URL to where your images are cached, default is your base_path + /images/resized

Attributes
----------
These will be set as attributes on the img tag

**alt**
**title**
**id**
**class**


Example Usage
=============

	{exp:img_resize src="/assets/img/imagename.jpg" width="100" height="100" alt="Some alt text"}
	// Outputs
	<img src="http://domain.com/images/resized/assets/img/imagename_100x100.jpg" width="100" height="100" alt="Some alt text">

	{exp:img_resize src="/assets/img/imagename.jpg" width="100" height="100" just_url="yes"}
	// Outputs
	http://domain.com/images/resized/assets/img/imagename_100x100.jpg

Retina Handling
===============
If you have a 100x100 image named like image@2x.png and resize to 25x25 the plugin will generate two images.

The first image will be 25x25 and named like image_25x25.png
The second image will be 50x50 and named like image_25x25@2x.png

This should provide support for many retina handling methods that use @2x as an identifer, like http://retinajs.com/.

A data-retina attribute will also be set on the img tag containing the url to the retina image.

Changelog
=========
2.3.2

+ Fix an issue with transparent GIFs

2.3.1

+ Define __DIR__ for PHP versions < 5.3

2.3.0

+ Added retina_quality parameter so you can seperately set the retina image quality if desired

2.2.0

+ Improve handling of remote images, should be much faster now

2.1.1

+ Fix a bug that could cause retina images could be sized and named wrong

2.1.0

+ Should no longer load images on the same domain as if they were remote when using full urls
+ Added base_url paremeter that you can set if you're images are on a sub-domain or something similar to make them load from the filesystem instead of remotely
+ Fix a bug with max_width and max_height

2.0.0

+ Updating to this version may break things for you, be sure to test on a non-live site.
+ Add support for retina images, if an image is named with @2x the plugin will generate both retina and non-retina versions.
+ Added the ability to set some options in a config file
+ Remove dir param
+ Refactored most of the heavy lifting code into a seperate class

1.2.1

+ Change a setting when resizing using Imagick that caused inconsistent behaviour between it and GD

1.2.0

+ Attempt to increase memory limit before resizing
+ Add urldecode parameter
+ Suppress fopen errors
+ Bugfixes

1.1.1

+ Add GIF support
+ Bugfix

1.1.0

+ Add option to sharpen images after resizing
+ Use Imagick if available
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}


/* End of file pi.img_resize.php */
/* Location: /system/expressionengine/third_party/img_resize/pi.img_resize.php */