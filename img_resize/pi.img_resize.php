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
 * @version    1.2.1
 */

$plugin_info = array(
	'pi_name'       => 'Img Resize',
	'pi_version'    => '1.2.1',
	'pi_author'     => 'Joseph Wensley',
	'pi_author_url' => 'http://josephwensley.com',
	'pi_description'=> 'Resizes images',
	'pi_usage'      => Img_resize::usage()
);


require_once("library/img_resize_image.php");

class Img_resize {

	public $return_data;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		// Retrieve the parameters
		$src        = $this->EE->TMPL->fetch_param('src');
		$height     = $this->EE->TMPL->fetch_param('height');
		$width      = $this->EE->TMPL->fetch_param('width');
		$max_height = $this->EE->TMPL->fetch_param('max_height');
		$max_width  = $this->EE->TMPL->fetch_param('max_width');

		$cache_dir = $this->EE->TMPL->fetch_param('dir') ? $this->EE->TMPL->fetch_param('dir') : '/images/resized/';
		$just_url  = $this->EE->TMPL->fetch_param('just_url') == 'yes' ? TRUE : FALSE;
		$urldecode = $this->EE->TMPL->fetch_param('urldecode') == 'no' ? FALSE : TRUE;
		$cache     = $this->EE->TMPL->fetch_param('cache') == 'no' ? FALSE : TRUE;
		$sharpen   = $this->EE->TMPL->fetch_param('sharpen') == 'yes' ? TRUE : FALSE;
		$quality   = (int) $this->EE->TMPL->fetch_param('quality') ? $this->EE->TMPL->fetch_param('quality') : 100;

		if ( ! $src)
		{
			$this->EE->TMPL->log_item("Img Resize: No source specified");
			$this->return_data = '';
			return;
		}

		if ( ! $height AND ! $width AND ! $max_height AND ! $max_width)
		{
			$this->EE->TMPL->log_item("Img Resize: No width or height specified");
			$this->return_data = '';
			return;
		}

		try {
			$options = array(
				'base_path' => FCPATH,
				'base_url'  => $this->EE->config->item('base_url'),
				'cache'     => $cache,
				'cache_dir' => $cache_dir,
				'quality'   => $quality,
				'sharpen'   => $sharpen,
				'urldecode' => $urldecode,
			);

			$image = new Img_resize_image($src, $options);
		} catch (Exception $e) {
			$this->EE->TMPL->log_item("Img Resize: ".$e->getMessage());
			$this->return_data = '';
			return;
		}

		// Tag Attributes
		$attr['alt']   = $this->EE->TMPL->fetch_param('alt');
		$attr['title'] = $this->EE->TMPL->fetch_param('title');
		$attr['class'] = $this->EE->TMPL->fetch_param('class');
		$attr['id']    = $this->EE->TMPL->fetch_param('id');

		// Resize the image
		$max = ($max_width OR $max_height) ? TRUE : FALSE;
		$image->resize($width, $height, $max);

		if ($just_url == TRUE)
		{
			$this->return_data = $image->get_url();
			return;
		}
		else
		{
			$this->return_data = $image->build_tag($attr);
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
**src:** Path to the image can be a full or relative (to the index.php) system path or a local url
**dir (optional):** Relative path to where you want resized images to be stored. Default is /images/resized/
**width and/or height:** Absolute width or height to resize to
**max_width and/or max_height:** Maximum width or height to resize to
**alt (optional):** Alt text for the img tag
**quality (optional):** The quality of the resized image between 0-100. Default is 100.
**just_url (optional):** Set this to 'no' to only return the URL to the image
**sharpen (optional):** Setting this to 'no' will cause images to be sharpened after they are resized
**urldecode (optional):** Setting to 'yes' will disable decoding of the src url

Example Usage
=============

	{exp:img_resize src="/assets/img/imagename.jpg" width="100" height="100" alt="Some alt text"}
	// Outputs
	<img src="http://domain.com/images/resized/assets/img/imagename_100x100.jpg" width="100" height="100" alt="Some alt text">

	{exp:img_resize src="/assets/img/imagename.jpg" width="100" height="100" just_url="yes"}
	// Outputs
	http://domain.com/images/resized/assets/img/imagename_100x100.jpg

Changelog
=========
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

1.0.3
+ Fix an issue with paths on Windows

1.0.2
+ Rewrite dimension calculation code

1.0.1
+ Bugfixes

1.0
+ Initial Release
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}


/* End of file pi.img_resize.php */
/* Location: /system/expressionengine/third_party/img_resize/pi.img_resize.php */