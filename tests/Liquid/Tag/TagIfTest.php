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

class TagIfStringable
{
	public function __toString()
	{
		return '100';
	}
}

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

	public static function stringableConditionProvider()
	{
		return [
			'against a string' => ['drop == "100"'],
			'against a number' => ['drop < 200'],
		];
	}

	/**
	 * @dataProvider stringableConditionProvider
	 */
	public function testStringableValue(string $condition)
	{
		$this->assertTemplateResult(' yes ', "{% if $condition %} yes {% endif %}", ['drop' => new TagIfStringable()]);
	}

	public static function missingValueComparisonProvider()
	{
		$yes = ' yes ';
		$no = '';

		return [
			'nothing is the same as nothing' => ['==', [], $yes],
			'nothing does not differ from nothing' => ['!=', [], $no],
			'nothing is not something' => ['==', ['two' => 'something'], $no],
			'nothing differs from something' => ['!=', ['two' => 'something'], $yes],
			'a value is not an empty collection' => ['==', ['one' => 'x', 'two' => []], $no],
			'a value is not a collection' => ['==', ['one' => 'x', 'two' => [1]], $no],
		];
	}

	/**
	 * @dataProvider missingValueComparisonProvider
	 */
	public function testComparingMissingValues(string $operator, array $assigns, string $expected)
	{
		$this->assertTemplateResult($expected, "{% if one $operator two %} yes {% endif %}", $assigns);
	}

	public static function comparisonOperatorProvider()
	{
		$yes = ' yes ';
		$no = '';

		return [
			'equal values are equal' => ['==', 1, 1, $yes],
			'equal values do not differ' => ['!=', 1, 1, $no],
			'equal values are not less' => ['<', 1, 1, $no],
			'equal values are not more' => ['>', 1, 1, $no],
			'equal values are no smaller' => ['<=', 1, 1, $yes],
			'equal values are no bigger' => ['>=', 1, 1, $yes],
			'different values are not equal' => ['==', 1, 2, $no],
			'different values differ' => ['!=', 1, 2, $yes],
			'a smaller value is smaller' => ['<', 1, 2, $yes],
			'a smaller value is not bigger' => ['>', 1, 2, $no],
			'a smaller value is no bigger' => ['>=', 1, 2, $no],
			'a smaller value is no smaller' => ['<=', 1, 2, $yes],
		];
	}

	/**
	 * @dataProvider comparisonOperatorProvider
	 */
	public function testComparingValues(string $operator, $one, $two, string $expected)
	{
		$this->assertTemplateResult($expected, "{% if one $operator two %} yes {% endif %}", ['one' => $one, 'two' => $two]);
	}

	public static function elsifChainProvider()
	{
		return [
			'the first branch' => [['one' => true], 'A'],
			'the second branch' => [['two' => true], 'B'],
			'the third branch' => [['three' => true], 'C'],
			'nothing matched' => [[], 'D'],
		];
	}

	/**
	 * @dataProvider elsifChainProvider
	 */
	public function testElsifChainPicksTheFirstMatch(array $assigns, string $expected)
	{
		$this->assertTemplateResult($expected, '{% if one %}A{% elsif two %}B{% elsif three %}C{% else %}D{% endif %}', $assigns);
	}

	public static function incompleteConditionProvider()
	{
		return [
			'a condition of one side' => ['{% if one %} yes {% endif %}', ['one' => true], ' yes '],
			'two values with no operator' => ['{% if one two %}yes{% else %}no{% endif %}', ['one' => true], 'no'],
			'an operator with nothing after it' => ['{% if one == %}yes{% else %}no{% endif %}', ['one' => true], 'no'],
		];
	}

	/**
	 * @dataProvider incompleteConditionProvider
	 */
	public function testIncompleteConditions(string $source, array $assigns, string $expected)
	{
		$this->assertTemplateResult($expected, $source, $assigns);
	}

	public static function compoundConditionProvider()
	{
		return [
			'and with both halves true' => ['and', ['one' => 1, 'two' => 2], 'yes'],
			'and with one half false' => ['and', ['one' => 1, 'two' => 3], 'no'],
			'or with one half true' => ['or', ['one' => 0, 'two' => 2], 'yes'],
			'or with both halves false' => ['or', ['one' => 0, 'two' => 3], 'no'],
		];
	}

	/**
	 * @dataProvider compoundConditionProvider
	 */
	public function testCompoundConditionKeepsItsOperatorsApart(string $operator, array $assigns, string $expected)
	{
		$this->assertTemplateResult($expected, "{% if one == 1 $operator two < 3 %}yes{% else %}no{% endif %}", $assigns);
	}

	public function testOrWithTwoFalseOperands()
	{
		$this->assertTemplateResult('no', '{% if one or two %}yes{% else %}no{% endif %}', ['one' => false, 'two' => false]);
	}

	public function testElsifAfterElseIsNotReached()
	{
		$this->assertTemplateResult('B', '{% if one %}A{% else %}B{% elsif two %}C{% endif %}', ['one' => false, 'two' => true]);
	}

	public function testUnknownTagInsideIf()
	{
		$this->expectException(\Liquid\Exception\ParseException::class);
		$this->expectExceptionMessage('Unknown tag foo');

		$this->assertTemplateResult('', '{% if true %}{% foo %}{% endif %}');
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
		$this->expectExceptionMessageMatches('/^Error in tag \'[^\']+\' - Unknown operator ===$/');

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
		$this->expectExceptionMessage('document does not expect else tag');

		$this->assertTemplateResult('', '{% if foo == 1 %}{% endif %}{% else %}');
	}

	/**
	 */
	public function testSyntaxErrorUnknown()
	{
		$this->expectException(\Liquid\Exception\ParseException::class);
		$this->expectExceptionMessage('Unknown tag unknown');

		$this->assertTemplateResult('', '{% unknown-tag %}');
	}
}
