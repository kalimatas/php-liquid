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

class TagTablerowTest extends TestCase
{
	public function testTablerow()
	{
		$this->assertTemplateResult(
			'<tr class="row1">'."\n".'<td class="col1"> yo </td><td class="col2"> yo </td><td class="col3"> yo </td><td class="col4"> yo </td></tr>'."\n",
			'{% tablerow item in array %} yo {% endtablerow %}',
			['array' => [1, 2, 3, 4]]
		);

		$this->assertTemplateResult(
			'<tr class="row1">' . "\n" . '<td class="col1"> item 1 </td></tr>' . "\n" . '<tr class="row2">' . "\n" . '<td class="col1"> item 2 </td></tr>' . "\n",
			'{% tablerow item in array cols:1 %} item {{ item }} {% endtablerow %}',
			['array' => [1, 2]]
		);

		$this->assertTemplateResult(
			'<tr class="row1">'."\n".'<td class="col1"> 2 </td><td class="col2"> 3 </td></tr>'."\n",
			'{% tablerow item in array limit:2 offset:1 %} {{ item }} {% endtablerow %}',
			['array' => [1, 2, 3, 4]]
		);

		$this->assertTemplateResult(
			'<tr class="row1">'."\n".'<td class="col1"> yo </td><td class="col2"> yo </td></tr>'."\n",
			'{%tablerow item in array%} yo {%endtablerow%}',
			['array' => new \ArrayIterator([1, 2])]
		);
	}

	/**
	 */
	public function testInvalidSyntax()
	{
		$this->expectException(\Liquid\Exception\ParseException::class);

		$this->assertTemplateResult('', '{%tablerow item array%} yo {%endtablerow%}', []);
	}

	/**
	 */
	public function testNotArray()
	{
		$this->expectException(\Liquid\Exception\RenderException::class);

		$this->assertTemplateResult('', '{%tablerow item in array%} yo {%endtablerow%}', ['array' => true]);
	}
}
