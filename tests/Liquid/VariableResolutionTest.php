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

class VariableResolutionTest extends TestCase
{
	public function testSimpleVariable()
	{
		$template = new Template();
		$template->parse("{{test}}");
		$this->assertEquals('worked', $template->render(['test' => 'worked']));
	}

	public function testSimpleWithWhitespaces()
	{
		$template = new Template();

		$template->parse('  {{ test }}  ');
		$this->assertEquals('  worked  ', $template->render(['test' => 'worked']));
		$this->assertEquals('  worked wonderfully  ', $template->render(['test' => 'worked wonderfully']));
	}

	public function testIgnoreUnknown()
	{
		$template = new Template();

		$template->parse('{{ test }}');
		$this->assertEquals('', $template->render());
	}

	public function testLineBreak()
	{
		$template = new Template();

		$template->parse("{{ test |\n strip_html }}");
		$this->assertEquals('worked', $template->render(['test' => '<b>worked</b>']));
	}

	public function testDeeplyNestedAttributes()
	{
		$template = new Template();

		$template->parse('{{ a.b.c }}');
		$this->assertEquals('deep', $template->render(['a' => ['b' => ['c' => 'deep']]]));
	}

	public static function sizeOfAValueProvider()
	{
		return [
			'an array is counted' => ['{{ a.size }}', ['a' => [1, 2, 3]], '3'],
			'a string is measured' => ['{{ a.size }}', ['a' => 'abcde'], '5'],
			'a key named size wins' => ['{{ a.size.b }}', ['a' => ['size' => ['b' => 'deep']]], 'deep'],
		];
	}

	/**
	 * @dataProvider sizeOfAValueProvider
	 */
	public function testSizeOfAValue(string $source, array $assigns, string $expected)
	{
		$template = new Template();
		$template->parse($source);

		$this->assertEquals($expected, $template->render($assigns));
	}

	public function testArrayScoping()
	{
		$template = new Template();

		$template->parse('{{ test.test }}');
		$this->assertEquals('worked', $template->render(['test' => ['test' => 'worked']]));
	}

	public function testVariableArrayIndices()
	{
		$template = new Template();

		$template->parse("{% assign days = 'Mon,Tue,Wed,Thu,Fri,Sat,Sun' | split: ',' %}{% for i in (0..6) %}{{ days[i] }} {% endfor %}");
		$this->assertEquals('Mon Tue Wed Thu Fri Sat Sun ', $template->render());
	}
}
