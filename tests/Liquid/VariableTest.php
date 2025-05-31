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

class VariableTest extends TestCase
{
	public function testVariable()
	{
		$var = new Variable('hello');
		$this->assertEquals('hello', $var->getName());
	}

	public function testFilters()
	{
		$var = new Variable('hello | textileze');
		$this->assertEquals('hello', $var->getName());
		$this->assertEquals([['textileze', []]], $var->getFilters());

		$var = new Variable('hello | textileze | paragraph');
		$this->assertEquals('hello', $var->getName());
		$this->assertEquals([['textileze', []], ['paragraph', []]], $var->getFilters());

		$var = new Variable(" hello | strftime: '%Y'");
		$this->assertEquals('hello', $var->getName());
		$this->assertEquals([['strftime', ["'%Y'"]]], $var->getFilters());

		$var = new Variable(" 'typo' | link_to: 'Typo', true ");
		$this->assertEquals("'typo'", $var->getName());
		$this->assertEquals([['link_to', ["'Typo'", "true"]]], $var->getFilters());

		$var = new Variable(" 'typo' | link_to: 'Typo', false ");
		$this->assertEquals("'typo'", $var->getName());
		$this->assertEquals([['link_to', ["'Typo'", "false"]]], $var->getFilters());

		$var = new Variable(" 'foo' | repeat: 3 ");
		$this->assertEquals("'foo'", $var->getName());
		$this->assertEquals([['repeat', ["3"]]], $var->getFilters());

		$var = new Variable(" 'foo' | repeat: 3, 3");
		$this->assertEquals("'foo'", $var->getName());
		$this->assertEquals([['repeat', ["3", "3"]]], $var->getFilters());

		$var = new Variable(" 'foo' | repeat: 3, 3, 3 ");
		$this->assertEquals("'foo'", $var->getName());
		$this->assertEquals([['repeat', ["3", "3", "3"]]], $var->getFilters());

		$var = new Variable(" hello | strftime: '%Y, okay?'");
		$this->assertEquals('hello', $var->getName());
		$this->assertEquals([['strftime', ["'%Y, okay?'"]]], $var->getFilters());

		$var = new Variable(" hello | things: \"%Y, okay?\", 'the other one'");
		$this->assertEquals('hello', $var->getName());
		$this->assertEquals([['things', ['"%Y, okay?"', "'the other one'"]]], $var->getFilters());

		$var = new Variable(" product.featured_image | img_url: '450x450', crop: 'center', scale: 2 ");
		$this->assertEquals("product.featured_image", $var->getName());
		$this->assertEquals([['img_url', ["'450x450'", ["crop" => "'center'", "scale" => "2"]]]], $var->getFilters());
	}

	public function testFiltersWithoutWhitespace()
	{
		$var = new Variable('hello | textileze | paragraph');
		$this->assertEquals('hello', $var->getName());
		$this->assertEquals([['textileze', []], ['paragraph', []]], $var->getFilters());

		$var = new Variable('hello|textileze|paragraph');
		$this->assertEquals('hello', $var->getName());
		$this->assertEquals([['textileze', []], ['paragraph', []]], $var->getFilters());
	}

	public function testSymbol()
	{
		$var = new Variable("http://disney.com/logo.gif | image: 'med' ");
		$this->assertEquals('http://disney.com/logo.gif', $var->getName());
		$this->assertEquals([['image', ["'med'"]]], $var->getFilters());
	}

	public function testStringSingleQuoted()
	{
		$var = new Variable(' "hello" ');
		$this->assertEquals('"hello"', $var->getName());
	}

	public function testStringDoubleQuoted()
	{
		$var = new Variable(" 'hello' ");
		$this->assertEquals("'hello'", $var->getName());
	}

	public function testInteger()
	{
		$var = new Variable(' 1000 ');
		$this->assertEquals('1000', $var->getName());
	}

	public function testFloat()
	{
		$var = new Variable(' 1000.01 ');
		$this->assertEquals('1000.01', $var->getName());
	}

	public function testStringWithSpecialChars()
	{
		$var = new Variable("'hello! $!@.;\"ddasd\" ' ");
		$this->assertEquals("'hello! $!@.;\"ddasd\" '", $var->getName());
	}

	public function testStringDot()
	{
		$var = new Variable(" test.test ");
		$this->assertEquals('test.test', $var->getName());
	}
}
