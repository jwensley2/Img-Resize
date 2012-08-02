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
 * @version    1.0.2
 */

$plugin_info = array(
	'pi_name'       => 'Img Resize',
	'pi_version'    => '1.0.2',
	'pi_author'     => 'Joseph Wensley',
	'pi_author_url' => 'http://josephwensley.com',
	'pi_description'=> 'Resizes images',
	'pi_usage'      => Img_resize::usage()
);


class Img_resize {

	public $return_data;

	// Defaults
	public $cache_dir = '/images/resized/';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		if (extension_loaded('gd2') AND function_exists('gd_info'))
		{
			$this->EE->TMPL->log_item("Img Resize: GD must be installed to use Img Resize");
			$this->return_data = '';
			return;
		}

		// Retrieve the parameters
		$src        = $this->EE->TMPL->fetch_param('src');
		$height     = $this->EE->TMPL->fetch_param('height');
		$width      = $this->EE->TMPL->fetch_param('width');
		$max_height = $this->EE->TMPL->fetch_param('max_height');
		$max_width  = $this->EE->TMPL->fetch_param('max_width');
		$quality    = $this->EE->TMPL->fetch_param('quality') ? $this->EE->TMPL->fetch_param('quality') : 100;
		$just_url   = $this->EE->TMPL->fetch_param('just_url') ? TRUE : FALSE;
		$cache      = $this->EE->TMPL->fetch_param('cache') == 'off' ? FALSE : TRUE;
		$cache_dir  = $this->EE->TMPL->fetch_param('dir') ? $this->EE->TMPL->fetch_param('dir') : $this->cache_dir;

		// Tag Attributes
		$attr_alt   = $this->EE->TMPL->fetch_param('alt');
		$attr_title = $this->EE->TMPL->fetch_param('title');
		$attr_class = $this->EE->TMPL->fetch_param('class');
		$attr_id    = $this->EE->TMPL->fetch_param('id');

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

		list($src_path_full, $src_path_rel, $src_filename, $src_extension, $is_remote) = $this->get_path_info($src);
		unset($src);

		// Try and read the image
		if (fopen($src_path_full, 'r'))
		{
			list($src_width, $src_height, $image_type) = getimagesize($src_path_full);
		}
		else
		{
			$this->EE->TMPL->log_item("Img Resize: Could not open image file");
			$this->return_data = '';
			return;
		}

		if ($max_width OR $max_height)
		{
			$d = $this->calculate_dimensions($max_width, $max_height, $src_width, $src_height, TRUE);
		}
		else
		{
			$d = $this->calculate_dimensions($width, $height, $src_width, $src_height);
		}

		// Determine the url and path to the cache folder
		$base_url   = 'http://'.$_SERVER['SERVER_NAME'];
		$cache_url  = $base_url.$cache_dir;
		$cache_path = FCPATH.$cache_dir;

		$out_filename = "{$src_filename}_{$d['out_w']}_{$d['out_h']}.$src_extension";

		$out_dir  = $this->EE->functions->remove_double_slashes($cache_path.$src_path_rel);
		$out_path = $this->EE->functions->remove_double_slashes($cache_path.$src_path_rel.'/'.$out_filename);
		$out_url  = $this->EE->functions->remove_double_slashes($cache_url.$src_path_rel.'/'.$out_filename);

		// Check if the destination directory exists, create it if it doesn't
		if( ! is_dir($out_dir))
		{
			mkdir($out_dir, DIR_WRITE_MODE, TRUE); // This does recursive creation in PHP 5.0 and up
		}

		$cached = $this->is_cached($out_path, $src_path_full, $is_remote);

		if ( ! $cached OR $cache === FALSE)
		{
			$out_image = imagecreatetruecolor($d['out_w'], $d['out_h']);

			if ($image_type == IMAGETYPE_JPEG)
			{
				$src_image = imagecreatefromjpeg($src_path_full);
			}
			elseif ($image_type = IMAGETYPE_PNG)
			{
				$src_image = imagecreatefrompng($src_path_full);

				// Make transparency work
				imagealphablending($out_image, FALSE);
				imagesavealpha($out_image, TRUE);
			}

			// Copy and resample the source image to the destination image, cropping and resizing at the same time
			imagecopyresampled($out_image, $src_image, $d['out_x'], $d['out_y'], 0, 0, $d['copy_w'], $d['copy_h'], $d['src_w'], $d['src_h']);

			// Output the new file using the correct function for the image type
			if ($image_type == IMAGETYPE_JPEG)
			{
				imagejpeg($out_image, $out_path, $quality);
			}
			elseif ($image_type = IMAGETYPE_PNG)
			{
				imagepng($out_image, $out_path);
			}
		}

		if ($just_url == TRUE)
		{
			$this->return_data = $out_url;
			return;
		}
		else
		{
			$attributes['src']    = $out_url;
			$attributes['width']  = $d['out_w'];
			$attributes['height'] = $d['out_h'];
			$attributes['alt']    = $attr_alt;
			$attributes['title']  = $attr_title;
			$attributes['class']  = $attr_class;
			$attributes['id']     = $attr_id;

			$this->return_data = $this->build_tag($attributes);
			return;
		}
	}

	// ----------------------------------------------------------------

	private function build_tag($attributes)
	{
		$tag = '<img ';

		foreach ($attributes AS $key => $value)
		{
			if ( ! empty($value))
			{
				$tag .= "{$key}=\"$value\" ";
			}
		}

		$tag .= '>';

		return $tag;
	}

	// ----------------------------------------------------------------

	private function is_cached($out_path, $src_path, $is_remote = FALSE)
	{
		if (file_exists($out_path) AND $is_remote)
		{
			return TRUE;
		}
		elseif (file_exists($out_path) AND filemtime($out_path) > filemtime($src_path))
		{
			return TRUE;
		}

		return FALSE;
	}

	// ----------------------------------------------------------------

	/**
	 * Calculate the size the image will be resized to
	 *
	 * @param string $out_width
	 * @param string $out_height
	 * @param string $src_width
	 * @param string $src_height
	 * @param string $max
	 * @return void
	 * @author Joseph Wensley
	 */

	private function calculate_dimensions($out_w, $out_h, $src_w, $src_h, $max = FALSE)
	{
		// Set default coordinates
		$out_x = 0;
		$out_y = 0;
		$src_x = 0;
		$src_y = 0;

		// Set Default Ratios
		$rw = 1;
		$rh = 1;

		// Calculate the resize ratios and missing dimensions
		if ($out_w AND $out_h) // Crop and Resize
		{
			if ($max)
			{
				$out_w = min($src_w, $out_w);
				$out_h = min($src_h, $out_h);
			}

			$rw = ($src_w / $out_w);
			$rh = ($src_h / $out_h);

			if ($max)
			{
				if ($rw > $rh)
				{
					$out_h = $out_h / $rw;
				}
				elseif ($rw < $rh)
				{
					$out_w = $out_w / $rh;
				}
			}
		}
		elseif ($out_w) // Resize by width
		{
			if ($max AND $out_w >= $src_w)
			{
				$out_w = $src_w;
			}

			$rw = $rh = ($src_w / $out_w);

			$out_h = ($src_h / $rw); // Calculate the height
		}
		elseif ($out_h) // Resize by height
		{
			if ($max AND $out_h >= $src_h)
			{
				$out_h = $src_h;
			}

			$rh = $rw = ($src_h / $out_h);

			$out_w = ($src_w / $rh); // Calculate the width
		}

		if ($rw > $rh)
		{
			$copy_w = $src_w / $rh;
			$copy_h = $src_h / $rh;
		}
		else
		{
			$copy_w = $src_w / $rw;
			$copy_h = $src_h / $rw;
		}

		$src_x = ($src_w - $copy_w) / 2;
		$src_y = ($src_h - $copy_h) / 2;

		if ($rw > $rh)
		{
			$out_x = -(($copy_w - $out_w) / 2);
		}

		if ($rw < $rh)
		{
			$out_y = -(($copy_h - $out_h) / 2);
		}

		$dimensions['out_x']  = $out_x;
		$dimensions['out_y']  = $out_y;
		$dimensions['src_x']  = $src_x;
		$dimensions['src_y']  = $src_y;
		$dimensions['out_w']  = floor($out_w);
		$dimensions['out_h']  = floor($out_h);
		$dimensions['copy_w'] = floor($copy_w);
		$dimensions['copy_h'] = floor($copy_h);
		$dimensions['src_w']  = $src_w;
		$dimensions['src_h']  = $src_h;

		return $dimensions;
	}

	// ----------------------------------------------------------------

	/**
	 * Determine the full and relative paths to the image
	 *
	 * @param string $src
	 * @return void
	 * @author Joseph Wensley
	 */

	private function get_path_info($src)
	{
		$pattern = "/(((http|ftp|https):\/\/){1}([a-zA-Z0-9_-]+)(\.[a-zA-Z0-9_-]+)+([\S,:\/\.\?=a-zA-Z0-9_-]+))/is";

		if (preg_match($pattern, $src, $matches))
		{
			$url_parts = parse_url($src);
			$url_path  = $url_parts['path'];

			$path_parts    = pathinfo($url_path);
			$filename      = $path_parts['filename'];
			$extension     = $path_parts['extension'];
			$relative_path = $path_parts['dirname'];
			$full_path     = $src;
			$is_remote     = TRUE;
		}
		else
		{
			$is_remote = FALSE;

			if (stripos($src, FCPATH) === FALSE)
			{
				$parts         = pathinfo($src);
				$filename      = $parts['filename'];
				$extension     = $parts['extension'];
				$relative_path = $parts['dirname'];
				$full_path     = FCPATH.$src;
			}
			else
			{
				$parts         = pathinfo(str_replace(FCPATH, '/', $src));
				$filename      = $parts['filename'];
				$extension     = $parts['extension'];
				$relative_path = $parts['dirname'];
				$full_path     = $src;
			}
		}

		return array($full_path, $relative_path, $filename, $extension, $is_remote);
	}

	// ----------------------------------------------------------------

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
- GD2

Parameters
==========
**src:** Path to the image can be a full or relative (to the index.php) system path or a local url
**dir (optional):** Relative path to where you want resized images to be stored. Default is /images/resized/
**width and/or height:** Absolute width or height to resize to
**max_width and/or max_height:** Maximum width or height to resize to
**alt (optional):** Alt text for the img tag
**quality (optional):** The quality of the resized image between 0-100. Default is 100.
**just_url (optional):** Set this to on to only return the URL to the image

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
1.0   - Initial Release
1.0.1 - Bugfixes
1.0.2 - Rewrite dimension calculation code
<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}


/* End of file pi.img_resize.php */
/* Location: /system/expressionengine/third_party/img_resize/pi.img_resize.php */