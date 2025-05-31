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

use ReflectionClass;
use function array_filter;
use function class_exists;
use function file;
use function interface_exists;
use function join;
use function trait_exists;
use function var_dump;

/**
 * @coversNothing
 */
class StaticAnalysisTest extends TestCase
{
	/**
	 * @return iterable
	 */
	public static function provideClasses()
	{
		$files = require 'vendor/composer/autoload_classmap.php';

		$seenNonVendor = false;
		foreach ($files as $class => $filename) {
			$path = str_replace(getcwd(), '.', $filename);

			if (strpos($path, './vendor/') === 0) {
				continue;
			}

			$seenNonVendor = true;
			yield $class => [str_replace(getcwd(), '.', $filename), $class];
		}

		if ($seenNonVendor === false) {
			throw new \RuntimeException('Please generate the classmap.');
		}
	}

	/**
	 * @dataProvider provideClasses
	 * @param mixed $filename
	 * @param mixed $class
	 */
	public function testClassExists($filename, $class)
	{
		$this->assertTrue(class_exists($class) || trait_exists($class) || interface_exists($class));
	}

	public static function provideAbstractTagSubclasses()
	{
		foreach (self::provideClasses() as $class => $data) {
			if (!is_subclass_of($class, AbstractTag::class)) {
				continue;
			}

			$refClass = new ReflectionClass($class);
			if (!$refClass->hasMethod('__construct')) {
				continue;
			}

			yield $class => [...$data, $refClass];
		}
	}

	/**
	 * @dataProvider provideAbstractTagSubclasses
	 * @param string $filename
	 * @param class-string<AbstractTag> $class
	 * @param ReflectionClass<AbstractTag> $refClass
	 */
	public function testAbstractTagChildCallsConstruct($filename, $class, ReflectionClass $refClass)
	{
		$refMethod = $refClass->getMethod('__construct');
		$startLine = $refMethod->getStartLine();
		$endLine = $refMethod->getEndLine();

		$code = array_slice(file($refMethod->getFileName()), $startLine - 1, $endLine - $startLine + 1);

		$code = array_filter($code, function ($line) {
			return strpos($line, 'parent::__construct') !== false;
		});

		$this->assertNotEmpty(
			$code,
			"The constructor of $class should call parent::__construct()"
		);
	}
}
