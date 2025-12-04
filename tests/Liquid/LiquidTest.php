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

class LiquidTest extends TestCase
{
	public function testGetNonExistingPropery()
	{
		$this->assertNull(Liquid::get('no_such_value'));
	}

	public function testSetProperty()
	{
		$key = 'test_key';
		$value = 'test_value';
		Liquid::set($key, $value);
		$this->assertSame($value, Liquid::get($key));
	}

	public function testGetSetAllowedChars()
	{
		Liquid::set('ALLOWED_VARIABLE_CHARS', 'abc');
		$this->assertSame('abc', Liquid::get('ALLOWED_VARIABLE_CHARS'));
		$this->assertSame('abc+', Liquid::get('VARIABLE_NAME'));
	}

	public function testArrayFlattenEmptyArray()
	{
		$this->assertSame([], Liquid::arrayFlatten([]));
	}

	public function testArrayFlattenFlatArray()
	{
		$object = new \stdClass();

		// Method does not maintain keys.
		$original = [
			'one' => 'one_value',
			42,
			$object,
		];

		$expected = [
			'one_value',
			42,
			$object,
		];

		$this->assertEquals($expected, Liquid::arrayFlatten($original));
	}

	public function testArrayFlattenNestedArray()
	{
		$object = new \stdClass();

		// Method does not maintain keys.
		$original = [
			'one' => 'one_value',
			42 => [
				'one_value',
				[
					'two_value',
					10,
				],
			],
			$object,
		];

		$expected = [
			'one_value',
			'one_value',
			'two_value',
			10,
			$object,
		];

		$this->assertEquals($expected, Liquid::arrayFlatten($original));
	}

	public function testArrayFlattenSkipHash()
	{
		$original = [
			[['attr' => 1], ['attr' => 2]],
			[['attr' => 3]],
		];

		$expected = [
			['attr' => 1],
			['attr' => 2],
			['attr' => 3],
		];

		$this->assertEquals($expected, Liquid::arrayFlatten($original, true));
	}

	public function testArrayFlattenSkipHashMixedContent()
	{
		$original = [
			['name' => 'John', 'age' => 30],
			[1, 2, 3],
			['key' => 'value'],
		];

		$expected = [
			['name' => 'John', 'age' => 30],
			1,
			2,
			3,
			['key' => 'value'],
		];

		$this->assertEquals($expected, Liquid::arrayFlatten($original, true));
	}

	public function testIsHashWithEmptyArray()
	{
		$this->assertFalse(Liquid::isHash([]));
	}

	public function testIsHashWithIndexedArray()
	{
		$this->assertFalse(Liquid::isHash(['a', 'b', 'c']));
		$this->assertFalse(Liquid::isHash([0 => 'a', 1 => 'b', 2 => 'c']));
	}

	public function testIsHashWithAssociativeArray()
	{
		$this->assertTrue(Liquid::isHash(['name' => 'John']));
		$this->assertTrue(Liquid::isHash(['a' => 1, 'b' => 2]));
	}

	public function testIsHashWithNonSequentialKeys()
	{
		$this->assertTrue(Liquid::isHash([1 => 'a', 2 => 'b']));
		$this->assertTrue(Liquid::isHash([0 => 'a', 2 => 'b']));
	}

	public function testIsIntegerWithIntType()
	{
		$this->assertTrue(Liquid::isInteger(20));
		$this->assertTrue(Liquid::isInteger(0));
		$this->assertTrue(Liquid::isInteger(-5));
	}

	public function testIsIntegerWithStringInteger()
	{
		$this->assertTrue(Liquid::isInteger('20'));
		$this->assertTrue(Liquid::isInteger('0'));
		$this->assertTrue(Liquid::isInteger('-5'));
	}

	public function testIsIntegerWithFloat()
	{
		$this->assertFalse(Liquid::isInteger(20.0));
		$this->assertFalse(Liquid::isInteger(20.5));
		$this->assertFalse(Liquid::isInteger(-5.0));
	}

	public function testIsIntegerWithStringFloat()
	{
		$this->assertFalse(Liquid::isInteger('20.0'));
		$this->assertFalse(Liquid::isInteger('20.5'));
		$this->assertFalse(Liquid::isInteger('-5.5'));
	}

	public function testIsIntegerWithInvalidValues()
	{
		$this->assertFalse(Liquid::isInteger(''));
		$this->assertFalse(Liquid::isInteger('-'));
		$this->assertFalse(Liquid::isInteger('abc'));
		$this->assertFalse(Liquid::isInteger(null));
		$this->assertFalse(Liquid::isInteger([]));
	}
}
