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

class TagForTest extends TestCase
{
	/**
	 */
	public function testForInvalidSyntax()
	{
		$this->expectException(\Liquid\Exception\ParseException::class);

		$template = new Template();
		$template->parse("{% for elem %}{% endfor %}");
	}

	public function testFor()
	{
		$this->assertTemplateResult('', '{%for item in array%} yo {%endfor%}', ['array' => []]);
		$this->assertTemplateResult(' yo  yo  yo  yo ', '{%for item in array%} yo {%endfor%}', ['array' => [1, 2, 3, 4]]);
		$this->assertTemplateResult(' boo  boo  boo  boo ', '{%for item in array%} boo {%endfor%}', ['array' => new \ArrayIterator([1, 2, 3, 4])]);
		$this->assertTemplateResult('yoyo', '{%for item in array%}yo{%endfor%}', ['array' => [1, 2]]);
		$this->assertTemplateResult(' yo ', '{%for item in array%} yo {%endfor%}', ['array' => [1]]);
		$this->assertTemplateResult('', '{%for item in array%}{%endfor%}', ['array' => [1, 2]]);

		$expected = <<<HERE

  yo

  yo

  yo

HERE;
		$template = <<<HERE
{%for item in array%}
  yo
{%endfor%}
HERE;
		$this->assertTemplateResult($expected, $template, ['array' => [1, 2, 3]]);
	}

	public function testForWithVariable()
	{
		$this->assertTemplateResult(' 1  2  3 ', '{%for item in array%} {{item}} {%endfor%}', ['array' => [1, 2, 3]]);
		$this->assertTemplateResult('123', '{%for item in array%}{{item}}{%endfor%}', ['array' => [1, 2, 3]]);
		$this->assertTemplateResult('123', '{% for item in array %}{{item}}{% endfor %}', ['array' => [1, 2, 3]]);
		$this->assertTemplateResult('abcd', '{%for item in array%}{{item}}{%endfor%}', ['array' => ['a', 'b', 'c', 'd']]);
		$this->assertTemplateResult('a b c', '{%for item in array%}{{item}}{%endfor%}', ['array' => ['a', ' ', 'b', ' ', 'c']]);
		$this->assertTemplateResult('abc', '{%for item in array%}{{item}}{%endfor%}', ['array' => ['a', '', 'b', '', 'c']]);
		$this->assertTemplateResult(' a ', "{%\nfor item in array%} {{item}} {%endfor%}", ['array' => ['a']]);
	}

	public function testForWithHash()
	{
		$this->assertTemplateResult('a=b c=d e=f ', '{%for item in array%}{{item[0]}}={{item[1]}} {%endfor%}', ['array' => ['a' => 'b', 'c' => 'd', 'e' => 'f']]);
	}

	public static function forLoopNameProvider()
	{
		return [
			'a collection' => ['{% for item in array %}{{ forloop.name }}{% endfor %}', ['array' => [1]], 'item-array'],
			'a range' => ['{% for item in (1..1) %}{{ forloop.name }}{% endfor %}', [], 'item-digit'],
		];
	}

	/**
	 * @dataProvider forLoopNameProvider
	 */
	public function testForLoopName(string $source, array $assigns, string $expected)
	{
		$this->assertTemplateResult($expected, $source, $assigns);
	}

	public static function forLoopRangeBoundsProvider()
	{
		return [
			'both bounds named' => ['(first..last)', ['first' => 1, 'last' => 3]],
			'a one letter name' => ['(1..z)', ['z' => 3]],
		];
	}

	/**
	 * @dataProvider forLoopRangeBoundsProvider
	 */
	public function testForLoopRangeBounds(string $range, array $assigns)
	{
		$this->assertTemplateResult('123', "{% for i in $range %}{{ i }}{% endfor %}", $assigns);
	}

	public function testForHelpersInADigitRange()
	{
		$this->assertTemplateResult(
			'2 1 0 2 1 1 0 2 2 1 1 0 0 1 2 3 2 0 -1 0 0 ',
			'{% for i in (1..3) %}'
			. '{{ forloop.length }} {{ forloop.index }} {{ forloop.index0 }}'
			. ' {{ forloop.rindex }} {{ forloop.rindex0 }}'
			. ' {{ forloop.first }} {{ forloop.last }} '
			. '{% endfor %}'
		);
	}

	public function testForLoopVariableIsLocalToADigitRange()
	{
		$this->assertTemplateResult('[]', '[{% for i in (1..3) %}{% endfor %}{{ i }}]');
	}

	public function testForLoopOverAScalar()
	{
		$this->assertTemplateResult('', '{% for i in scalar %}x{% endfor %}', ['scalar' => 5]);
	}

	public function testForHelpers()
	{
		$assigns = ['array' => [1, 2, 3]];

		$this->assertTemplateResult(' 1/3  2/3  3/3 ', '{%for item in array%} {{forloop.index}}/{{forloop.length}} {%endfor%}', $assigns);
		$this->assertTemplateResult(' 1  2  3 ', '{%for item in array%} {{forloop.index}} {%endfor%}', $assigns);
		$this->assertTemplateResult(' 0  1  2 ', '{%for item in array%} {{forloop.index0}} {%endfor%}', $assigns);
		$this->assertTemplateResult(' 2  1  0 ', '{%for item in array%} {{forloop.rindex0}} {%endfor%}', $assigns);
		$this->assertTemplateResult(' 3  2  1 ', '{%for item in array%} {{forloop.rindex}} {%endfor%}', $assigns);
		$this->assertTemplateResult(' 1  0  0 ', '{%for item in array%} {{forloop.first}} {%endfor%}', $assigns);
		$this->assertTemplateResult(' 0  0  1 ', '{%for item in array%} {{forloop.last}} {%endfor%}', $assigns);
	}

	public function testForHelpersWithOffsetAndLimit()
	{
		$assigns = ['array' => [0, 1, 2, 3, 4]];

		$this->assertTemplateResult(' 1/3  2/3  3/3 ', '{%for item in array offset:1 limit:3%} {{forloop.index}}/{{forloop.length}} {%endfor%}', $assigns);
		$this->assertTemplateResult(' 1  2  3 ', '{%for item in array offset:1 limit:3%} {{forloop.index}} {%endfor%}', $assigns);
		$this->assertTemplateResult(' 0  1  2 ', '{%for item in array offset:1 limit:3%} {{forloop.index0}} {%endfor%}', $assigns);
		$this->assertTemplateResult(' 2  1  0 ', '{%for item in array offset:1 limit:3%} {{forloop.rindex0}} {%endfor%}', $assigns);
		$this->assertTemplateResult(' 3  2  1 ', '{%for item in array offset:1 limit:3%} {{forloop.rindex}} {%endfor%}', $assigns);
		$this->assertTemplateResult(' 1  0  0 ', '{%for item in array offset:1 limit:3%} {{forloop.first}} {%endfor%}', $assigns);
		$this->assertTemplateResult(' 0  0  1 ', '{%for item in array offset:1 limit:3%} {{forloop.last}} {%endfor%}', $assigns);
	}

	public function testForAndIf()
	{
		$assigns = ['array' => [1, 2, 3]];
		$this->assertTemplateResult(' yay     ', '{%for item in array%} {% if forloop.first %}yay{% endif %} {%endfor%}', $assigns);
		$this->assertTemplateResult(' yay  boo  boo ', '{%for item in array%} {% if forloop.first %}yay{% else %}boo{% endif %} {%endfor%}', $assigns);
		$this->assertTemplateResult('   boo  boo ', '{%for item in array%} {% if forloop.first %}{% else %}boo{% endif %} {%endfor%}', $assigns);
	}

	public function testLimiting()
	{
		$assigns = ['array' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 0]];
		$this->assertTemplateResult('12', '{%for i in array limit:2 %}{{ i }}{%endfor%}', $assigns);
		$this->assertTemplateResult('1234567890', '{%for i in array limit:20 %}{{ i }}{%endfor%}', $assigns);
		$this->assertTemplateResult('1234', '{%for i in array limit:4 %}{{ i }}{%endfor%}', $assigns);
		$this->assertTemplateResult('3456', '{%for i in array limit:4 offset:2 %}{{ i }}{%endfor%}', $assigns);
		$this->assertTemplateResult('3456', '{%for i in array limit: 4  offset: 2 %}{{ i }}{%endfor%}', $assigns);

		$assigns['limit'] = 2;
		$assigns['offset'] = 2;
		$this->assertTemplateResult('34', '{%for i in array limit: limit offset: offset %}{{ i }}{%endfor%}', $assigns);
	}

	public function testNestedFor()
	{
		$assigns = ['array' => [[1, 2], [3, 4], [5, 6]]];
		$this->assertTemplateResult('123456', '{%for item in array%}{%for i in item%}{{ i }}{%endfor%}{%endfor%}', $assigns);
	}

	public function testOffsetOnly()
	{
		$assigns = ['array' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 0]];
		$this->assertTemplateResult('890', '{%for i in array offset:7 %}{{ i }}{%endfor%}', $assigns);
	}

	public function testPauseResume()
	{
		$assigns = ['array' => ['items' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 0]]];

		$markup = <<<MKUP
{%for i in array.items limit: 3 %}{{i}}{%endfor%}
next
{%for i in array.items offset:continue limit: 3 %}{{i}}{%endfor%}
next
{%for i in array.items offset:continue limit: 3 %}{{i}}{%endfor%}
MKUP;
		$expected = <<<XPCTD
123
next
456
next
789
XPCTD;
		$this->assertTemplateResult($expected, $markup, $assigns);
	}

	public function testPauseResumeLimit()
	{
		$assigns = ['array' => ['items' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 0]]];

		$markup = <<<MKUP
{%for i in array.items limit: 3 %}{{i}}{%endfor%}
next
{%for i in array.items offset:continue limit: 3 %}{{i}}{%endfor%}
next
{%for i in array.items offset:continue limit: 1 %}{{i}}{%endfor%}
MKUP;
		$expected = <<<XPCTD
123
next
456
next
7
XPCTD;
		$this->assertTemplateResult($expected, $markup, $assigns);
	}

	public function testPauseResumeBIGLimit()
	{
		$assigns = ['array' => ['items' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 0]]];

		$markup = <<<MKUP
{%for i in array.items limit: 3 %}{{i}}{%endfor%}
next
{%for i in array.items offset:continue limit: 3 %}{{i}}{%endfor%}
next
{%for i in array.items offset:continue limit: 1000 %}{{i}}{%endfor%}
MKUP;
		$expected = <<<XPCTD
123
next
456
next
7890
XPCTD;
		$this->assertTemplateResult($expected, $markup, $assigns);
	}

	public function testPauseResumeBIGOffset()
	{
		$assigns = ['array' => ['items' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 0]]];

		$markup = <<<MKUP
{%for i in array.items limit: 3 %}{{i}}{%endfor%}
next
{%for i in array.items offset:continue limit: 3 %}{{i}}{%endfor%}
next
{%for i in array.items offset:continue limit: 1000 offset:1000 %}{{i}}{%endfor%}
MKUP;
		$expected = <<<XPCTD
123
next
456
next

XPCTD;
		$this->assertTemplateResult($expected, $markup, $assigns);
	}

	public function testForWithRanges()
	{
		$this->assertTemplateResult('123456789', '{%for i in (1..9)%}{{i}}{%endfor%}');
		$this->assertTemplateResult(' 9 10 11', '{%for i in (9..11)%} {{i}}{%endfor%}');
		$this->assertTemplateResult('9991000', '{%for i in (999..1000)%}{{i}}{%endfor%}');

		$assigns = ['variable' => 100];
		$this->assertTemplateResult('9596979899100', '{%for i in (95..variable)%}{{i}}{%endfor%}', $assigns);
	}
}
