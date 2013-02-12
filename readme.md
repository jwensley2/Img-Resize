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
**handle_retina (optional):** Set to 'no' to disable  

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

Changelog
=========
1.3.0
+ Add support for retina images, if an image is named with @2x the plugin will generate both retina and non-retina versions.
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

1.0.3
+ Fix an issue with paths on Windows

1.0.2
+ Rewrite dimension calculation code

1.0.1
+ Bugfixes

1.0
+ Initial Release