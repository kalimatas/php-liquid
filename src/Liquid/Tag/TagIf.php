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

use Liquid\Decision;
use Liquid\Context;
use Liquid\Exception\ParseException;
use Liquid\Liquid;
use Liquid\FileSystem;
use Liquid\Regexp;

/**
 * An if statement
 *
 * Example:
 *
 *     {% if true %} YES {% else %} NO {% endif %}
 *
 *     will return:
 *     YES
 */
class TagIf extends Decision
{
	/**
	 * Array holding the nodes to render for each logical block
	 *
	 * @var array
	 */
	private $nodelistHolders = [];

	/**
	 * Array holding the block type, block markup (conditions) and block nodelist
	 *
	 * @var array
	 */
	protected $blocks = [];

	/**
	 * Constructor
	 *
	 * @param string $markup
	 * @param array $tokens
	 * @param FileSystem|null $fileSystem
	 */
	public function __construct($markup, array &$tokens, ?FileSystem $fileSystem = null)
	{
		$this->nodelist = & $this->nodelistHolders[count($this->blocks)];

		array_push($this->blocks, ['if', $markup, &$this->nodelist]);

		parent::__construct($markup, $tokens, $fileSystem);
	}

	/**
	 * Handler for unknown tags, handle else tags
	 *
	 * @param string $tag
	 * @param array $params
	 * @param array $tokens
	 */
	public function unknownTag($tag, $params, array $tokens)
	{
		if ($tag == 'else' || $tag == 'elsif') {
			// Update reference to nodelistHolder for this block
			$this->nodelist = & $this->nodelistHolders[count($this->blocks) + 1];
			$this->nodelistHolders[count($this->blocks) + 1] = [];

			array_push($this->blocks, [$tag, $params, &$this->nodelist]);
		} else {
			parent::unknownTag($tag, $params, $tokens);
		}
	}

	/**
	 * Render the tag
	 *
	 * @param Context $context
	 *
	 * @throws \Liquid\Exception\ParseException
	 * @return string
	 */
	public function render(Context $context)
	{
		$context->push();

		$conditionalRegex = new Regexp('/(' . Liquid::get('QUOTED_FRAGMENT') . ')\s*([=!<>a-z_]+)?\s*(' . Liquid::get('QUOTED_FRAGMENT') . ')?/');

		$result = '';

		foreach ($this->blocks as $block) {
			if ($block[0] == 'else') {
				$result = $this->renderAll($block[2], $context);
				break;
			}

			if ($block[0] == 'if' || $block[0] == 'elsif') {
				$markup = $block[1];

				// quote-aware condition & operator parsing
				$parsed      = $this->parseConditionsAndOperators($markup, $conditionalRegex);
				$conditions  = $parsed['conditions'];
				$operators   = $parsed['operators'];

				if (count($conditions) === 0) {
					throw new ParseException("Syntax Error in tag 'if' - Valid syntax: if [condition]");
				}

				// Evaluate first condition
				$display = $this->interpretCondition(
					$conditions[0]['left'],
					$conditions[0]['right'],
					$conditions[0]['operator'],
					$context
				);

				// Apply subsequent conditions with logical operators
				foreach ($operators as $index => $logicalOperator) {
					$next = $this->interpretCondition(
						$conditions[$index + 1]['left'],
						$conditions[$index + 1]['right'],
						$conditions[$index + 1]['operator'],
						$context
					);

					if ($logicalOperator === 'and') {
						$display = $display && $next;
					} else {
						$display = $display || $next;
					}
				}

				// hook for unless tag
				$display = $this->negateIfUnless($display);

				if ($display) {
					$result = $this->renderAll($block[2], $context);
					break;
				}
			}
		}

		$context->pop();

		return $result;
	}

	protected function negateIfUnless($display)
	{
		// no need to negate a condition in a regular `if` tag (will do that in `unless` tag)
		return $display;
	}

	private function parseConditionsAndOperators(string $markup, Regexp $conditionalRegex): array
	{
		$len       = strlen($markup);
		$inString  = false;
		$quote     = null;
		$buffer    = '';
		$fragments = [];
		$operators = [];

		for ($i = 0; $i < $len; $i++) {
			$ch = $markup[$i];

			// Track entering/leaving string literals
			if ($ch === "'" || $ch === '"') {
				if (!$inString) {
					$inString = true;
					$quote    = $ch;
				} elseif ($quote === $ch) {
					$inString = false;
					$quote    = null;
				}

				$buffer .= $ch;
				continue;
			}

			if (!$inString) {
				// Look for logical " and " outside quotes
				if (substr($markup, $i, 5) === ' and ') {
					$fragments[] = trim($buffer);
					$buffer      = '';
					$operators[] = 'and';
					$i          += 4; // skip " and" (loop will add 1 more)
					continue;
				}

				// Look for logical " or " outside quotes
				if (substr($markup, $i, 4) === ' or ') {
					$fragments[] = trim($buffer);
					$buffer      = '';
					$operators[] = 'or';
					$i          += 3; // skip " or" (loop will add 1 more)
					continue;
				}
			}

			// Default: just accumulate characters
			$buffer .= $ch;
		}

		if (trim($buffer) !== '') {
			$fragments[] = trim($buffer);
		}

		$conditions = [];

		foreach ($fragments as $fragment) {
			if ($conditionalRegex->match($fragment)) {
				$left     = isset($conditionalRegex->matches[1]) ? $conditionalRegex->matches[1] : null;
				$operator = isset($conditionalRegex->matches[2]) ? $conditionalRegex->matches[2] : null;
				$right    = isset($conditionalRegex->matches[3]) ? $conditionalRegex->matches[3] : null;

				$conditions[] = [
					'left'     => $left,
					'operator' => $operator,
					'right'    => $right,
				];
			} else {
				throw new ParseException("Syntax Error in tag 'if' - Valid syntax: if [condition]");
			}
		}

		return ['conditions' => $conditions, 'operators' => $operators];
	}
}
