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

class MoneyFilter
{
	public Context $context;

	public function money($value)
	{
		return sprintf(' %d$ ', $value);
	}

	public function money_with_underscore($value)
	{
		return sprintf(' %d$ ', $value);
	}
}

class CanadianMoneyFilter
{
	public Context $context;

	public function money($value)
	{
		return sprintf(' %d$ CAD ', $value);
	}
}

class SizeClass
{
	const SIZE = 42;

	public function toLiquid()
	{
		return $this;
	}

	public function size()
	{
		return self::SIZE;
	}

	public function __toString()
	{
		return "forty two";
	}
}


class StandardFiltersTest extends TestCase
{
	/**
	 * The current context
	 *
	 * @var Context
	 */
	public $context;

	protected function setUp(): void
	{
		parent::setUp();

		$this->context = new Context();
	}

	public function testSize()
	{
		$data = [
			4 => 1000,
			3 => 100,
			2 => ['one', 'two'],
			1 => new \ArrayIterator(['one']),
			SizeClass::SIZE => new SizeClass(),
		];

		foreach ($data as $expected => $element) {
			$this->assertEquals($expected, StandardFilters::size($element));
		}
	}

	/**
	 */
	public function testSizeObject()
	{
		$this->expectException(\Liquid\LiquidException::class);
		$this->expectExceptionMessage('cannot be estimated');

		StandardFilters::size((object) []);
	}

	public function testDowncase()
	{
		$data = [
			'UpperCaseMiXed' => 'uppercasemixed',
			3 => 3,
			// UTF-8
			'Владимир' => 'владимир',
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::downcase($element));
		}
	}

	public function testUpcase()
	{
		$data = [
			'UpperCaseMiXed' => 'UPPERCASEMIXED',
			3 => 3,
			// UTF-8
			'владимир' => 'ВЛАДИМИР',
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::upcase($element));
		}
	}

	public function testCapitalize()
	{
		$data = [
			'one Word not' => 'One word not',
			'1test' => '1test',
			'' => '',
			// UTF-8
			'владимир владимирович' => 'Владимир владимирович',
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::capitalize($element));
		}
	}

	public function testUrlEncode()
	{
		$data = [
			'nothing' => 'nothing',
			'%#&^' => '%25%23%26%5E',
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::url_encode($element));
		}
	}


	public function testUrlDecode()
	{
		$data = [
			'%25%23%26%5E' => '%#&^',
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::url_decode($element));
		}
	}


	public function testRaw()
	{
		$data = [
			"Anything" => "Anything",
			3 => 3,
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::raw($element));
		}
	}

	public function testJson()
	{
		$data = [
			[
				"before" => "Anything",
				"after" => "\"Anything\"",
			],
			[
				"before" => 3,
				"after" => 3,
			],
			[
				"before" => [1, 2, 3],
				"after" => "[1,2,3]",
			],
			[
				"before" => ["one" => 1, "two" => 2, "three" => 3],
				"after" => "{\"one\":1,\"two\":2,\"three\":3}",
			],
			[
				"before" => ["one" => 1, "two" => [1, 2, 3], "three" => 3],
				"after" => "{\"one\":1,\"two\":[1,2,3],\"three\":3}",
			],
		];

		foreach ($data as $testCase) {
			$this->assertEquals($testCase['after'], StandardFilters::json($testCase['before']));
		}
	}

	public function testWhere()
	{
		$data = [
			[
				'before' => [['model' => 'bmw'], ['model' => 'audi']],
				'after' => [['model' => 'bmw']],
			],
			[
				'before' => ['model' => 'bmw'],
				'after' => [],
			],
		];

		foreach ($data as $testCase) {
			$this->assertEquals($testCase['after'], StandardFilters::where($testCase['before'], 'model', 'bmw'));
		}
	}

	public function testEscape()
	{
		$data = [
			"one Word's not" => "one Word&#039;s not",
			"&><\"'" => "&amp;&gt;&lt;&quot;&#039;",
			null => '',
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::escape($element));
		}

		$this->assertSame([1], StandardFilters::escape([1]));
	}

	public function testEscapeOnce()
	{
		$data = [
			"<b><script>alert()</script>" => "&lt;b&gt;&lt;script&gt;alert()&lt;/script&gt;",
			"a < b & c" => "a &lt; b &amp; c",
			"a &lt; b &amp; c" => "a &lt; b &amp; c",
			"&lt;\">" => "&lt;&quot;&gt;",
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::escape_once($element));
		}

		$this->assertSame([1], StandardFilters::escape_once([1]));
	}

	public function testStripNewLines()
	{
		$data = [
			"one Word\r\n not\r\n\r\n" => "one Word not",
			'test' => 'test',
			3 => 3,
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::strip_newlines($element));
		}
	}

	public function testNewLineToBr()
	{
		$data = [
			"one Word\n not\n" => "one Word<br />\n not<br />\n",
			'test' => 'test',
			3 => 3,
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::newline_to_br($element));
		}
	}

	public function testReplace()
	{
		// Replace for empty string
		$data = [
			"one Word not Word" => "one  not ",
			'test' => 'test',
			3 => 3,
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::replace($element, 'Word'));
		}

		// Replace for "Hello" string
		$data = [
			"one Word not Word" => "one Hello not Hello",
			'test' => 'test',
			3 => 3,
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::replace($element, 'Word', 'Hello'));
		}
	}

	public function testReplaceFirst()
	{
		// Replace for empty string
		$data = [
			"one Word not Word" => "one  not Word",
			'test' => 'test',
			3 => 3,
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::replace_first($element, 'Word'));
		}

		// Replace for "Hello" string
		$data = [
			"one Word not Word" => "one Hello not Word",
			'test' => 'test',
			3 => 3,
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::replace_first($element, 'Word', 'Hello'));
		}
	}

	public function testRemove()
	{
		$data = [
			"one Word not Word" => "one  not ",
			'test' => 'test',
			3 => 3,
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::remove($element, 'Word'));
		}
	}

	public function testRemoveFirst()
	{
		$data = [
			"one Word not Word" => "one  not Word",
			'test' => 'test',
			3 => 3,
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::remove_first($element, 'Word'));
		}
	}

	public function testAppend()
	{
		$data = [
			"one Word not Word" => "one Word not Word appended",
			'' => ' appended',
			3 => '3 appended',
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::append($element, ' appended'));
		}
	}

	public function testPrepend()
	{
		$data = [
			"one Word not Word" => "prepended one Word not Word",
			'' => 'prepended ',
			3 => 'prepended 3',
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::prepend($element, 'prepended '));
		}
	}

	public function testSlice()
	{
		// Slice up to the end
		$data = [
			[
				[],
				[],
			],
			[
				new \ArrayIterator([]),
				[],
			],
			[
				'',
				'',
			],
			[
				[1, 2, 3, 4, 5],
				[3, 4, 5],
			],
			[
				new \ArrayIterator([1, 2, 3, 4, 5]),
				[3, 4, 5],
			],
			[
				'12345',
				'345',
			],
			[
				100,
				100,
			],
		];

		foreach ($data as $item) {
			$actual = StandardFilters::slice($item[0], 2);
			if ($actual instanceof \Traversable) {
				$actual = iterator_to_array($actual);
			}
			$this->assertEquals($item[1], $actual);
		}

		// Slice a few elements
		$data = [
			[
				null,
				null,
			],
			[
				[],
				[],
			],
			[
				new \ArrayIterator([]),
				[],
			],
			[
				'',
				'',
			],
			[
				[1, 2, 3, 4, 5],
				[3, 4],
			],
			[
				new \ArrayIterator([1, 2, 3, 4, 5]),
				[3, 4],
			],
			[
				'12345',
				'34',
			],
			[
				100,
				100,
			],
		];

		foreach ($data as $item) {
			$actual = StandardFilters::slice($item[0], 2, 2);
			if ($actual instanceof \Traversable) {
				$actual = iterator_to_array($actual);
			}
			$this->assertEquals($item[1], $actual);
		}

		$this->assertEquals('Владимир', StandardFilters::slice('Владимир Владимирович', 0, 8));
	}

	public function testTruncate()
	{
		// Truncate with default ending
		$data = [
			'' => '',
			str_repeat('a', 150) => str_repeat('a', 100) . '...',
			'test' => 'test',
			3 => 3,
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::truncate($element));
		}

		// Custom length
		$this->assertEquals('abc...', StandardFilters::truncate('abcdef', 3));

		// Custom ending
		$this->assertEquals('abcend', StandardFilters::truncate('abcdef', 3, 'end'));

		// UTF-8
		$this->assertEquals('Влад...', StandardFilters::truncate('Владимир Владимирович', 4));
	}

	public function testTruncateWords()
	{
		// Truncate with default ending
		$data = [
			'' => '',
			str_repeat('abc ', 10) => rtrim(str_repeat('abc ', 3)) . '...',
			'test two' => 'test two',
			3 => 3,
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::truncatewords($element));
		}

		// Custom length
		$this->assertEquals('hello...', StandardFilters::truncatewords('hello from string', 1));

		// Custom ending
		$this->assertEquals('helloend', StandardFilters::truncatewords('hello from string', 1, 'end'));
	}

	public function testStripHtml()
	{
		$data = [
			'' => '',
			'test no html tags' => 'test no html tags',
			'test <br /> <p>paragraph</p> hello' => 'test  paragraph hello',
			3 => 3,
		];

		foreach ($data as $element => $expected) {
			$this->assertEquals($expected, StandardFilters::strip_html($element));
		}
	}

	public function testJoin()
	{
		$data = [
			[
				[],
				'',
			],
			[
				new \ArrayIterator([]),
				'',
			],
			[
				'',
				'',
			],
			[
				[1, 2, 3, 4, 5],
				'1 2 3 4 5',
			],
			[
				new \ArrayIterator([1, 2, 3, 4, 5]),
				'1 2 3 4 5',
			],
			[
				100,
				100,
			],
		];

		foreach ($data as $item) {
			$this->assertEquals($item[1], StandardFilters::join($item[0]));
		}

		// Custom glue
		$this->assertEquals('1-2-3', StandardFilters::join([1, 2, 3], '-'));
		$this->assertEquals('1-2-3', StandardFilters::join(new \ArrayIterator([1, 2, 3]), '-'));
	}

	public function testSort()
	{
		$data = [
			[
				[],
				[],
			],
			[
				new \ArrayIterator([]),
				[],
			],
			[
				[1, 5, 3, 4, 2],
				[1, 2, 3, 4, 5],
			],
			[
				new \ArrayIterator([1, 5, 3, 4, 2]),
				[1, 2, 3, 4, 5],
			],
		];

		foreach ($data as $key => $item) {
			$this->assertEquals(array_values($item[1]), array_values(StandardFilters::sort($item[0])), "Sort failed for case #{$key}");
		}

		// Sort by inner key
		$original = [
			['a' => 20, 'b' => 10],
			['a' => 45, 'b' => 5],
			['a' => 40, 'b' => 6],
			['a' => 30, 'b' => 48],
		];
		$expected = [
			['a' => 45, 'b' => 5],
			['a' => 40, 'b' => 6],
			['a' => 20, 'b' => 10],
			['a' => 30, 'b' => 48],
		];

		$this->assertEquals($expected, array_values(StandardFilters::sort($original, 'b')));
		$this->assertEquals($expected, array_values(StandardFilters::sort(new \ArrayIterator($original), 'b')));
	}

	public function testSortWithoutKey()
	{
		// Sort by inner key
		$original = [
			['a' => 20, 'b' => 10],
			['a' => 45, 'b' => 5],
			['a' => 40, 'b' => 6],
			['a' => 30, 'b' => 48],
			['a' => 50],
		];
		$expected = [
			['a' => 50],
			['a' => 45, 'b' => 5],
			['a' => 40, 'b' => 6],
			['a' => 20, 'b' => 10],
			['a' => 30, 'b' => 48],
		];

		$this->assertEquals($expected, array_values(StandardFilters::sort($original, 'b')));
		$this->assertEquals($expected, array_values(StandardFilters::sort(new \ArrayIterator($original), 'b')));
	}

	/*

		I've commented this out as its not one of the Ruby Standard Filters

		public function testSortKey() {
			$data = array(
				array(
					array(),
					array(),
				),
				array(
					array('b' => 1, 'c' => 5, 'a' => 3, 'z' => 4, 'h' => 2),
					array('a' => 3, 'b' => 1, 'c' => 5, 'h' => 2, 'z' => 4),
				),
			);

			foreach ($data as $item) {
				$this->assertEquals($item[1], StandardFilters::sort_key($item[0]));
			}
		}
	*/

	public function testDefault()
	{
		$this->assertEquals('hello', StandardFilters::_default('', 'hello'));
		$this->assertEquals('world', StandardFilters::_default('world', 'hello'));
		// check that our workaround for 'default' works as it should
		$this->assertTemplateResult('something', '{{ nothing | default: "something" }}');
	}

	public function testUnique()
	{
		$data = [
			[
				[],
				[],
			],
			[
				new \ArrayIterator([]),
				[],
			],
			[
				[1, 1, 5, 3, 4, 2, 5, 2],
				[1, 5, 3, 4, 2],
			],
			[
				new \ArrayIterator([1, 1, 5, 3, 4, 2, 5, 2]),
				[1, 5, 3, 4, 2],
			],
		];

		foreach ($data as $item) {
			$this->assertEquals($item[1], array_values(StandardFilters::uniq($item[0])));
		}
	}

	public function testReverse()
	{
		$data = [
			[
				[],
				[],
			],
			[
				new \ArrayIterator([]),
				[],
			],
			[
				[1, 1, 5, 3, 4, 2, 5, 2],
				[2, 5, 2, 4, 3, 5, 1, 1],
			],
			[
				new \ArrayIterator([1, 1, 5, 3, 4, 2, 5, 2]),
				[2, 5, 2, 4, 3, 5, 1, 1],
			],
		];

		foreach ($data as $item) {
			$this->assertEquals($item[1], StandardFilters::reverse($item[0]), '', 0, 10, true);
		}
	}

	public function testMap()
	{
		$data = [
			[
				[],
				[],
			],
			[
				new \ArrayIterator([]),
				[],
			],
			[
				[
					function () {
						return 'from function ';
					},
					[
						'b' => 10,
						'attr' => 'value ',
					],
					[
						'a' => 20,
						'no_attr' => 'another value ',
					],
				],
				['from function ', 'value ', null],
			],
			[
				new \ArrayIterator([
					function () {
						return 'from function ';
					},
					[
						'b' => 10,
						'attr' => 'value ',
					],
					[
						'a' => 20,
						'no_attr' => 'another value ',
					],
				]),
				['from function ', 'value ', null],
			],
			[
				0,
				0,
			],
		];

		foreach ($data as $item) {
			$actual = StandardFilters::map($item[0], 'attr');
			if ($actual instanceof \Traversable) {
				$actual = iterator_to_array($actual);
			}
			$this->assertEquals($item[1], $actual);
		}
	}

	public function testFirst()
	{
		$data = [
			[
				[],
				false,
			],
			[
				new \ArrayIterator([]),
				false,
			],
			[
				['two', 'one', 'three'],
				'two',
			],
			[
				new \ArrayIterator(['two', 'one', 'three']),
				'two',
			],
			[
				[100, 400, 200],
				100,
			],
			[
				new \ArrayIterator([100, 400, 200]),
				100,
			],
		];

		foreach ($data as $item) {
			$this->assertEquals($item[1], StandardFilters::first($item[0]));
		}
	}

	public function testLast()
	{
		$data = [
			[
				[],
				false,
			],
			[
				new \ArrayIterator([]),
				false,
			],
			[
				['two', 'one', 'three'],
				'three',
			],
			[
				new \ArrayIterator(['two', 'one', 'three']),
				'three',
			],
			[
				[100, 400, 200],
				200,
			],
			[
				new \ArrayIterator([100, 400, 200]),
				200,
			],
		];

		foreach ($data as $item) {
			$this->assertEquals($item[1], StandardFilters::last($item[0]));
		}
	}

	public function testString()
	{
		$data = [
				[
						1,
						'1',
				],
				[
						new SizeClass(),
						"forty two",
				],
		];

		foreach ($data as $item) {
			$this->assertEquals($item[1], StandardFilters::string($item[0]));
		}
	}

	public function testSplit()
	{
		$data = [
			[
				'',
				[],
			],
			[
				null,
				[],
			],
			[
				'two-one-three',
				['two', 'one', 'three'],
			],
			[
				'12301230123',
				['123', '123', '123'],
				'0',
			],
			[
				'phrase',
				['p', 'h', 'r', 'a', 's', 'e'],
				'',
			],
			[
				'phrase',
				['phrase'],
				null,
			],
			[
				'123 123 123',
				['123', '123', '123'],
				' ',
			],
		];

		foreach ($data as $item) {
			$this->assertEquals($item[1], StandardFilters::split($item[0], $item[2] ?? '-'));
		}
	}

	public function testStrip()
	{
		$data = [
			[
				'',
				'',
			],
			[
				' hello   ',
				'hello',
			],
			[
				1,
				1,
			],
		];

		foreach ($data as $item) {
			$this->assertEquals($item[1], StandardFilters::strip($item[0]));
		}
	}

	public function testLStrip()
	{
		$data = [
			[
				'',
				'',
			],
			[
				' hello   ',
				'hello   ',
			],
			[
				1,
				1,
			],
		];

		foreach ($data as $item) {
			$this->assertEquals($item[1], StandardFilters::lstrip($item[0]));
		}
	}

	public function testRStrip()
	{
		$data = [
			[
				'',
				'',
			],
			[
				' hello   ',
				' hello',
			],
			[
				1,
				1,
			],
		];

		foreach ($data as $item) {
			$this->assertEquals($item[1], StandardFilters::rstrip($item[0]));
		}
	}

	public function testPlus()
	{
		$data = [
			[
				'',
				'',
				0,
			],
			[
				10,
				20,
				30,
			],
			[
				1.5,
				2.7,
				4.2,
			],
		];

		foreach ($data as $item) {
			$this->assertEqualsWithDelta($item[2], StandardFilters::plus($item[0], $item[1]), 0.00001);
		}
	}

	public function testMinus()
	{
		$data = [
			[
				'',
				'',
				0,
			],
			[
				10,
				20,
				-10,
			],
			[
				1.5,
				2.7,
				-1.2,
			],
			[
				3.1,
				3.1,
				0,
			],
		];

		foreach ($data as $item) {
			$this->assertEqualsWithDelta($item[2], StandardFilters::minus($item[0], $item[1]), 0.00001);
		}
	}

	public function testTimes()
	{
		$data = [
			[
				'',
				'',
				0,
			],
			[
				10,
				20,
				200,
			],
			[
				1.5,
				2.7,
				4.05,
			],
			[
				  7.5,
				  0,
				  0,
			],
		];

		foreach ($data as $item) {
			$this->assertEqualsWithDelta($item[2], StandardFilters::times($item[0], $item[1]), 0.00001);
		}
	}

	public function testDivideBy()
	{
		$data = [
			[
				'20',
				10,
				2,
			],
			[
				10,
				20,
				0.5,
			],
			[
				0,
				200,
				0,
			],
			[
				10,
				0.5,
				20,
			],
		];

		foreach ($data as $item) {
			$this->assertEqualsWithDelta($item[2], StandardFilters::divided_by($item[0], $item[1]), 0.00001);
		}
	}

	public function testModulo()
	{
		$data = [
			[
				'20',
				10,
				0,
			],
			[
				10,
				20,
				10,
			],
			[
				8,
				3,
				2,
			],
			[
				8.9,
				3.5,
				1.9,
			],
			[
				183.357,
				12,
				3.357,
			],
		];

		foreach ($data as $item) {
			$this->assertEqualsWithDelta($item[2], StandardFilters::modulo($item[0], $item[1]), 0.00001);
		}
	}

	public function testRound()
	{
		$data = [
			[
				'20.003',
				2,
				20.00,
			],
			[
				10,
				3,
				10.000,
			],
			[
				8,
				0,
				8.0,
			],
		];

		foreach ($data as $item) {
			$this->assertSame($item[2], StandardFilters::round($item[0], $item[1]));
		}
	}

	public function testCeil()
	{
		$data = [
			[
				'20.003',
				21,
			],
			[
				10,
				10,
			],
			[
				0.42,
				1,
			],
		];

		foreach ($data as $item) {
			$this->assertSame($item[1], StandardFilters::ceil($item[0]));
		}
	}

	public function testFloor()
	{
		$data = [
			[
				'20.003',
				20,
			],
			[
				10,
				10,
			],
			[
				0.42,
				0,
			],
			[
				2.5,
				2,
			],
		];

		foreach ($data as $item) {
			$this->assertSame($item[1], StandardFilters::floor($item[0]));
		}
	}

	public function testLocalFilter()
	{
		$var = new Variable('var | money');
		$this->context->set('var', 1000);
		$this->context->addFilters(new MoneyFilter());
		$this->assertEquals(' 1000$ ', $var->render($this->context));
	}

	public function testUnderscoreInFilterName()
	{
		$var = new Variable('var | money_with_underscore ');
		$this->context->set('var', 1000);
		$this->context->addFilters(new MoneyFilter());
		$this->assertEquals(' 1000$ ', $var->render($this->context));
	}

	public function testSecondFilterOverwritesFirst()
	{
		$var = new Variable('var | money ');
		$this->context->set('var', 1000);
		$this->context->addFilters(new MoneyFilter());
		$this->context->addFilters(new CanadianMoneyFilter());
		$this->assertEquals(' 1000$ CAD ', $var->render($this->context));
	}

	public function testDate()
	{
		$dateVar = '2017-07-01 21:00:00';

		$var = new Variable('var | date, "%Y"');
		$this->context->set('var', $dateVar);
		$this->assertEquals('2017', $var->render($this->context));

		$var = new Variable("var | date: '%d/%m/%Y %l:%M %p'");
		$this->context->set('var', $dateVar);
		$this->assertEquals('01/07/2017 9:00 PM', $var->render($this->context));

		$var = new Variable('var | date, ""');
		$this->context->set('var', $dateVar);
		$this->assertEquals($dateVar, $var->render($this->context));

		$var = new Variable('var | date, "%Y-%m-%d %H:%M:%S"');
		$this->context->set('var', 1498942800);
		$this->assertEquals($dateVar, $var->render($this->context));
	}
}
