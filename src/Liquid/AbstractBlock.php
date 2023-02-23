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

use Liquid\Exception\ParseException;
use Liquid\Exception\RenderException;

/**
 * Base class for blocks.
 */
class AbstractBlock extends AbstractTag
{
	const TAG_PREFIX = '\Liquid\Tag\Tag';

	/**
	 * @var AbstractTag[]|Variable[]|string[]
	 */
	protected $nodelist = array();

	/**
	 * Whenever next token should be ltrim'med.
	 *
	 * @var bool
	 */
	protected static $trimWhitespace = false;

	/**
	 * @return array
	 */
	public function getNodelist()
	{
		return $this->nodelist;
	}

	/**
	 * Parses the given tokens
	 *
	 * @param array $tokens
	 *
	 * @throws \Liquid\LiquidException
	 * @return void
	 */
	public function parse(array &$tokens)
	{
		$this->nodelist = array();
		$tags = Template::getTags();

		if (!isset(Liquid::$config['tokenRegex'])) {
			Liquid::$config['tokenRegex'] = '/(' . Liquid::$config['TAG_START'] . ')?(?(1)(' . Liquid::$config['WHITESPACE_CONTROL'] . ')?\s*(\w+)|(' . Liquid::$config['VARIABLE_START'] . ')(' . Liquid::$config['WHITESPACE_CONTROL'] . ')?)\s*(.*?)\s*(' . Liquid::$config['WHITESPACE_CONTROL'] . ')?((?(1)' . Liquid::$config['TAG_END'] . '|' . Liquid::$config['VARIABLE_END'] . '))/s';
		}

		for ($i = 0, $n = count($tokens); $i < $n; $i++) {
			if ($tokens[$i] === null) {
				continue;
			}
			$token = $tokens[$i];
			$tokens[$i] = null;

			if (preg_match(Liquid::$config['tokenRegex'], $token, $tokenParts)) {

				// $tokenParts[1]: Start tag
				// $tokenParts[2]: Whitespace control for tag start
				// $tokenParts[3]: Tag name
				// $tokenParts[4]: Variable start
				// $tokenParts[5]: Whitespace control for variable start
				// $tokenParts[6]: Tag/variable markup
				// $tokenParts[7]: Whitespace control for tag/variable end
				// $tokenParts[8]: End tag/variable

				if ($tokenParts[1] == '{%') {
					$this->whitespaceHandler($tokenParts);
					if ($tokenParts[8] == '%}') {
						// If we found the proper block delimitor just end parsing here and let the outer block proceed
						if ($tokenParts[3] == $this->blockDelimiter()) {
							$this->endTag();
							return;
						}

						$tagName = $tags[$tokenParts[3]] ?? null;
						if ($tagName === null) {
							$tagName = self::TAG_PREFIX . ucwords($tokenParts[3]);
							$tagName = class_exists($tagName) ? $tagName : null;
						}

						if ($tagName !== null) {
							$this->nodelist[] = new $tagName($tokenParts[6], $tokens, $this->fileSystem);
							if ($tokenParts[3] == 'extends') {
								return;
							}
						} else {
							$this->unknownTag($tokenParts[3], $tokenParts[6], $tokens);
						}
					} else {
						throw new ParseException("Tag $token was not properly terminated");
					}
				} elseif ($tokenParts[4] == '{{') {
					if ($tokenParts[8] == '}}') {
						$this->whitespaceHandler($tokenParts);
						$this->nodelist[] = new Variable($tokenParts[6]);
					} else {
						throw new ParseException("Variable $token was not properly terminated");
					}
				}
			} else {
				// This is neither a tag or a variable, proceed with an ltrim
				$this->nodelist[] = self::$trimWhitespace ? ltrim($token) : $token;
				self::$trimWhitespace = false;
			}
		}

		$this->assertMissingDelimitation();
	}

	/**
	 * Handle the whitespace.
	 *
	 * @param string[] $tokenParts
	 */
	protected function whitespaceHandler($tokenParts)
	{
		if ($tokenParts[2] === Liquid::$config['WHITESPACE_CONTROL'] || $tokenParts[5] === Liquid::$config['WHITESPACE_CONTROL']) {
			$previousToken = end($this->nodelist);
			if (is_string($previousToken)) { // this can also be a tag or a variable
				$this->nodelist[key($this->nodelist)] = rtrim($previousToken);
			}
		}

		self::$trimWhitespace = $tokenParts[7] === Liquid::$config['WHITESPACE_CONTROL'];
	}

	/**
	 * Render the block.
	 *
	 * @param Context $context
	 *
	 * @return string
	 */
	public function render(Context $context)
	{
		return $this->renderAll($this->nodelist, $context);
	}

	/**
	 * Renders all the given nodelist's nodes
	 *
	 * @param array $list
	 * @param Context $context
	 *
	 * @return string
	 */
	protected function renderAll(array $list, Context $context)
	{
		$result = '';

		foreach ($list as $token) {
			if (is_object($token) && method_exists($token, 'render')) {
				$value = $token->render($context);
			} else {
				$value = $token;
			}

			if (is_array($value)) {
				$value = htmlspecialchars(print_r($value, true));
			}

			$result .= $value;

			if (isset($context->registers['break'])) {
				break;
			}
			if (isset($context->registers['continue'])) {
				break;
			}

			$context->tick();
		}

		return $result;
	}

	/**
	 * An action to execute when the end tag is reached
	 */
	protected function endTag()
	{
		// Do nothing by default
	}

	/**
	 * Handler for unknown tags
	 *
	 * @param string $tag
	 * @param string $params
	 * @param array $tokens
	 *
	 * @throws \Liquid\Exception\ParseException
	 */
	protected function unknownTag($tag, $params, array $tokens)
	{
		switch ($tag) {
			case 'else':
				throw new ParseException($this->blockName() . " does not expect else tag");
			case 'end':
				throw new ParseException("'end' is not a valid delimiter for " . $this->blockName() . " tags. Use " . $this->blockDelimiter());
			default:
				throw new ParseException("Unknown tag $tag");
		}
	}

	/**
	 * This method is called at the end of parsing, and will throw an error unless
	 * this method is subclassed, like it is for Document
	 *
	 * @throws \Liquid\Exception\ParseException
	 * @return bool
	 */
	protected function assertMissingDelimitation()
	{
		throw new ParseException($this->blockName() . " tag was never closed");
	}

	/**
	 * Returns the string that delimits the end of the block
	 *
	 * @return string
	 */
	protected function blockDelimiter()
	{
		return "end" . $this->blockName();
	}

	/**
	 * Returns the name of the block
	 *
	 * @return string
	 */
	private function blockName()
	{
		$reflection = new \ReflectionClass($this);
		return str_replace('tag', '', strtolower($reflection->getShortName()));
	}
}
