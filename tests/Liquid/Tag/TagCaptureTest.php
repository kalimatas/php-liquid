<?php

/*
 * This file is part of the Liquid package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Liquid
 */

namespace Liquid\Tag;

use Liquid\TestCase;
use Liquid\Template;

class TagCaptureTest extends TestCase
{
	/**
	 */
	public function testInvalidSyntax()
	{
		$this->expectException(\Liquid\Exception\ParseException::class);

		$template = new Template();
		$template->parse("{% capture %} hello");
	}

	public function testCapture()
	{
		$assigns = ['var' => 'content'];
		$this->assertTemplateResult('content foo content foo ', '{{ var2 }}{% capture var2 %}{{ var }} foo {% endcapture %}{{ var2 }}{{ var2 }}', $assigns);
	}
}
