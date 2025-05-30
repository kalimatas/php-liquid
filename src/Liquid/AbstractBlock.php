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
	protected $nodelist = [];

	/**
	 * Whenever next token should be ltrim'med.
	 *
	 * @var bool
	 */
	protected static $trimWhitespace = false;


	private ?string $whitespaceControl;

	private ?Regexp $startRegexp;
	private ?Regexp $tagRegexp;
	private ?Regexp $variableStartRegexp;

	private ?Regexp $variableRegexp;

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
		// Constructor is not reliably called by subclasses, so we need to ensure these are set
		$this->startRegexp ??= new Regexp('/^' . Liquid::get('TAG_START') . '/');
		$this->tagRegexp ??= new Regexp('/^' . Liquid::get('TAG_START') . Liquid::get('WHITESPACE_CONTROL') . '?\s*(\w+)\s*(.*?)' . Liquid::get('WHITESPACE_CONTROL') . '?' . Liquid::get('TAG_END') . '$/s');
		$this->variableStartRegexp ??= new Regexp('/^' . Liquid::get('VARIABLE_START') . '/');

		$startRegexp = $this->startRegexp;
		$tagRegexp = $this->tagRegexp;
		$variableStartRegexp = $this->variableStartRegexp;

		$this->nodelist = [];

		$tags = Template::getTags();

		for ($i = 0, $n = count($tokens); $i < $n; $i++) {
			if ($tokens[$i] === null) {
				continue;
			}
			$token = $tokens[$i];
			$tokens[$i] = null;

			if ($startRegexp->match($token)) {
				$this->whitespaceHandler($token);
				if ($tagRegexp->match($token)) {
					// If we found the proper block delimitor just end parsing here and let the outer block proceed
					if ($tagRegexp->matches[1] == $this->blockDelimiter()) {
						$this->endTag();
						return;
					}

					$tagName = null;
					if (array_key_exists($tagRegexp->matches[1], $tags)) {
						$tagName = $tags[$tagRegexp->matches[1]];
					} else {
						$tagName = self::TAG_PREFIX . ucwords($tagRegexp->matches[1]);
						$tagName = (class_exists($tagName) === true) ? $tagName : null;
					}

					if ($tagName !== null) {
						$this->nodelist[] = new $tagName($tagRegexp->matches[2], $tokens, $this->fileSystem);
						if ($tagRegexp->matches[1] == 'extends') {
							return;
						}
					} else {
						$this->unknownTag($tagRegexp->matches[1], $tagRegexp->matches[2], $tokens);
					}
				} else {
					throw new ParseException("Tag $token was not properly terminated (won't match $tagRegexp)");
				}
			} elseif ($variableStartRegexp->match($token)) {
				$this->whitespaceHandler($token);
				$this->nodelist[] = $this->createVariable($token);
			} else {
				// This is neither a tag or a variable, proceed with an ltrim
				if (self::$trimWhitespace) {
					$token = ltrim($token);
				}

				self::$trimWhitespace = false;
				$this->nodelist[] = $token;
			}
		}

		$this->assertMissingDelimitation();
	}

	/**
	 * Handle the whitespace.
	 *
	 * @param string $token
	 */
	protected function whitespaceHandler($token)
	{
		$this->whitespaceControl ??= Liquid::get('WHITESPACE_CONTROL');

		/*
		 * This assumes that TAG_START is always '{%', and a whitespace control indicator
		 * is exactly one character long, on a third position.
		 */
		if ($token[2] === $this->whitespaceControl) {
			$previousToken = end($this->nodelist);
			if (is_string($previousToken)) { // this can also be a tag or a variable
				$this->nodelist[key($this->nodelist)] = rtrim($previousToken);
			}
		}

		/*
		 * This assumes that TAG_END is always '%}', and a whitespace control indicator
		 * is exactly one character long, on a third position from the end.
		 */
		self::$trimWhitespace = $token[-3] === $this->whitespaceControl;
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
				$value = htmlspecialchars(implode($value));
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

	/**
	 * Create a variable for the given token
	 *
	 * @param string $token
	 *
	 * @throws \Liquid\Exception\ParseException
	 * @return Variable
	 */
	private function createVariable($token)
	{
		$this->variableRegexp ??= new Regexp('/^' . Liquid::get('VARIABLE_START') . Liquid::get('WHITESPACE_CONTROL') . '?(.*?)' . Liquid::get('WHITESPACE_CONTROL') . '?' . Liquid::get('VARIABLE_END') . '$/s');

		if ($this->variableRegexp->match($token)) {
			return new Variable($this->variableRegexp->matches[1]);
		}

		throw new ParseException("Variable $token was not properly terminated");
	}
}
