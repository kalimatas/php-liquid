<?php

/*
 * This file is part of the Liquid package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Liquid
 */

namespace Liquid;

class TickTest extends TestCase
{
	public function testSimpleVariable()
	{
		$ticks = 0;

		$template = new Template();
		$template->parse("{% for i in (1..100) %}x{% endfor %}");
		$this->assertEquals(str_pad('x', 100, 'x'), $template->render(
			[],
			[],
			[],
			function (Context $context) use (&$ticks) {
				$ticks++;
			}
		));

		$this->assertGreaterThanOrEqual(100, $ticks);
	}
}
