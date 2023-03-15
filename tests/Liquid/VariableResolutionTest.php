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
		$this->assertEquals('worked', $template->render(array('test' => 'worked')));
	}

	public function testSimpleWithWhitespaces()
	{
		$template = new Template();

		$template->parse('  {{ test }}  ');
		$this->assertEquals('  worked  ', $template->render(array('test' => 'worked')));
		$this->assertEquals('  worked wonderfully  ', $template->render(array('test' => 'worked wonderfully')));
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
		$this->assertEquals('worked', $template->render(array('test' => '<b>worked</b>')));
	}

	public function squareBracketsProvider()
	{
		yield ['Hello, {{ name["Hello World"] }}!'];
		yield ['Hello, {{ name[\'Hello World\'] }}!'];
	}

	/**
	 * @dataProvider squareBracketsProvider
	 */
	public function testSquareBrackets(string $test)
	{
		$template = new Template;
		$template->parse($test);
		$this->assertEquals('Hello, foo!', $template->render(array('name' => array('Hello World' => 'foo'))));
	}

	public function testArrayScoping()
	{
		$template = new Template();

		$template->parse('{{ test.test }}');
		$this->assertEquals('worked', $template->render(array('test' => array('test' => 'worked'))));
	}

	public function testVariableArrayIndices()
	{
		$template = new Template();

		$template->parse("{% assign days = 'Mon,Tue,Wed,Thu,Fri,Sat,Sun' | split: ',' %}{% for i in (0..6) %}{{ days[i] }} {% endfor %}");
		$this->assertEquals('Mon Tue Wed Thu Fri Sat Sun ', $template->render());
	}
}
