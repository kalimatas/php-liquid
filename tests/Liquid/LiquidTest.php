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
}
