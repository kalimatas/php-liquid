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

class AbstractBlockTest extends TestCase
{
	public function testUnterminatedBlockError()
	{
		$this->expectException(\Liquid\Exception\ParseException::class);

		$this->assertTemplateResult('', '{% block }');
	}

	public static function endDelimiterProvider()
	{
		return [
			'if' => ['{% if true %}{% end %}{% endif %}', "'end' is not a valid delimiter for if tags. Use endif"],
			'for' => ['{% for i in array %}{% end %}{% endfor %}', "'end' is not a valid delimiter for for tags. Use endfor"],
			'case' => ['{% case 1 %}{% end %}{% endcase %}', "'end' is not a valid delimiter for case tags. Use endcase"],
		];
	}

	/**
	 * @dataProvider endDelimiterProvider
	 */
	public function testEndIsNotADelimiter(string $source, string $message)
	{
		$this->expectException(\Liquid\Exception\ParseException::class);
		$this->expectExceptionMessage($message);

		$this->assertTemplateResult('', $source);
	}

	public function testElseNeedsAConditional()
	{
		$this->expectException(\Liquid\Exception\ParseException::class);
		$this->expectExceptionMessage('for does not expect else tag');

		$this->assertTemplateResult('', '{% for i in array %}{% else %}{% endfor %}');
	}

	public function testWhitespaceHandler()
	{
		$this->assertTemplateResult('foo', '{% if true %}foo{% endif %}');
		$this->assertTemplateResult(' foo ', '{% if true %} foo {% endif %}');
		$this->assertTemplateResult('  foo  ', ' {% if true %} foo {% endif %} ');
		$this->assertTemplateResult('foo ', '{% if true -%} foo {% endif %}');
		$this->assertTemplateResult('foo', '{% if true -%} foo {%- endif %}');
		$this->assertTemplateResult('foo', ' {%- if true -%} foo {%- endif %}');
		$this->assertTemplateResult('foo', ' {%- if true -%} foo {%- endif -%} ');
		$this->assertTemplateResult('foo', ' {%- if true -%} foo {%- endif -%}  {%- if false -%} bar {%- endif -%} ');
		$this->assertTemplateResult('foobar', ' {%- if true -%} foo {%- endif -%}  {%- if true -%} bar {%- endif -%} ');
		$this->assertTemplateResult('-> foo', '{% if true %}-> {% endif %} {%- if true -%} foo {%- endif -%}');
	}
}
