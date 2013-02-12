<?php

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

class Img_resize_image {

	public $return_data;

	// Options
	private $cache_dir = '/images/resized/';
	private $base_url  = '/';
	private $base_path = __DIR__;
	private $quality   = 100;
	private $sharpen   = FALSE;
	private $cache     = TRUE;
	private $urldecode = TRUE;

	// Image Properties
	private $image_path;
	private $image_type;
	private $width;
	private $height;
	private $out_height;
	private $out_path;
	private $out_url;
	private $out_width;

	/**
	 * Constructor
	 * @param string $image_path Path to the image
	 * @param array  $options    Array of options
	 */
	public function __construct($image_path, $options = array())
	{
		$this->image_path = $image_path;

		if (( ! extension_loaded('gd') AND ! function_exists('gd_info')) AND ! class_exists("Imagick"))
		{
			throw new Exception('GD OR Imagick must be installed to use Img Resize', 1);
		}

		foreach ($options AS $key => $value)
		{
			$this->$key = $value;
		}

		if ($this->urldecode)
		{
			$this->image_path = urldecode($this->image_path);
		}

		// Get the path info for the image
		$this->find_path_info();

		// Try and read the image
		if (@fopen($this->full_path, 'r'))
		{
			list($this->width, $this->height, $this->image_type) = getimagesize($this->full_path);
		}
		else
		{
			throw new Exception("Could not open image file - {$this->full_path}", 1);
		}
	}

	// ------------------------------------------------------------------------

	public function load($image_path, $options = array())
	{
		return new self($image_path, $options);
	}

	// ------------------------------------------------------------------------

	public function resize($width, $height, $max = FALSE, $method = 'Imagick')
	{

		$this->calculate_dimensions($width, $height, $max);
		$this->find_output_paths();

		// Check if the destination directory exists, create it if it doesn't
		if( ! is_dir($this->out_dir))
		{
			mkdir($this->out_dir, 0777, TRUE);
		}

		$cached = $this->is_cached();

		if ( ! $cached OR $this->cache === FALSE)
		{
			if ($method === 'Imagick' AND class_exists("Imagick"))
			{
				$this->resize_using_imagick();
			}
			else
			{
				$this->resize_using_gd();
			}
		}

		return $this;
	}

	// ------------------------------------------------------------------------

	/**
	 * Resize the image using GD
	 *
	 * @return void
	 * @author Joseph Wensley
	 */

	private function resize_using_gd()
	{
		@ini_set("memory_limit","12M");
		@ini_set("memory_limit","16M");
		@ini_set("memory_limit","32M");
		@ini_set("memory_limit","64M");
		@ini_set("memory_limit","128M");

		$d = $this->dimensions;

		$this->out_image = imagecreatetruecolor($d['out_w'], $d['out_h']);

		if ($this->image_type == IMAGETYPE_JPEG)
		{
			$this->image = imagecreatefromjpeg($this->full_path);
		}
		elseif ($this->image_type == IMAGETYPE_PNG)
		{
			$this->image = imagecreatefrompng($this->full_path);

		// 	// Make transparency work
			imagealphablending($this->out_image, FALSE);
			imagesavealpha($this->out_image, TRUE);
		}
		elseif ($this->image_type == IMAGETYPE_GIF)
		{
			$this->image = imagecreatefromgif($this->full_path);
		}


		// // Copy and resample the source image to the destination image, cropping and resizing at the same time
		imagecopyresampled($this->out_image, $this->image, $d['out_x'], $d['out_y'], 0, 0, $d['copy_w'], $d['copy_h'], $d['src_w'], $d['src_h']);

		// Sharpen the image if enabled
		if ($this->sharpen === TRUE)
		{
			$this->imagesharpen($this->out_image);
		}

		// Output the new file using the correct function for the image type
		if ($this->image_type == IMAGETYPE_JPEG)
		{
			imagejpeg($this->out_image, $this->out_path, $this->quality);
		}
		elseif ($this->image_type == IMAGETYPE_PNG)
		{
			imagepng($this->out_image, $this->out_path);
		}
		elseif ($this->image_type == IMAGETYPE_GIF)
		{
			imagegif($this->out_image, $this->out_path);
		}

		// imagedestroy($this->image);
		imagedestroy($this->out_image); // Destroy the image, we've already written the file
	}

	// ------------------------------------------------------------------------

	/**
	 * Resize the image using Imagick
	 *
	 * @return void
	 * @author Joseph Wensley
	 */

	private function resize_using_imagick()
	{
		$image = new Imagick($this->full_path);

		$d = $this->dimensions;

		if ($d['crop'])
		{
			$image->cropThumbnailImage($d['out_w'], $d['out_h']);
		}
		else
		{
			$image->thumbnailImage($d['out_w'], $d['out_h'], TRUE);
		}

		// Sharpen the image if enabled
		if ($this->sharpen)
		{
			$image->sharpenImage(1.5, 1);
		}

		$image->writeImage($this->out_path);
	}

	// ------------------------------------------------------------------------

	/**
	 * Build the HTML tag
	 *
	 * @return string
	 * @author Joseph Wensley
	 */

	public function build_tag($attributes = array())
	{
		$tag = '<img ';

		$attributes['width']  = $this->out_width;
		$attributes['height'] = $this->out_height;
		$attributes['src']    = $this->out_url;

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

	// ------------------------------------------------------------------------

	public function get_url()
	{
		return $this->out_url;
	}

	// ------------------------------------------------------------------------

	/**
	 * Check to see if there is a cached version of the image
	 *
	 * @return bool
	 * @author Joseph Wensley
	 */

	private function is_cached()
	{
		if (file_exists($this->out_path) AND $this->is_remote)
		{
			return TRUE;
		}
		elseif (file_exists($this->out_path) AND filemtime($this->out_path) > filemtime($this->full_path))
		{
			return TRUE;
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

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

	private function calculate_dimensions($out_w, $out_h, $max = FALSE)
	{
		$crop = FALSE;

		$src_w = $this->width;
		$src_h = $this->height;

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

			$crop = TRUE;
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
		$dimensions['out_w']  = $this->out_width  = floor($out_w);
		$dimensions['out_h']  = $this->out_height = floor($out_h);
		$dimensions['copy_w'] = floor($copy_w);
		$dimensions['copy_h'] = floor($copy_h);
		$dimensions['src_w']  = $src_w;
		$dimensions['src_h']  = $src_h;
		$dimensions['crop']   = $crop;

		$this->dimensions = $dimensions;
	}

	// ------------------------------------------------------------------------

	/**
	 * Determine the full and relative paths to the image
	 *
	 * @param string $src
	 * @return void
	 * @author Joseph Wensley
	 */

	private function find_path_info()
	{
		$pattern = "/(((http|ftp|https):\/\/){1}([a-zA-Z0-9_-]+)(\.[a-zA-Z0-9_-]+)+([\S,:\/\.\?=a-zA-Z0-9_-]+))/is";

		if (preg_match($pattern, $this->image_path, $matches))
		{
			$url_parts = parse_url($this->image_path);
			$url_path  = $url_parts['path'];

			$path_parts    = pathinfo($url_path);
			$filename      = $path_parts['filename'];
			$extension     = $path_parts['extension'];
			$relative_path = $path_parts['dirname'];
			$full_path     = $this->image_path;
			$is_remote     = TRUE;
		}
		else
		{
			$is_remote = FALSE;

			// Trim the trailing slashes off
			$this->image_path    = rtrim($this->image_path, '\\/');

			$fcpath = rtrim($this->base_path, '\\/');

			if (stripos($this->image_path, $fcpath) === FALSE)
			{
				$parts         = pathinfo($this->image_path);
				$filename      = $parts['filename'];
				$extension     = $parts['extension'];
				$relative_path = $parts['dirname'];
				$full_path     = $this->remove_double_slashes($fcpath.'/'.$this->image_path);
			}
			else
			{
				$parts         = pathinfo(str_replace($fcpath, '/', $this->image_path));
				$filename      = $parts['filename'];
				$extension     = $parts['extension'];
				$relative_path = $parts['dirname'];
				$full_path     = $src;
			}
		}

		$this->full_path     = $full_path;
		$this->relative_path = $relative_path;
		$this->filename      = $filename;
		$this->extension     = $extension;
		$this->is_remote     = $is_remote;
	}

	// ------------------------------------------------------------------------

	private function find_output_paths()
	{
		// Determine the url and path to the cache folder
		$this->cache_url  = $this->base_url.$this->cache_dir;
		$this->cache_path = $this->base_path.$this->cache_dir;

		$filename = "{$this->filename}_{$this->out_width}x{$this->out_height}.{$this->extension}";

		$this->out_dir  = $this->remove_double_slashes($this->cache_path.$this->relative_path);
		$this->out_path = $this->remove_double_slashes($this->cache_path.$this->relative_path.'/'.$filename);
		$this->out_url  = $this->remove_double_slashes($this->cache_url.$this->relative_path.'/'.urlencode($filename));
	}

	// ------------------------------------------------------------------------

	/**
	 * Sharpen an image using GD
	 *
	 * @return void
	 * @author Joseph Wensley
	 */

	private function imagesharpen() {
		if (function_exists('imageconvolution'))
		{
			$matrix = array(
				array(-1, -1, -1),
				array(-1, 16, -1),
				array(-1, -1, -1),
			);

			$divisor = array_sum(array_map('array_sum', $matrix));
			$offset  = 0;

			imageconvolution($this->out_image, $matrix, $divisor, $offset);
		}
	}

	// ------------------------------------------------------------------------

	private function remove_double_slashes($string)
	{
		return preg_replace("#([^/:])/+#", "\\1/", $string);
	}

	// ------------------------------------------------------------------------

	public function is_retina()
	{
		$regex = "/@2x/s";

		if (preg_match($regex, $this->filename))
		{
			# code...
		}
	}
}


/* End of file pi.img_resize.php */
/* Location: /system/expressionengine/third_party/img_resize/pi.img_resize.php */