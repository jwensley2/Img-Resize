<?php
require_once(__DIR__.'/../img_resize/library/img_resize_image.php');

class ImgResizeImageTest extends PHPUnit_Framework_TestCase
{

	public function setUp()
	{
		$this->sources = array(
			'images/250x500.png',
			'images/500x250.png',
			'images/500x500.png',
		);

		$this->options = array(
			'base_path' => __DIR__,
			'base_url'  => '/images/',
			'cache_path' => __DIR__.'/resized/',
			'cache_url' => '/images/'
		);
	}

	/**
	 * @covers Img_resize_image::resize
	 * @covers Img_resize_image::getDimensions
	 */
	public function testResizeByWidthAndHeight()
	{
		// 250x500 image
		$image = Img_resize_image::load($this->sources[0], $this->options);

		$d = $image->resize(500, 500)->getDimensions();
		$this->assertEquals(500, $d['out_w']);
		$this->assertEquals(500, $d['out_h']);

		$d = $image->resize(500, 250)->getDimensions();
		$this->assertEquals(500, $d['out_w']);
		$this->assertEquals(250, $d['out_h']);

		$d = $image->resize(250, 500)->getDimensions();
		$this->assertEquals(250, $d['out_w']);
		$this->assertEquals(500, $d['out_h']);

		// 500x250 image
		$image = Img_resize_image::load($this->sources[1], $this->options);

		$d = $image->resize(500, 500)->getDimensions();
		$this->assertEquals(500, $d['out_w']);
		$this->assertEquals(500, $d['out_h']);

		$d = $image->resize(500, 250)->getDimensions();
		$this->assertEquals(500, $d['out_w']);
		$this->assertEquals(250, $d['out_h']);

		$d = $image->resize(250, 500)->getDimensions();
		$this->assertEquals(250, $d['out_w']);
		$this->assertEquals(500, $d['out_h']);

		// 500x500 image
		$image = Img_resize_image::load($this->sources[2], $this->options);

		$d = $image->resize(500, 500)->getDimensions();
		$this->assertEquals(500, $d['out_w']);
		$this->assertEquals(500, $d['out_h']);

		$d = $image->resize(500, 250)->getDimensions();
		$this->assertEquals(500, $d['out_w']);
		$this->assertEquals(250, $d['out_h']);

		$d = $image->resize(250, 500)->getDimensions();
		$this->assertEquals(250, $d['out_w']);
		$this->assertEquals(500, $d['out_h']);
	}

	/**
	 * @covers Img_resize_image::resize
	 * @covers Img_resize_image::getDimensions
	 */
	public function testResizeByWidth()
	{
		// 250x500 image
		$image = Img_resize_image::load($this->sources[0], $this->options);

		$d = $image->resize(500, NULL)->getDimensions();
		$this->assertEquals(500, $d['out_w']);
		$this->assertEquals(1000, $d['out_h']);

		$d = $image->resize(250, NULL)->getDimensions();
		$this->assertEquals(250, $d['out_w']);
		$this->assertEquals(500, $d['out_h']);

		// 500x250 image
		$image = Img_resize_image::load($this->sources[1], $this->options);

		$d = $image->resize(500, NULL)->getDimensions();
		$this->assertEquals(500, $d['out_w']);
		$this->assertEquals(250, $d['out_h']);

		$d = $image->resize(250, NULL)->getDimensions();
		$this->assertEquals(250, $d['out_w']);
		$this->assertEquals(125, $d['out_h']);


		// 500x500 image
		$image = Img_resize_image::load($this->sources[2], $this->options);

		$d = $image->resize(500, NULL)->getDimensions();
		$this->assertEquals(500, $d['out_w']);
		$this->assertEquals(500, $d['out_h']);

		$d = $image->resize(250, NULL)->getDimensions();
		$this->assertEquals(250, $d['out_w']);
		$this->assertEquals(250, $d['out_h']);
	}

	public function testResizeByHeight()
	{
		// 250x500 image
		$image = Img_resize_image::load($this->sources[0], $this->options);

		$d = $image->resize(NULL, 500)->getDimensions();
		$this->assertEquals(250, $d['out_w']);
		$this->assertEquals(500, $d['out_h']);

		$d = $image->resize(NULL, 250)->getDimensions();
		$this->assertEquals(125, $d['out_w']);
		$this->assertEquals(250, $d['out_h']);

		// 500x250 image
		$image = Img_resize_image::load($this->sources[1], $this->options);

		$d = $image->resize(NULL, 500)->getDimensions();
		$this->assertEquals(1000, $d['out_w']);
		$this->assertEquals(500, $d['out_h']);

		$d = $image->resize(NULL, 250)->getDimensions();
		$this->assertEquals(500, $d['out_w']);
		$this->assertEquals(250, $d['out_h']);

		// 500x500 image
		$image = Img_resize_image::load($this->sources[2], $this->options);

		$d = $image->resize(NULL, 500)->getDimensions();
		$this->assertEquals(500, $d['out_w']);
		$this->assertEquals(500, $d['out_h']);

		$d = $image->resize(NULL, 250)->getDimensions();
		$this->assertEquals(250, $d['out_w']);
		$this->assertEquals(250, $d['out_h']);
	}
}