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
**max\_width and/or max\_height:** Maximum width or height to resize to  

Optional
---------
The following options can also be set globaly in a config file using like $config['img_resize:param'] = 'VALUE'

**quality:** The quality of the resized image between 0-100. Default is 100.  
**retina\_quality:** The quality of the resized retina image between 0-100. Defaults to whatever **quality** is.  
**just\_url:** Set this to 'no' to only return the URL to the image  
**sharpen:** Setting this to 'no' will cause images to be sharpened after they are resized  
**urldecode:** Setting to 'no' will disable decoding of the src url  
**handle_retina:** Set to 'no' to disable  

**These should only be set if you know what you are doing**  
**base\_url:** URL to where your images are stored, default is your base_url  
**base\_path:** The base path to where your images are stored, this is used to determine the path to your images when using relative image paths  
**cache\_path:** Full path to where your images are cached, default is FCPATH/images/resized  
**cache\_url:** URL to where your images are cached, default is your base\_path + /images/resized  

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

The first image will be 25x25 and named like image\_25x25.png  
The second image will be 50x50 and named like image\_25x25@2x.png

This should provide support for many retina handling methods that use @2x as an identifer, like http://retinajs.com/.

A data-retina attribute will also be set on the img tag containing the url to the retina image.

Changelog
=========
2.3.0

+ Added retina\_quality parameter so you can seperately set the retina image quality if desired

2.2.0

+ Improve handling of remote images, should be much faster now

2.1.1

+ Fix a bug that could cause retina images could be sized and named wrong

2.1.0

+ Should no longer load images on the same domain as if they were remote when using full urls
+ Added base\_url paremeter that you can set if you're images are on a sub-domain or something similar to make them load from the filesystem instead of remotely
+ Fix a bug with max\_width and max\_height

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