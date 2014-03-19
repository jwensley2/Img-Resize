Changelog
=========
2.4.0

+ Miscellaneous fixes and improvements under the hood

2.3.2

+ Fix an issue with transparent GIFs

2.3.1

+ Define \_\_DIR\_\_ for PHP versions < 5.3

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