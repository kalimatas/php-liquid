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

class TagIfTest extends TestCase
{
	public function testTrueEqlTrue()
	{
		$text = " {% if true == true %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text);
	}

	public function testTrueNotEqlTrue()
	{
		$text = " {% if true != true %} true {% else %} false {% endif %} ";
		$expected = "  false  ";
		$this->assertTemplateResult($expected, $text);
	}

	public function testTrueLqTrue()
	{
		$text = " {% if 0 > 0 %} true {% else %} false {% endif %} ";
		$expected = "  false  ";
		$this->assertTemplateResult($expected, $text);
	}

	public function testOneLqZero()
	{
		$text = " {% if 1 > 0 %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text);
	}

	public function testZeroLqOne()
	{
		$text = " {% if 0 < 1 %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text);
	}

	public function testZeroLqOrEqualOne()
	{
		$text = " {% if 0 <= 0 %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text);
	}

	public function testZeroLqOrEqualOneInvolvingNil()
	{
		$text = " {% if null <= 0 %} true {% else %} false {% endif %} ";
		$expected = "  false  ";
		$this->assertTemplateResult($expected, $text);


		$text = " {% if 0 <= null %} true {% else %} false {% endif %} ";
		$expected = "  false  ";
		$this->assertTemplateResult($expected, $text);
	}

	public function testZeroLqqOrEqualOne()
	{
		$text = " {% if 0 >= 0 %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text);
	}

	public function testStrings()
	{
		$text = " {% if 'test' == 'test' %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text);
	}

	public function testStringsNotEqual()
	{
		$text = " {% if 'test' != 'test' %} true {% else %} false {% endif %} ";
		$expected = "  false  ";
		$this->assertTemplateResult($expected, $text);
	}

	public function testVarStringsEqual()
	{
		$text = " {% if var == \"hello there!\" %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text, ['var' => 'hello there!']);
	}

	public function testVarStringsAreNotEqual()
	{
		$text = " {% if \"hello there!\" == var %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text, ['var' => 'hello there!']);
	}

	public function testVarAndLongStringAreEqual()
	{
		$text = " {% if var == 'hello there!' %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text, ['var' => 'hello there!']);
	}

	public function testVarAndLongStringAreEqualBackwards()
	{
		$text = " {% if 'hello there!' == var %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text, ['var' => 'hello there!']);
	}

	public function testIsCollectionEmpty()
	{
		$text = " {% if array == empty %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text, ['array' => []]);

		$text = " {% if empty == array %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text, ['array' => []]);
	}

	public function testIsNotCollectionEmpty()
	{
		$text = " {% if array == empty %} true {% else %} false {% endif %} ";
		$expected = "  false  ";
		$this->assertTemplateResult($expected, $text, ['array' => [1, 2, 3]]);
	}

	public function testNil()
	{
		$text = " {% if var == null %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text, ['var' => null]);

		$text = " {% if var == null %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text, ['var' => null]);
	}

	public function testNotNil()
	{
		$text = " {% if var != null %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text, ['var' => 1]);

		$text = " {% if var != null %} true {% else %} false {% endif %} ";
		$expected = "  true  ";
		$this->assertTemplateResult($expected, $text, ['var' => 1]);
	}

	public function testNotNilWhitespaceControlEdgeCase()
	{
		$this->assertTemplateResult("true", "{% if 1 -%}true{% endif %}");
		$this->assertTemplateResult("true", "{% if 1 -%} true{% endif %}");
	}

	public function testIfFromVariable()
	{
		$this->assertTemplateResult('', '{% if var %} NO {% endif %}', ['var' => false]);
		$this->assertTemplateResult('', '{% if var %} NO {% endif %}', ['var' => null]);
		$this->assertTemplateResult('', '{% if foo.bar %} NO {% endif %}', ['foo' => ['bar' => false]]);
		$this->assertTemplateResult('', '{% if foo.bar %} NO {% endif %}', ['foo' => []]);
		$this->assertTemplateResult('', '{% if foo.bar %} NO {% endif %}', ['foo' => null]);

		$this->assertTemplateResult(' YES ', '{% if var %} YES {% endif %}', ['var' => "text"]);
		$this->assertTemplateResult(' YES ', '{% if var %} YES {% endif %}', ['var' => true]);
		$this->assertTemplateResult(' YES ', '{% if var %} YES {% endif %}', ['var' => 1]);
		$this->assertTemplateResult(' YES ', '{% if "foo" %} YES {% endif %}');
		$this->assertTemplateResult(' YES ', '{% if foo.bar %} YES {% endif %}', ['foo' => ['bar' => true]]);
		$this->assertTemplateResult(' YES ', '{% if foo.bar %} YES {% endif %}', ['foo' => ['bar' => "text"]]);
		$this->assertTemplateResult(' YES ', '{% if foo.bar %} YES {% endif %}', ['foo' => ['bar' => 1]]);

		$this->assertTemplateResult(' YES ', '{% if var %} NO {% else %} YES {% endif %}', ['var' => false]);
		$this->assertTemplateResult(' YES ', '{% if var %} NO {% else %} YES {% endif %}', ['var' => null]);
		$this->assertTemplateResult(' YES ', '{% if var %} YES {% else %} NO {% endif %}', ['var' => true]);
		$this->assertTemplateResult(' YES ', '{% if "foo" %} YES {% else %} NO {% endif %}', ['var' => "text"]);

		$this->assertTemplateResult(' YES ', '{% if foo.bar %} NO {% else %} YES {% endif %}', ['foo' => ['bar' => false]]);
		$this->assertTemplateResult(' YES ', '{% if foo.bar %} YES {% else %} NO {% endif %}', ['foo' => ['bar' => true]]);
		$this->assertTemplateResult(' YES ', '{% if foo.bar %} YES {% else %} NO {% endif %}', ['foo' => ['bar' => "text"]]);
		$this->assertTemplateResult(' YES ', '{% if foo.bar %} NO {% else %} YES {% endif %}', ['foo' => ['notbar' => true]]);
		$this->assertTemplateResult(' YES ', '{% if foo.bar %} NO {% else %} YES {% endif %}', ['foo' => []]);
		$this->assertTemplateResult(' YES ', '{% if foo.bar %} NO {% else %} YES {% endif %}', ['notfoo' => ['bar' => true]]);
	}

	public function testNestedIf()
	{
		$this->assertTemplateResult('', '{% if false %}{% if false %} NO {% endif %}{% endif %}');
		$this->assertTemplateResult('', '{% if false %}{% if true %} NO {% endif %}{% endif %}');
		$this->assertTemplateResult('', '{% if true %}{% if false %} NO {% endif %}{% endif %}');
		$this->assertTemplateResult(' YES ', '{% if true %}{% if true %} YES {% endif %}{% endif %}');

		$this->assertTemplateResult(' YES ', '{% if true %}{% if true %} YES {% else %} NO {% endif %}{% else %} NO {% endif %}');
		$this->assertTemplateResult(' YES ', '{% if true %}{% if false %} NO {% else %} YES {% endif %}{% else %} NO {% endif %}');
		$this->assertTemplateResult(' YES ', '{% if false %}{% if true %} NO {% else %} NONO {% endif %}{% else %} YES {% endif %}');
	}

	public function testComplexConditions()
	{
		$this->assertTemplateResult('true', '{% if 10 == 10 and "h" == "h" %}true{% else %}false{% endif %}');
		$this->assertTemplateResult('true', '{% if 8 == 10 or "h" == "h" %}true{% else %}false{% endif %}');
		$this->assertTemplateResult('false', '{% if 8 == 10 and "h" == "h" %}true{% else %}false{% endif %}');
		$this->assertTemplateResult('true', '{% if 10 == 10 or "h" == "k" or "k" == "k" %}true{% else %}false{% endif %}');
	}

	public function testContains()
	{
		$this->assertTemplateResult('true', '{% if foo contains "h" %}true{% else %}false{% endif %}', ['foo' => ['k', 'h', 'z']]);
		$this->assertTemplateResult('false', '{% if foo contains "y" %}true{% else %}false{% endif %}', ['foo' => ['k', 'h', 'z']]);
		$this->assertTemplateResult('true', '{% if foo contains "e" %}true{% else %}false{% endif %}', ['foo' => 'abcedf']);
		$this->assertTemplateResult('true', '{% if foo contains "e" %}true{% else %}false{% endif %}', ['foo' => 'e']);
		$this->assertTemplateResult('false', '{% if foo contains "y" %}true{% else %}false{% endif %}', ['foo' => 'abcedf']);
	}

	/**
	 */
	public function testSyntaxErrorNotClosed()
	{
		$this->expectException(\Liquid\Exception\ParseException::class);
		$this->expectExceptionMessage('if tag was never closed');

		$this->assertTemplateResult('', '{% if jerry == 1 %}');
	}

	public function testSyntaxErrorNotClosedLineBreak()
	{
		$this->expectException(\Liquid\Exception\ParseException::class);
		$this->expectExceptionMessage('if tag was never closed');

		$this->assertTemplateResult('', "{% if jerry\n == 1 %}");
	}

	/**
	 */
	public function testSyntaxErrorEnd()
	{
		$this->expectException(\Liquid\Exception\ParseException::class);

		$this->assertTemplateResult('', '{% if jerry == 1 %}{% end %}');
	}

	/**
	 */
	public function testInvalidOperator()
	{
		$this->expectException(\Liquid\Exception\RenderException::class);

		$this->assertTemplateResult('', '{% if foo === y %}true{% else %}false{% endif %}', ['foo' => true, 'y' => true]);
	}

	/**
	 */
	public function testIncomparable()
	{
		$this->expectException(\Liquid\Exception\RenderException::class);

		$this->assertTemplateResult('', '{% if foo == 1 %}true{% endif %}', ['foo' => (object) []]);
	}

	/**
	 */
	public function testSyntaxErrorElse()
	{
		$this->expectException(\Liquid\Exception\ParseException::class);
		$this->expectExceptionMessage('does not expect else tag');

		$this->assertTemplateResult('', '{% if foo == 1 %}{% endif %}{% else %}');
	}

	/**
	 */
	public function testSyntaxErrorUnknown()
	{
		$this->expectException(\Liquid\Exception\ParseException::class);
		$this->expectExceptionMessage('Unknown tag');

		$this->assertTemplateResult('', '{% unknown-tag %}');
	}
}
