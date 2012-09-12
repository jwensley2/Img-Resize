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
**just_url (optional):** Set this to 'on' to only return the URL to the image  
**sharpen (optional):** Setting this to 'on' will cause images to be sharpened after they are resized  

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