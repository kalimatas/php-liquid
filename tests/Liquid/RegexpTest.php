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

class RegexpTest extends TestCase
{
	/** @var Regexp */
	protected $regexp;

	protected function setUp(): void
	{
		parent::setUp();

		$this->regexp = new Regexp('/' . Liquid::get('QUOTED_FRAGMENT') . '/');
	}

	public function testEmpty()
	{
		$this->assertEquals([], $this->regexp->scan(''));
	}

	public function testQuote()
	{
		$this->assertEquals(['"arg 1"'], $this->regexp->scan('"arg 1"'));
	}

	public function testWords()
	{
		$this->assertEquals(['arg1', 'arg2'], $this->regexp->scan('arg1 arg2'));
	}

	public function testQuotedWords()
	{
		$this->assertEquals(['arg1', 'arg2', '"arg 3"'], $this->regexp->scan('arg1 arg2 "arg 3"'));
	}

	public function testQuotedWords2()
	{
		$this->assertEquals(['arg1', 'arg2', "'arg 3'"], $this->regexp->scan('arg1 arg2 \'arg 3\''));
	}

	public function testQuotedWordsInTheMiddle()
	{
		$this->assertEquals(['arg1', 'arg2', '"arg 3"', 'arg4'], $this->regexp->scan('arg1 arg2 "arg 3" arg4   '));
	}

	public function testPregQuote()
	{
		$this->assertEquals('', $this->regexp->quote(''));
		$this->assertEquals('abc', $this->regexp->quote('abc'));
		$this->assertEquals('\/\(\{\}\)\/', $this->regexp->quote('/({})/'));
	}

	public function testNoDelimiter()
	{
		$regexp = new Regexp('(example)');
		$this->assertEquals(['(example)'], $regexp->scan('(example)'));
		$this->assertEquals([], $regexp->scan('nothing'));
	}
}
