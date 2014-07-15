<?php

// definitions which files to minify
define("SRC_JS", "_minify/*.js");
define("SRC_CSS", "_minify/*.css");
define("DST_JS", "scripts.min.js");
define("DST_CSS", "style.min.css");


/**
 * JSMinPlus version 1.4
 *
 * Minifies a javascript file using a javascript parser
 *
 * This implements a PHP port of Brendan Eich's Narcissus open source javascript engine (in javascript)
 * References: http://en.wikipedia.org/wiki/Narcissus_(JavaScript_engine)
 * Narcissus sourcecode: http://mxr.mozilla.org/mozilla/source/js/narcissus/
 * JSMinPlus weblog: http://crisp.tweakblogs.net/blog/cat/716
 *
 * Tino Zijdel <crisp@tweakers.net>
 *
 * Usage: $minified = JSMinPlus::minify($script [, $filename])
 *
 * Versionlog (see also changelog.txt):
 * 23-07-2011 - remove dynamic creation of OP_* and KEYWORD_* defines and declare them on top
 *              reduce memory footprint by minifying by block-scope
 *              some small byte-saving and performance improvements
 * 12-05-2009 - fixed hook:colon precedence, fixed empty body in loop and if-constructs
 * 18-04-2009 - fixed crashbug in PHP 5.2.9 and several other bugfixes
 * 12-04-2009 - some small bugfixes and performance improvements
 * 09-04-2009 - initial open sourced version 1.0
 *
 * Latest version of this script: http://files.tweakers.net/jsminplus/jsminplus.zip
 *
 */

/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is the Narcissus JavaScript engine.
 *
 * The Initial Developer of the Original Code is
 * Brendan Eich <brendan@mozilla.org>.
 * Portions created by the Initial Developer are Copyright (C) 2004
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s): Tino Zijdel <crisp@tweakers.net>
 * PHP port, modifications and minifier routine are (C) 2009-2011
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('TOKEN_END', 1);
define('TOKEN_NUMBER', 2);
define('TOKEN_IDENTIFIER', 3);
define('TOKEN_STRING', 4);
define('TOKEN_REGEXP', 5);
define('TOKEN_NEWLINE', 6);
define('TOKEN_CONDCOMMENT_START', 7);
define('TOKEN_CONDCOMMENT_END', 8);

define('JS_SCRIPT', 100);
define('JS_BLOCK', 101);
define('JS_LABEL', 102);
define('JS_FOR_IN', 103);
define('JS_CALL', 104);
define('JS_NEW_WITH_ARGS', 105);
define('JS_INDEX', 106);
define('JS_ARRAY_INIT', 107);
define('JS_OBJECT_INIT', 108);
define('JS_PROPERTY_INIT', 109);
define('JS_GETTER', 110);
define('JS_SETTER', 111);
define('JS_GROUP', 112);
define('JS_LIST', 113);

define('JS_MINIFIED', 999);

define('DECLARED_FORM', 0);
define('EXPRESSED_FORM', 1);
define('STATEMENT_FORM', 2);

/* Operators */
define('OP_SEMICOLON', ';');
define('OP_COMMA', ',');
define('OP_HOOK', '?');
define('OP_COLON', ':');
define('OP_OR', '||');
define('OP_AND', '&&');
define('OP_BITWISE_OR', '|');
define('OP_BITWISE_XOR', '^');
define('OP_BITWISE_AND', '&');
define('OP_STRICT_EQ', '===');
define('OP_EQ', '==');
define('OP_ASSIGN', '=');
define('OP_STRICT_NE', '!==');
define('OP_NE', '!=');
define('OP_LSH', '<<');
define('OP_LE', '<=');
define('OP_LT', '<');
define('OP_URSH', '>>>');
define('OP_RSH', '>>');
define('OP_GE', '>=');
define('OP_GT', '>');
define('OP_INCREMENT', '++');
define('OP_DECREMENT', '--');
define('OP_PLUS', '+');
define('OP_MINUS', '-');
define('OP_MUL', '*');
define('OP_DIV', '/');
define('OP_MOD', '%');
define('OP_NOT', '!');
define('OP_BITWISE_NOT', '~');
define('OP_DOT', '.');
define('OP_LEFT_BRACKET', '[');
define('OP_RIGHT_BRACKET', ']');
define('OP_LEFT_CURLY', '{');
define('OP_RIGHT_CURLY', '}');
define('OP_LEFT_PAREN', '(');
define('OP_RIGHT_PAREN', ')');
define('OP_CONDCOMMENT_END', '@*/');

define('OP_UNARY_PLUS', 'U+');
define('OP_UNARY_MINUS', 'U-');

/* Keywords */
define('KEYWORD_BREAK', 'break');
define('KEYWORD_CASE', 'case');
define('KEYWORD_CATCH', 'catch');
define('KEYWORD_CONST', 'const');
define('KEYWORD_CONTINUE', 'continue');
define('KEYWORD_DEBUGGER', 'debugger');
define('KEYWORD_DEFAULT', 'default');
define('KEYWORD_DELETE', 'delete');
define('KEYWORD_DO', 'do');
define('KEYWORD_ELSE', 'else');
define('KEYWORD_ENUM', 'enum');
define('KEYWORD_FALSE', 'false');
define('KEYWORD_FINALLY', 'finally');
define('KEYWORD_FOR', 'for');
define('KEYWORD_FUNCTION', 'function');
define('KEYWORD_IF', 'if');
define('KEYWORD_IN', 'in');
define('KEYWORD_INSTANCEOF', 'instanceof');
define('KEYWORD_NEW', 'new');
define('KEYWORD_NULL', 'null');
define('KEYWORD_RETURN', 'return');
define('KEYWORD_SWITCH', 'switch');
define('KEYWORD_THIS', 'this');
define('KEYWORD_THROW', 'throw');
define('KEYWORD_TRUE', 'true');
define('KEYWORD_TRY', 'try');
define('KEYWORD_TYPEOF', 'typeof');
define('KEYWORD_VAR', 'var');
define('KEYWORD_VOID', 'void');
define('KEYWORD_WHILE', 'while');
define('KEYWORD_WITH', 'with');


class JSMinPlus
{
	private $parser;
	private $reserved = array(
		'break', 'case', 'catch', 'continue', 'default', 'delete', 'do',
		'else', 'finally', 'for', 'function', 'if', 'in', 'instanceof',
		'new', 'return', 'switch', 'this', 'throw', 'try', 'typeof', 'var',
		'void', 'while', 'with',
		// Words reserved for future use
		'abstract', 'boolean', 'byte', 'char', 'class', 'const', 'debugger',
		'double', 'enum', 'export', 'extends', 'final', 'float', 'goto',
		'implements', 'import', 'int', 'interface', 'long', 'native',
		'package', 'private', 'protected', 'public', 'short', 'static',
		'super', 'synchronized', 'throws', 'transient', 'volatile',
		// These are not reserved, but should be taken into account
		// in isValidIdentifier (See jslint source code)
		'arguments', 'eval', 'true', 'false', 'Infinity', 'NaN', 'null', 'undefined'
	);

	private function __construct()
	{
		$this->parser = new JSParser($this);
	}

	public static function minify($js, $filename='')
	{
		static $instance;

		// this is a singleton
		if(!$instance)
			$instance = new JSMinPlus();

		return $instance->min($js, $filename);
	}

	private function min($js, $filename)
	{
		try
		{
			$n = $this->parser->parse($js, $filename, 1);
			return $this->parseTree($n);
		}
		catch(Exception $e)
		{
			echo $e->getMessage() . "\n";
		}

		return false;
	}

	public function parseTree($n, $noBlockGrouping = false)
	{
		$s = '';

		switch ($n->type)
		{
			case JS_MINIFIED:
				$s = $n->value;
			break;

			case JS_SCRIPT:
				// we do nothing yet with funDecls or varDecls
				$noBlockGrouping = true;
			// FALL THROUGH

			case JS_BLOCK:
				$childs = $n->treeNodes;
				$lastType = 0;
				for ($c = 0, $i = 0, $j = count($childs); $i < $j; $i++)
				{
					$type = $childs[$i]->type;
					$t = $this->parseTree($childs[$i]);
					if (strlen($t))
					{
						if ($c)
						{
							$s = rtrim($s, ';');

							if ($type == KEYWORD_FUNCTION && $childs[$i]->functionForm == DECLARED_FORM)
							{
								// put declared functions on a new line
								$s .= "\n";
							}
							elseif ($type == KEYWORD_VAR && $type == $lastType)
							{
								// mutiple var-statements can go into one
								$t = ',' . substr($t, 4);
							}
							else
							{
								// add terminator
								$s .= ';';
							}
						}

						$s .= $t;

						$c++;
						$lastType = $type;
					}
				}

				if ($c > 1 && !$noBlockGrouping)
				{
					$s = '{' . $s . '}';
				}
			break;

			case KEYWORD_FUNCTION:
				$s .= 'function' . ($n->name ? ' ' . $n->name : '') . '(';
				$params = $n->params;
				for ($i = 0, $j = count($params); $i < $j; $i++)
					$s .= ($i ? ',' : '') . $params[$i];
				$s .= '){' . $this->parseTree($n->body, true) . '}';
			break;

			case KEYWORD_IF:
				$s = 'if(' . $this->parseTree($n->condition) . ')';
				$thenPart = $this->parseTree($n->thenPart);
				$elsePart = $n->elsePart ? $this->parseTree($n->elsePart) : null;

				// empty if-statement
				if ($thenPart == '')
					$thenPart = ';';

				if ($elsePart)
				{
					// be carefull and always make a block out of the thenPart; could be more optimized but is a lot of trouble
					if ($thenPart != ';' && $thenPart[0] != '{')
						$thenPart = '{' . $thenPart . '}';

					$s .= $thenPart . 'else';

					// we could check for more, but that hardly ever applies so go for performance
					if ($elsePart[0] != '{')
						$s .= ' ';

					$s .= $elsePart;
				}
				else
				{
					$s .= $thenPart;
				}
			break;

			case KEYWORD_SWITCH:
				$s = 'switch(' . $this->parseTree($n->discriminant) . '){';
				$cases = $n->cases;
				for ($i = 0, $j = count($cases); $i < $j; $i++)
				{
					$case = $cases[$i];
					if ($case->type == KEYWORD_CASE)
						$s .= 'case' . ($case->caseLabel->type != TOKEN_STRING ? ' ' : '') . $this->parseTree($case->caseLabel) . ':';
					else
						$s .= 'default:';

					$statement = $this->parseTree($case->statements, true);
					if ($statement)
					{
						$s .= $statement;
						// no terminator for last statement
						if ($i + 1 < $j)
							$s .= ';';
					}
				}
				$s .= '}';
			break;

			case KEYWORD_FOR:
				$s = 'for(' . ($n->setup ? $this->parseTree($n->setup) : '')
					. ';' . ($n->condition ? $this->parseTree($n->condition) : '')
					. ';' . ($n->update ? $this->parseTree($n->update) : '') . ')';

				$body  = $this->parseTree($n->body);
				if ($body == '')
					$body = ';';

				$s .= $body;
			break;

			case KEYWORD_WHILE:
				$s = 'while(' . $this->parseTree($n->condition) . ')';

				$body  = $this->parseTree($n->body);
				if ($body == '')
					$body = ';';

				$s .= $body;
			break;

			case JS_FOR_IN:
				$s = 'for(' . ($n->varDecl ? $this->parseTree($n->varDecl) : $this->parseTree($n->iterator)) . ' in ' . $this->parseTree($n->object) . ')';

				$body  = $this->parseTree($n->body);
				if ($body == '')
					$body = ';';

				$s .= $body;
			break;

			case KEYWORD_DO:
				$s = 'do{' . $this->parseTree($n->body, true) . '}while(' . $this->parseTree($n->condition) . ')';
			break;

			case KEYWORD_BREAK:
			case KEYWORD_CONTINUE:
				$s = $n->value . ($n->label ? ' ' . $n->label : '');
			break;

			case KEYWORD_TRY:
				$s = 'try{' . $this->parseTree($n->tryBlock, true) . '}';
				$catchClauses = $n->catchClauses;
				for ($i = 0, $j = count($catchClauses); $i < $j; $i++)
				{
					$t = $catchClauses[$i];
					$s .= 'catch(' . $t->varName . ($t->guard ? ' if ' . $this->parseTree($t->guard) : '') . '){' . $this->parseTree($t->block, true) . '}';
				}
				if ($n->finallyBlock)
					$s .= 'finally{' . $this->parseTree($n->finallyBlock, true) . '}';
			break;

			case KEYWORD_THROW:
			case KEYWORD_RETURN:
				$s = $n->type;
				if ($n->value)
				{
					$t = $this->parseTree($n->value);
					if (strlen($t))
					{
						if ($this->isWordChar($t[0]) || $t[0] == '\\')
							$s .= ' ';

						$s .= $t;
					}
				}
			break;

			case KEYWORD_WITH:
				$s = 'with(' . $this->parseTree($n->object) . ')' . $this->parseTree($n->body);
			break;

			case KEYWORD_VAR:
			case KEYWORD_CONST:
				$s = $n->value . ' ';
				$childs = $n->treeNodes;
				for ($i = 0, $j = count($childs); $i < $j; $i++)
				{
					$t = $childs[$i];
					$s .= ($i ? ',' : '') . $t->name;
					$u = $t->initializer;
					if ($u)
						$s .= '=' . $this->parseTree($u);
				}
			break;

			case KEYWORD_IN:
			case KEYWORD_INSTANCEOF:
				$left = $this->parseTree($n->treeNodes[0]);
				$right = $this->parseTree($n->treeNodes[1]);

				$s = $left;

				if ($this->isWordChar(substr($left, -1)))
					$s .= ' ';

				$s .= $n->type;

				if ($this->isWordChar($right[0]) || $right[0] == '\\')
					$s .= ' ';

				$s .= $right;
			break;

			case KEYWORD_DELETE:
			case KEYWORD_TYPEOF:
				$right = $this->parseTree($n->treeNodes[0]);

				$s = $n->type;

				if ($this->isWordChar($right[0]) || $right[0] == '\\')
					$s .= ' ';

				$s .= $right;
			break;

			case KEYWORD_VOID:
				$s = 'void(' . $this->parseTree($n->treeNodes[0]) . ')';
			break;

			case KEYWORD_DEBUGGER:
				throw new Exception('NOT IMPLEMENTED: DEBUGGER');
			break;

			case TOKEN_CONDCOMMENT_START:
			case TOKEN_CONDCOMMENT_END:
				$s = $n->value . ($n->type == TOKEN_CONDCOMMENT_START ? ' ' : '');
				$childs = $n->treeNodes;
				for ($i = 0, $j = count($childs); $i < $j; $i++)
					$s .= $this->parseTree($childs[$i]);
			break;

			case OP_SEMICOLON:
				if ($expression = $n->expression)
					$s = $this->parseTree($expression);
			break;

			case JS_LABEL:
				$s = $n->label . ':' . $this->parseTree($n->statement);
			break;

			case OP_COMMA:
				$childs = $n->treeNodes;
				for ($i = 0, $j = count($childs); $i < $j; $i++)
					$s .= ($i ? ',' : '') . $this->parseTree($childs[$i]);
			break;

			case OP_ASSIGN:
				$s = $this->parseTree($n->treeNodes[0]) . $n->value . $this->parseTree($n->treeNodes[1]);
			break;

			case OP_HOOK:
				$s = $this->parseTree($n->treeNodes[0]) . '?' . $this->parseTree($n->treeNodes[1]) . ':' . $this->parseTree($n->treeNodes[2]);
			break;

			case OP_OR: case OP_AND:
			case OP_BITWISE_OR: case OP_BITWISE_XOR: case OP_BITWISE_AND:
			case OP_EQ: case OP_NE: case OP_STRICT_EQ: case OP_STRICT_NE:
			case OP_LT: case OP_LE: case OP_GE: case OP_GT:
			case OP_LSH: case OP_RSH: case OP_URSH:
			case OP_MUL: case OP_DIV: case OP_MOD:
				$s = $this->parseTree($n->treeNodes[0]) . $n->type . $this->parseTree($n->treeNodes[1]);
			break;

			case OP_PLUS:
			case OP_MINUS:
				$left = $this->parseTree($n->treeNodes[0]);
				$right = $this->parseTree($n->treeNodes[1]);

				switch ($n->treeNodes[1]->type)
				{
					case OP_PLUS:
					case OP_MINUS:
					case OP_INCREMENT:
					case OP_DECREMENT:
					case OP_UNARY_PLUS:
					case OP_UNARY_MINUS:
						$s = $left . $n->type . ' ' . $right;
					break;

					case TOKEN_STRING:
						//combine concatted strings with same quotestyle
						if ($n->type == OP_PLUS && substr($left, -1) == $right[0])
						{
							$s = substr($left, 0, -1) . substr($right, 1);
							break;
						}
					// FALL THROUGH

					default:
						$s = $left . $n->type . $right;
				}
			break;

			case OP_NOT:
			case OP_BITWISE_NOT:
			case OP_UNARY_PLUS:
			case OP_UNARY_MINUS:
				$s = $n->value . $this->parseTree($n->treeNodes[0]);
			break;

			case OP_INCREMENT:
			case OP_DECREMENT:
				if ($n->postfix)
					$s = $this->parseTree($n->treeNodes[0]) . $n->value;
				else
					$s = $n->value . $this->parseTree($n->treeNodes[0]);
			break;

			case OP_DOT:
				$s = $this->parseTree($n->treeNodes[0]) . '.' . $this->parseTree($n->treeNodes[1]);
			break;

			case JS_INDEX:
				$s = $this->parseTree($n->treeNodes[0]);
				// See if we can replace named index with a dot saving 3 bytes
				if (	$n->treeNodes[0]->type == TOKEN_IDENTIFIER &&
					$n->treeNodes[1]->type == TOKEN_STRING &&
					$this->isValidIdentifier(substr($n->treeNodes[1]->value, 1, -1))
				)
					$s .= '.' . substr($n->treeNodes[1]->value, 1, -1);
				else
					$s .= '[' . $this->parseTree($n->treeNodes[1]) . ']';
			break;

			case JS_LIST:
				$childs = $n->treeNodes;
				for ($i = 0, $j = count($childs); $i < $j; $i++)
					$s .= ($i ? ',' : '') . $this->parseTree($childs[$i]);
			break;

			case JS_CALL:
				$s = $this->parseTree($n->treeNodes[0]) . '(' . $this->parseTree($n->treeNodes[1]) . ')';
			break;

			case KEYWORD_NEW:
			case JS_NEW_WITH_ARGS:
				$s = 'new ' . $this->parseTree($n->treeNodes[0]) . '(' . ($n->type == JS_NEW_WITH_ARGS ? $this->parseTree($n->treeNodes[1]) : '') . ')';
			break;

			case JS_ARRAY_INIT:
				$s = '[';
				$childs = $n->treeNodes;
				for ($i = 0, $j = count($childs); $i < $j; $i++)
				{
					$s .= ($i ? ',' : '') . $this->parseTree($childs[$i]);
				}
				$s .= ']';
			break;

			case JS_OBJECT_INIT:
				$s = '{';
				$childs = $n->treeNodes;
				for ($i = 0, $j = count($childs); $i < $j; $i++)
				{
					$t = $childs[$i];
					if ($i)
						$s .= ',';
					if ($t->type == JS_PROPERTY_INIT)
					{
						// Ditch the quotes when the index is a valid identifier
						if (	$t->treeNodes[0]->type == TOKEN_STRING &&
							$this->isValidIdentifier(substr($t->treeNodes[0]->value, 1, -1))
						)
							$s .= substr($t->treeNodes[0]->value, 1, -1);
						else
							$s .= $t->treeNodes[0]->value;

						$s .= ':' . $this->parseTree($t->treeNodes[1]);
					}
					else
					{
						$s .= $t->type == JS_GETTER ? 'get' : 'set';
						$s .= ' ' . $t->name . '(';
						$params = $t->params;
						for ($i = 0, $j = count($params); $i < $j; $i++)
							$s .= ($i ? ',' : '') . $params[$i];
						$s .= '){' . $this->parseTree($t->body, true) . '}';
					}
				}
				$s .= '}';
			break;

			case TOKEN_NUMBER:
				$s = $n->value;
				if (preg_match('/^([1-9]+)(0{3,})$/', $s, $m))
					$s = $m[1] . 'e' . strlen($m[2]);
			break;

			case KEYWORD_NULL: case KEYWORD_THIS: case KEYWORD_TRUE: case KEYWORD_FALSE:
			case TOKEN_IDENTIFIER: case TOKEN_STRING: case TOKEN_REGEXP:
				$s = $n->value;
			break;

			case JS_GROUP:
				if (in_array(
					$n->treeNodes[0]->type,
					array(
						JS_ARRAY_INIT, JS_OBJECT_INIT, JS_GROUP,
						TOKEN_NUMBER, TOKEN_STRING, TOKEN_REGEXP, TOKEN_IDENTIFIER,
						KEYWORD_NULL, KEYWORD_THIS, KEYWORD_TRUE, KEYWORD_FALSE
					)
				))
				{
					$s = $this->parseTree($n->treeNodes[0]);
				}
				else
				{
					$s = '(' . $this->parseTree($n->treeNodes[0]) . ')';
				}
			break;

			default:
				throw new Exception('UNKNOWN TOKEN TYPE: ' . $n->type);
		}

		return $s;
	}

	private function isValidIdentifier($string)
	{
		return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $string) && !in_array($string, $this->reserved);
	}

	private function isWordChar($char)
	{
		return $char == '_' || $char == '$' || ctype_alnum($char);
	}
}

class JSParser
{
	private $t;
	private $minifier;

	private $opPrecedence = array(
		';' => 0,
		',' => 1,
		'=' => 2, '?' => 2, ':' => 2,
		// The above all have to have the same precedence, see bug 330975
		'||' => 4,
		'&&' => 5,
		'|' => 6,
		'^' => 7,
		'&' => 8,
		'==' => 9, '!=' => 9, '===' => 9, '!==' => 9,
		'<' => 10, '<=' => 10, '>=' => 10, '>' => 10, 'in' => 10, 'instanceof' => 10,
		'<<' => 11, '>>' => 11, '>>>' => 11,
		'+' => 12, '-' => 12,
		'*' => 13, '/' => 13, '%' => 13,
		'delete' => 14, 'void' => 14, 'typeof' => 14,
		'!' => 14, '~' => 14, 'U+' => 14, 'U-' => 14,
		'++' => 15, '--' => 15,
		'new' => 16,
		'.' => 17,
		JS_NEW_WITH_ARGS => 0, JS_INDEX => 0, JS_CALL => 0,
		JS_ARRAY_INIT => 0, JS_OBJECT_INIT => 0, JS_GROUP => 0
	);

	private $opArity = array(
		',' => -2,
		'=' => 2,
		'?' => 3,
		'||' => 2,
		'&&' => 2,
		'|' => 2,
		'^' => 2,
		'&' => 2,
		'==' => 2, '!=' => 2, '===' => 2, '!==' => 2,
		'<' => 2, '<=' => 2, '>=' => 2, '>' => 2, 'in' => 2, 'instanceof' => 2,
		'<<' => 2, '>>' => 2, '>>>' => 2,
		'+' => 2, '-' => 2,
		'*' => 2, '/' => 2, '%' => 2,
		'delete' => 1, 'void' => 1, 'typeof' => 1,
		'!' => 1, '~' => 1, 'U+' => 1, 'U-' => 1,
		'++' => 1, '--' => 1,
		'new' => 1,
		'.' => 2,
		JS_NEW_WITH_ARGS => 2, JS_INDEX => 2, JS_CALL => 2,
		JS_ARRAY_INIT => 1, JS_OBJECT_INIT => 1, JS_GROUP => 1,
		TOKEN_CONDCOMMENT_START => 1, TOKEN_CONDCOMMENT_END => 1
	);

	public function __construct($minifier=null)
	{
		$this->minifier = $minifier;
		$this->t = new JSTokenizer();
	}

	public function parse($s, $f, $l)
	{
		// initialize tokenizer
		$this->t->init($s, $f, $l);

		$x = new JSCompilerContext(false);
		$n = $this->Script($x);
		if (!$this->t->isDone())
			throw $this->t->newSyntaxError('Syntax error');

		return $n;
	}

	private function Script($x)
	{
		$n = $this->Statements($x);
		$n->type = JS_SCRIPT;
		$n->funDecls = $x->funDecls;
		$n->varDecls = $x->varDecls;

		// minify by scope
		if ($this->minifier)
		{
			$n->value = $this->minifier->parseTree($n);

			// clear tree from node to save memory
			$n->treeNodes = null;
			$n->funDecls = null;
			$n->varDecls = null;

			$n->type = JS_MINIFIED;
		}

		return $n;
	}

	private function Statements($x)
	{
		$n = new JSNode($this->t, JS_BLOCK);
		array_push($x->stmtStack, $n);

		while (!$this->t->isDone() && $this->t->peek() != OP_RIGHT_CURLY)
			$n->addNode($this->Statement($x));

		array_pop($x->stmtStack);

		return $n;
	}

	private function Block($x)
	{
		$this->t->mustMatch(OP_LEFT_CURLY);
		$n = $this->Statements($x);
		$this->t->mustMatch(OP_RIGHT_CURLY);

		return $n;
	}

	private function Statement($x)
	{
		$tt = $this->t->get();
		$n2 = null;

		// Cases for statements ending in a right curly return early, avoiding the
		// common semicolon insertion magic after this switch.
		switch ($tt)
		{
			case KEYWORD_FUNCTION:
				return $this->FunctionDefinition(
					$x,
					true,
					count($x->stmtStack) > 1 ? STATEMENT_FORM : DECLARED_FORM
				);
			break;

			case OP_LEFT_CURLY:
				$n = $this->Statements($x);
				$this->t->mustMatch(OP_RIGHT_CURLY);
			return $n;

			case KEYWORD_IF:
				$n = new JSNode($this->t);
				$n->condition = $this->ParenExpression($x);
				array_push($x->stmtStack, $n);
				$n->thenPart = $this->Statement($x);
				$n->elsePart = $this->t->match(KEYWORD_ELSE) ? $this->Statement($x) : null;
				array_pop($x->stmtStack);
			return $n;

			case KEYWORD_SWITCH:
				$n = new JSNode($this->t);
				$this->t->mustMatch(OP_LEFT_PAREN);
				$n->discriminant = $this->Expression($x);
				$this->t->mustMatch(OP_RIGHT_PAREN);
				$n->cases = array();
				$n->defaultIndex = -1;

				array_push($x->stmtStack, $n);

				$this->t->mustMatch(OP_LEFT_CURLY);

				while (($tt = $this->t->get()) != OP_RIGHT_CURLY)
				{
					switch ($tt)
					{
						case KEYWORD_DEFAULT:
							if ($n->defaultIndex >= 0)
								throw $this->t->newSyntaxError('More than one switch default');
							// FALL THROUGH
						case KEYWORD_CASE:
							$n2 = new JSNode($this->t);
							if ($tt == KEYWORD_DEFAULT)
								$n->defaultIndex = count($n->cases);
							else
								$n2->caseLabel = $this->Expression($x, OP_COLON);
								break;
						default:
							throw $this->t->newSyntaxError('Invalid switch case');
					}

					$this->t->mustMatch(OP_COLON);
					$n2->statements = new JSNode($this->t, JS_BLOCK);
					while (($tt = $this->t->peek()) != KEYWORD_CASE && $tt != KEYWORD_DEFAULT && $tt != OP_RIGHT_CURLY)
						$n2->statements->addNode($this->Statement($x));

					array_push($n->cases, $n2);
				}

				array_pop($x->stmtStack);
			return $n;

			case KEYWORD_FOR:
				$n = new JSNode($this->t);
				$n->isLoop = true;
				$this->t->mustMatch(OP_LEFT_PAREN);

				if (($tt = $this->t->peek()) != OP_SEMICOLON)
				{
					$x->inForLoopInit = true;
					if ($tt == KEYWORD_VAR || $tt == KEYWORD_CONST)
					{
						$this->t->get();
						$n2 = $this->Variables($x);
					}
					else
					{
						$n2 = $this->Expression($x);
					}
					$x->inForLoopInit = false;
				}

				if ($n2 && $this->t->match(KEYWORD_IN))
				{
					$n->type = JS_FOR_IN;
					if ($n2->type == KEYWORD_VAR)
					{
						if (count($n2->treeNodes) != 1)
						{
							throw $this->t->SyntaxError(
								'Invalid for..in left-hand side',
								$this->t->filename,
								$n2->lineno
							);
						}

						// NB: n2[0].type == IDENTIFIER and n2[0].value == n2[0].name.
						$n->iterator = $n2->treeNodes[0];
						$n->varDecl = $n2;
					}
					else
					{
						$n->iterator = $n2;
						$n->varDecl = null;
					}

					$n->object = $this->Expression($x);
				}
				else
				{
					$n->setup = $n2 ? $n2 : null;
					$this->t->mustMatch(OP_SEMICOLON);
					$n->condition = $this->t->peek() == OP_SEMICOLON ? null : $this->Expression($x);
					$this->t->mustMatch(OP_SEMICOLON);
					$n->update = $this->t->peek() == OP_RIGHT_PAREN ? null : $this->Expression($x);
				}

				$this->t->mustMatch(OP_RIGHT_PAREN);
				$n->body = $this->nest($x, $n);
			return $n;

			case KEYWORD_WHILE:
			        $n = new JSNode($this->t);
			        $n->isLoop = true;
			        $n->condition = $this->ParenExpression($x);
			        $n->body = $this->nest($x, $n);
			return $n;

			case KEYWORD_DO:
				$n = new JSNode($this->t);
				$n->isLoop = true;
				$n->body = $this->nest($x, $n, KEYWORD_WHILE);
				$n->condition = $this->ParenExpression($x);
				if (!$x->ecmaStrictMode)
				{
					// <script language="JavaScript"> (without version hints) may need
					// automatic semicolon insertion without a newline after do-while.
					// See http://bugzilla.mozilla.org/show_bug.cgi?id=238945.
					$this->t->match(OP_SEMICOLON);
					return $n;
				}
			break;

			case KEYWORD_BREAK:
			case KEYWORD_CONTINUE:
				$n = new JSNode($this->t);

				if ($this->t->peekOnSameLine() == TOKEN_IDENTIFIER)
				{
					$this->t->get();
					$n->label = $this->t->currentToken()->value;
				}

				$ss = $x->stmtStack;
				$i = count($ss);
				$label = $n->label;
				if ($label)
				{
					do
					{
						if (--$i < 0)
							throw $this->t->newSyntaxError('Label not found');
					}
					while ($ss[$i]->label != $label);
				}
				else
				{
					do
					{
						if (--$i < 0)
							throw $this->t->newSyntaxError('Invalid ' . $tt);
					}
					while (!$ss[$i]->isLoop && ($tt != KEYWORD_BREAK || $ss[$i]->type != KEYWORD_SWITCH));
				}

				$n->target = $ss[$i];
			break;

			case KEYWORD_TRY:
				$n = new JSNode($this->t);
				$n->tryBlock = $this->Block($x);
				$n->catchClauses = array();

				while ($this->t->match(KEYWORD_CATCH))
				{
					$n2 = new JSNode($this->t);
					$this->t->mustMatch(OP_LEFT_PAREN);
					$n2->varName = $this->t->mustMatch(TOKEN_IDENTIFIER)->value;

					if ($this->t->match(KEYWORD_IF))
					{
						if ($x->ecmaStrictMode)
							throw $this->t->newSyntaxError('Illegal catch guard');

						if (count($n->catchClauses) && !end($n->catchClauses)->guard)
							throw $this->t->newSyntaxError('Guarded catch after unguarded');

						$n2->guard = $this->Expression($x);
					}
					else
					{
						$n2->guard = null;
					}

					$this->t->mustMatch(OP_RIGHT_PAREN);
					$n2->block = $this->Block($x);
					array_push($n->catchClauses, $n2);
				}

				if ($this->t->match(KEYWORD_FINALLY))
					$n->finallyBlock = $this->Block($x);

				if (!count($n->catchClauses) && !$n->finallyBlock)
					throw $this->t->newSyntaxError('Invalid try statement');
			return $n;

			case KEYWORD_CATCH:
			case KEYWORD_FINALLY:
				throw $this->t->newSyntaxError($tt + ' without preceding try');

			case KEYWORD_THROW:
				$n = new JSNode($this->t);
				$n->value = $this->Expression($x);
			break;

			case KEYWORD_RETURN:
				if (!$x->inFunction)
					throw $this->t->newSyntaxError('Invalid return');

				$n = new JSNode($this->t);
				$tt = $this->t->peekOnSameLine();
				if ($tt != TOKEN_END && $tt != TOKEN_NEWLINE && $tt != OP_SEMICOLON && $tt != OP_RIGHT_CURLY)
					$n->value = $this->Expression($x);
				else
					$n->value = null;
			break;

			case KEYWORD_WITH:
				$n = new JSNode($this->t);
				$n->object = $this->ParenExpression($x);
				$n->body = $this->nest($x, $n);
			return $n;

			case KEYWORD_VAR:
			case KEYWORD_CONST:
			        $n = $this->Variables($x);
			break;

			case TOKEN_CONDCOMMENT_START:
			case TOKEN_CONDCOMMENT_END:
				$n = new JSNode($this->t);
			return $n;

			case KEYWORD_DEBUGGER:
				$n = new JSNode($this->t);
			break;

			case TOKEN_NEWLINE:
			case OP_SEMICOLON:
				$n = new JSNode($this->t, OP_SEMICOLON);
				$n->expression = null;
			return $n;

			default:
				if ($tt == TOKEN_IDENTIFIER)
				{
					$this->t->scanOperand = false;
					$tt = $this->t->peek();
					$this->t->scanOperand = true;
					if ($tt == OP_COLON)
					{
						$label = $this->t->currentToken()->value;
						$ss = $x->stmtStack;
						for ($i = count($ss) - 1; $i >= 0; --$i)
						{
							if ($ss[$i]->label == $label)
								throw $this->t->newSyntaxError('Duplicate label');
						}

						$this->t->get();
						$n = new JSNode($this->t, JS_LABEL);
						$n->label = $label;
						$n->statement = $this->nest($x, $n);

						return $n;
					}
				}

				$n = new JSNode($this->t, OP_SEMICOLON);
				$this->t->unget();
				$n->expression = $this->Expression($x);
				$n->end = $n->expression->end;
			break;
		}

		if ($this->t->lineno == $this->t->currentToken()->lineno)
		{
			$tt = $this->t->peekOnSameLine();
			if ($tt != TOKEN_END && $tt != TOKEN_NEWLINE && $tt != OP_SEMICOLON && $tt != OP_RIGHT_CURLY)
				throw $this->t->newSyntaxError('Missing ; before statement');
		}

		$this->t->match(OP_SEMICOLON);

		return $n;
	}

	private function FunctionDefinition($x, $requireName, $functionForm)
	{
		$f = new JSNode($this->t);

		if ($f->type != KEYWORD_FUNCTION)
			$f->type = ($f->value == 'get') ? JS_GETTER : JS_SETTER;

		if ($this->t->match(TOKEN_IDENTIFIER))
			$f->name = $this->t->currentToken()->value;
		elseif ($requireName)
			throw $this->t->newSyntaxError('Missing function identifier');

		$this->t->mustMatch(OP_LEFT_PAREN);
			$f->params = array();

		while (($tt = $this->t->get()) != OP_RIGHT_PAREN)
		{
			if ($tt != TOKEN_IDENTIFIER)
				throw $this->t->newSyntaxError('Missing formal parameter');

			array_push($f->params, $this->t->currentToken()->value);

			if ($this->t->peek() != OP_RIGHT_PAREN)
				$this->t->mustMatch(OP_COMMA);
		}

		$this->t->mustMatch(OP_LEFT_CURLY);

		$x2 = new JSCompilerContext(true);
		$f->body = $this->Script($x2);

		$this->t->mustMatch(OP_RIGHT_CURLY);
		$f->end = $this->t->currentToken()->end;

		$f->functionForm = $functionForm;
		if ($functionForm == DECLARED_FORM)
			array_push($x->funDecls, $f);

		return $f;
	}

	private function Variables($x)
	{
		$n = new JSNode($this->t);

		do
		{
			$this->t->mustMatch(TOKEN_IDENTIFIER);

			$n2 = new JSNode($this->t);
			$n2->name = $n2->value;

			if ($this->t->match(OP_ASSIGN))
			{
				if ($this->t->currentToken()->assignOp)
					throw $this->t->newSyntaxError('Invalid variable initialization');

				$n2->initializer = $this->Expression($x, OP_COMMA);
			}

			$n2->readOnly = $n->type == KEYWORD_CONST;

			$n->addNode($n2);
			array_push($x->varDecls, $n2);
		}
		while ($this->t->match(OP_COMMA));

		return $n;
	}

	private function Expression($x, $stop=false)
	{
		$operators = array();
		$operands = array();
		$n = false;

		$bl = $x->bracketLevel;
		$cl = $x->curlyLevel;
		$pl = $x->parenLevel;
		$hl = $x->hookLevel;

		while (($tt = $this->t->get()) != TOKEN_END)
		{
			if ($tt == $stop &&
				$x->bracketLevel == $bl &&
				$x->curlyLevel == $cl &&
				$x->parenLevel == $pl &&
				$x->hookLevel == $hl
			)
			{
				// Stop only if tt matches the optional stop parameter, and that
				// token is not quoted by some kind of bracket.
				break;
			}

			switch ($tt)
			{
				case OP_SEMICOLON:
					// NB: cannot be empty, Statement handled that.
					break 2;

				case OP_HOOK:
					if ($this->t->scanOperand)
						break 2;

					while (	!empty($operators) &&
						$this->opPrecedence[end($operators)->type] > $this->opPrecedence[$tt]
					)
						$this->reduce($operators, $operands);

					array_push($operators, new JSNode($this->t));

					++$x->hookLevel;
					$this->t->scanOperand = true;
					$n = $this->Expression($x);

					if (!$this->t->match(OP_COLON))
						break 2;

					--$x->hookLevel;
					array_push($operands, $n);
				break;

				case OP_COLON:
					if ($x->hookLevel)
						break 2;

					throw $this->t->newSyntaxError('Invalid label');
				break;

				case OP_ASSIGN:
					if ($this->t->scanOperand)
						break 2;

					// Use >, not >=, for right-associative ASSIGN
					while (	!empty($operators) &&
						$this->opPrecedence[end($operators)->type] > $this->opPrecedence[$tt]
					)
						$this->reduce($operators, $operands);

					array_push($operators, new JSNode($this->t));
					end($operands)->assignOp = $this->t->currentToken()->assignOp;
					$this->t->scanOperand = true;
				break;

				case KEYWORD_IN:
					// An in operator should not be parsed if we're parsing the head of
					// a for (...) loop, unless it is in the then part of a conditional
					// expression, or parenthesized somehow.
					if ($x->inForLoopInit && !$x->hookLevel &&
						!$x->bracketLevel && !$x->curlyLevel &&
						!$x->parenLevel
					)
						break 2;
				// FALL THROUGH
				case OP_COMMA:
					// A comma operator should not be parsed if we're parsing the then part
					// of a conditional expression unless it's parenthesized somehow.
					if ($tt == OP_COMMA && $x->hookLevel &&
						!$x->bracketLevel && !$x->curlyLevel &&
						!$x->parenLevel
					)
						break 2;
				// Treat comma as left-associative so reduce can fold left-heavy
				// COMMA trees into a single array.
				// FALL THROUGH
				case OP_OR:
				case OP_AND:
				case OP_BITWISE_OR:
				case OP_BITWISE_XOR:
				case OP_BITWISE_AND:
				case OP_EQ: case OP_NE: case OP_STRICT_EQ: case OP_STRICT_NE:
				case OP_LT: case OP_LE: case OP_GE: case OP_GT:
				case KEYWORD_INSTANCEOF:
				case OP_LSH: case OP_RSH: case OP_URSH:
				case OP_PLUS: case OP_MINUS:
				case OP_MUL: case OP_DIV: case OP_MOD:
				case OP_DOT:
					if ($this->t->scanOperand)
						break 2;

					while (	!empty($operators) &&
						$this->opPrecedence[end($operators)->type] >= $this->opPrecedence[$tt]
					)
						$this->reduce($operators, $operands);

					if ($tt == OP_DOT)
					{
						$this->t->mustMatch(TOKEN_IDENTIFIER);
						array_push($operands, new JSNode($this->t, OP_DOT, array_pop($operands), new JSNode($this->t)));
					}
					else
					{
						array_push($operators, new JSNode($this->t));
						$this->t->scanOperand = true;
					}
				break;

				case KEYWORD_DELETE: case KEYWORD_VOID: case KEYWORD_TYPEOF:
				case OP_NOT: case OP_BITWISE_NOT: case OP_UNARY_PLUS: case OP_UNARY_MINUS:
				case KEYWORD_NEW:
					if (!$this->t->scanOperand)
						break 2;

					array_push($operators, new JSNode($this->t));
				break;

				case OP_INCREMENT: case OP_DECREMENT:
					if ($this->t->scanOperand)
					{
						array_push($operators, new JSNode($this->t));  // prefix increment or decrement
					}
					else
					{
						// Don't cross a line boundary for postfix {in,de}crement.
						$t = $this->t->tokens[($this->t->tokenIndex + $this->t->lookahead - 1) & 3];
						if ($t && $t->lineno != $this->t->lineno)
							break 2;

						if (!empty($operators))
						{
							// Use >, not >=, so postfix has higher precedence than prefix.
							while ($this->opPrecedence[end($operators)->type] > $this->opPrecedence[$tt])
								$this->reduce($operators, $operands);
						}

						$n = new JSNode($this->t, $tt, array_pop($operands));
						$n->postfix = true;
						array_push($operands, $n);
					}
				break;

				case KEYWORD_FUNCTION:
					if (!$this->t->scanOperand)
						break 2;

					array_push($operands, $this->FunctionDefinition($x, false, EXPRESSED_FORM));
					$this->t->scanOperand = false;
				break;

				case KEYWORD_NULL: case KEYWORD_THIS: case KEYWORD_TRUE: case KEYWORD_FALSE:
				case TOKEN_IDENTIFIER: case TOKEN_NUMBER: case TOKEN_STRING: case TOKEN_REGEXP:
					if (!$this->t->scanOperand)
						break 2;

					array_push($operands, new JSNode($this->t));
					$this->t->scanOperand = false;
				break;

				case TOKEN_CONDCOMMENT_START:
				case TOKEN_CONDCOMMENT_END:
					if ($this->t->scanOperand)
						array_push($operators, new JSNode($this->t));
					else
						array_push($operands, new JSNode($this->t));
				break;

				case OP_LEFT_BRACKET:
					if ($this->t->scanOperand)
					{
						// Array initialiser.  Parse using recursive descent, as the
						// sub-grammar here is not an operator grammar.
						$n = new JSNode($this->t, JS_ARRAY_INIT);
						while (($tt = $this->t->peek()) != OP_RIGHT_BRACKET)
						{
							if ($tt == OP_COMMA)
							{
								$this->t->get();
								$n->addNode(null);
								continue;
							}

							$n->addNode($this->Expression($x, OP_COMMA));
							if (!$this->t->match(OP_COMMA))
								break;
						}

						$this->t->mustMatch(OP_RIGHT_BRACKET);
						array_push($operands, $n);
						$this->t->scanOperand = false;
					}
					else
					{
						// Property indexing operator.
						array_push($operators, new JSNode($this->t, JS_INDEX));
						$this->t->scanOperand = true;
						++$x->bracketLevel;
					}
				break;

				case OP_RIGHT_BRACKET:
					if ($this->t->scanOperand || $x->bracketLevel == $bl)
						break 2;

					while ($this->reduce($operators, $operands)->type != JS_INDEX)
						continue;

					--$x->bracketLevel;
				break;

				case OP_LEFT_CURLY:
					if (!$this->t->scanOperand)
						break 2;

					// Object initialiser.  As for array initialisers (see above),
					// parse using recursive descent.
					++$x->curlyLevel;
					$n = new JSNode($this->t, JS_OBJECT_INIT);
					while (!$this->t->match(OP_RIGHT_CURLY))
					{
						do
						{
							$tt = $this->t->get();
							$tv = $this->t->currentToken()->value;
							if (($tv == 'get' || $tv == 'set') && $this->t->peek() == TOKEN_IDENTIFIER)
							{
								if ($x->ecmaStrictMode)
									throw $this->t->newSyntaxError('Illegal property accessor');

								$n->addNode($this->FunctionDefinition($x, true, EXPRESSED_FORM));
							}
							else
							{
								switch ($tt)
								{
									case TOKEN_IDENTIFIER:
									case TOKEN_NUMBER:
									case TOKEN_STRING:
										$id = new JSNode($this->t);
									break;

									case OP_RIGHT_CURLY:
										if ($x->ecmaStrictMode)
											throw $this->t->newSyntaxError('Illegal trailing ,');
									break 3;

									default:
										throw $this->t->newSyntaxError('Invalid property name');
								}

								$this->t->mustMatch(OP_COLON);
								$n->addNode(new JSNode($this->t, JS_PROPERTY_INIT, $id, $this->Expression($x, OP_COMMA)));
							}
						}
						while ($this->t->match(OP_COMMA));

						$this->t->mustMatch(OP_RIGHT_CURLY);
						break;
					}

					array_push($operands, $n);
					$this->t->scanOperand = false;
					--$x->curlyLevel;
				break;

				case OP_RIGHT_CURLY:
					if (!$this->t->scanOperand && $x->curlyLevel != $cl)
						throw new Exception('PANIC: right curly botch');
				break 2;

				case OP_LEFT_PAREN:
					if ($this->t->scanOperand)
					{
						array_push($operators, new JSNode($this->t, JS_GROUP));
					}
					else
					{
						while (	!empty($operators) &&
							$this->opPrecedence[end($operators)->type] > $this->opPrecedence[KEYWORD_NEW]
						)
							$this->reduce($operators, $operands);

						// Handle () now, to regularize the n-ary case for n > 0.
						// We must set scanOperand in case there are arguments and
						// the first one is a regexp or unary+/-.
						$n = end($operators);
						$this->t->scanOperand = true;
						if ($this->t->match(OP_RIGHT_PAREN))
						{
							if ($n && $n->type == KEYWORD_NEW)
							{
								array_pop($operators);
								$n->addNode(array_pop($operands));
							}
							else
							{
								$n = new JSNode($this->t, JS_CALL, array_pop($operands), new JSNode($this->t, JS_LIST));
							}

							array_push($operands, $n);
							$this->t->scanOperand = false;
							break;
						}

						if ($n && $n->type == KEYWORD_NEW)
							$n->type = JS_NEW_WITH_ARGS;
						else
							array_push($operators, new JSNode($this->t, JS_CALL));
					}

					++$x->parenLevel;
				break;

				case OP_RIGHT_PAREN:
					if ($this->t->scanOperand || $x->parenLevel == $pl)
						break 2;

					while (($tt = $this->reduce($operators, $operands)->type) != JS_GROUP &&
						$tt != JS_CALL && $tt != JS_NEW_WITH_ARGS
					)
					{
						continue;
					}

					if ($tt != JS_GROUP)
					{
						$n = end($operands);
						if ($n->treeNodes[1]->type != OP_COMMA)
							$n->treeNodes[1] = new JSNode($this->t, JS_LIST, $n->treeNodes[1]);
						else
							$n->treeNodes[1]->type = JS_LIST;
					}

					--$x->parenLevel;
				break;

				// Automatic semicolon insertion means we may scan across a newline
				// and into the beginning of another statement.  If so, break out of
				// the while loop and let the t.scanOperand logic handle errors.
				default:
					break 2;
			}
		}

		if ($x->hookLevel != $hl)
			throw $this->t->newSyntaxError('Missing : in conditional expression');

		if ($x->parenLevel != $pl)
			throw $this->t->newSyntaxError('Missing ) in parenthetical');

		if ($x->bracketLevel != $bl)
			throw $this->t->newSyntaxError('Missing ] in index expression');

		if ($this->t->scanOperand)
			throw $this->t->newSyntaxError('Missing operand');

		// Resume default mode, scanning for operands, not operators.
		$this->t->scanOperand = true;
		$this->t->unget();

		while (count($operators))
			$this->reduce($operators, $operands);

		return array_pop($operands);
	}

	private function ParenExpression($x)
	{
		$this->t->mustMatch(OP_LEFT_PAREN);
		$n = $this->Expression($x);
		$this->t->mustMatch(OP_RIGHT_PAREN);

		return $n;
	}

	// Statement stack and nested statement handler.
	private function nest($x, $node, $end = false)
	{
		array_push($x->stmtStack, $node);
		$n = $this->statement($x);
		array_pop($x->stmtStack);

		if ($end)
			$this->t->mustMatch($end);

		return $n;
	}

	private function reduce(&$operators, &$operands)
	{
		$n = array_pop($operators);
		$op = $n->type;
		$arity = $this->opArity[$op];
		$c = count($operands);
		if ($arity == -2)
		{
			// Flatten left-associative trees
			if ($c >= 2)
			{
				$left = $operands[$c - 2];
				if ($left->type == $op)
				{
					$right = array_pop($operands);
					$left->addNode($right);
					return $left;
				}
			}
			$arity = 2;
		}

		// Always use push to add operands to n, to update start and end
		$a = array_splice($operands, $c - $arity);
		for ($i = 0; $i < $arity; $i++)
			$n->addNode($a[$i]);

		// Include closing bracket or postfix operator in [start,end]
		$te = $this->t->currentToken()->end;
		if ($n->end < $te)
			$n->end = $te;

		array_push($operands, $n);

		return $n;
	}
}

class JSCompilerContext
{
	public $inFunction = false;
	public $inForLoopInit = false;
	public $ecmaStrictMode = false;
	public $bracketLevel = 0;
	public $curlyLevel = 0;
	public $parenLevel = 0;
	public $hookLevel = 0;

	public $stmtStack = array();
	public $funDecls = array();
	public $varDecls = array();

	public function __construct($inFunction)
	{
		$this->inFunction = $inFunction;
	}
}

class JSNode
{
	private $type;
	private $value;
	private $lineno;
	private $start;
	private $end;

	public $treeNodes = array();
	public $funDecls = array();
	public $varDecls = array();

	public function __construct($t, $type=0)
	{
		if ($token = $t->currentToken())
		{
			$this->type = $type ? $type : $token->type;
			$this->value = $token->value;
			$this->lineno = $token->lineno;
			$this->start = $token->start;
			$this->end = $token->end;
		}
		else
		{
			$this->type = $type;
			$this->lineno = $t->lineno;
		}

		if (($numargs = func_num_args()) > 2)
		{
			$args = func_get_args();
			for ($i = 2; $i < $numargs; $i++)
				$this->addNode($args[$i]);
		}
	}

	// we don't want to bloat our object with all kind of specific properties, so we use overloading
	public function __set($name, $value)
	{
		$this->$name = $value;
	}

	public function __get($name)
	{
		if (isset($this->$name))
			return $this->$name;

		return null;
	}

	public function addNode($node)
	{
		if ($node !== null)
		{
			if ($node->start < $this->start)
				$this->start = $node->start;
			if ($this->end < $node->end)
				$this->end = $node->end;
		}

		$this->treeNodes[] = $node;
	}
}

class JSTokenizer
{
	private $cursor = 0;
	private $source;

	public $tokens = array();
	public $tokenIndex = 0;
	public $lookahead = 0;
	public $scanNewlines = false;
	public $scanOperand = true;

	public $filename;
	public $lineno;

	private $keywords = array(
		'break',
		'case', 'catch', 'const', 'continue',
		'debugger', 'default', 'delete', 'do',
		'else', 'enum',
		'false', 'finally', 'for', 'function',
		'if', 'in', 'instanceof',
		'new', 'null',
		'return',
		'switch',
		'this', 'throw', 'true', 'try', 'typeof',
		'var', 'void',
		'while', 'with'
	);

	private $opTypeNames = array(
		';', ',', '?', ':', '||', '&&', '|', '^',
		'&', '===', '==', '=', '!==', '!=', '<<', '<=',
		'<', '>>>', '>>', '>=', '>', '++', '--', '+',
		'-', '*', '/', '%', '!', '~', '.', '[',
		']', '{', '}', '(', ')', '@*/'
	);

	private $assignOps = array('|', '^', '&', '<<', '>>', '>>>', '+', '-', '*', '/', '%');
	private $opRegExp;

	public function __construct()
	{
		$this->opRegExp = '#^(' . implode('|', array_map('preg_quote', $this->opTypeNames)) . ')#';
	}

	public function init($source, $filename = '', $lineno = 1)
	{
		$this->source = $source;
		$this->filename = $filename ? $filename : '[inline]';
		$this->lineno = $lineno;

		$this->cursor = 0;
		$this->tokens = array();
		$this->tokenIndex = 0;
		$this->lookahead = 0;
		$this->scanNewlines = false;
		$this->scanOperand = true;
	}

	public function getInput($chunksize)
	{
		if ($chunksize)
			return substr($this->source, $this->cursor, $chunksize);

		return substr($this->source, $this->cursor);
	}

	public function isDone()
	{
		return $this->peek() == TOKEN_END;
	}

	public function match($tt)
	{
		return $this->get() == $tt || $this->unget();
	}

	public function mustMatch($tt)
	{
	        if (!$this->match($tt))
			throw $this->newSyntaxError('Unexpected token; token ' . $tt . ' expected');

		return $this->currentToken();
	}

	public function peek()
	{
		if ($this->lookahead)
		{
			$next = $this->tokens[($this->tokenIndex + $this->lookahead) & 3];
			if ($this->scanNewlines && $next->lineno != $this->lineno)
				$tt = TOKEN_NEWLINE;
			else
				$tt = $next->type;
		}
		else
		{
			$tt = $this->get();
			$this->unget();
		}

		return $tt;
	}

	public function peekOnSameLine()
	{
		$this->scanNewlines = true;
		$tt = $this->peek();
		$this->scanNewlines = false;

		return $tt;
	}

	public function currentToken()
	{
		if (!empty($this->tokens))
			return $this->tokens[$this->tokenIndex];
	}

	public function get($chunksize = 1000)
	{
		while($this->lookahead)
		{
			$this->lookahead--;
			$this->tokenIndex = ($this->tokenIndex + 1) & 3;
			$token = $this->tokens[$this->tokenIndex];
			if ($token->type != TOKEN_NEWLINE || $this->scanNewlines)
				return $token->type;
		}

		$conditional_comment = false;

		// strip whitespace and comments
		while(true)
		{
			$input = $this->getInput($chunksize);

			// whitespace handling; gobble up \r as well (effectively we don't have support for MAC newlines!)
			$re = $this->scanNewlines ? '/^[ \r\t]+/' : '/^\s+/';
			if (preg_match($re, $input, $match))
			{
				$spaces = $match[0];
				$spacelen = strlen($spaces);
				$this->cursor += $spacelen;
				if (!$this->scanNewlines)
					$this->lineno += substr_count($spaces, "\n");

				if ($spacelen == $chunksize)
					continue; // complete chunk contained whitespace

				$input = $this->getInput($chunksize);
				if ($input == '' || $input[0] != '/')
					break;
			}

			// Comments
			if (!preg_match('/^\/(?:\*(@(?:cc_on|if|elif|else|end))?.*?\*\/|\/[^\n]*)/s', $input, $match))
			{
				if (!$chunksize)
					break;

				// retry with a full chunk fetch; this also prevents breakage of long regular expressions (which will never match a comment)
				$chunksize = null;
				continue;
			}

			// check if this is a conditional (JScript) comment
			if (!empty($match[1]))
			{
				$match[0] = '/*' . $match[1];
				$conditional_comment = true;
				break;
			}
			else
			{
				$this->cursor += strlen($match[0]);
				$this->lineno += substr_count($match[0], "\n");
			}
		}

		if ($input == '')
		{
			$tt = TOKEN_END;
			$match = array('');
		}
		elseif ($conditional_comment)
		{
			$tt = TOKEN_CONDCOMMENT_START;
		}
		else
		{
			switch ($input[0])
			{
				case '0':
					// hexadecimal
					if (($input[1] == 'x' || $input[1] == 'X') && preg_match('/^0x[0-9a-f]+/i', $input, $match))
					{
						$tt = TOKEN_NUMBER;
						break;
					}
				// FALL THROUGH

				case '1': case '2': case '3': case '4': case '5':
				case '6': case '7': case '8': case '9':
					// should always match
					preg_match('/^\d+(?:\.\d*)?(?:[eE][-+]?\d+)?/', $input, $match);
					$tt = TOKEN_NUMBER;
				break;

				case "'":
					if (preg_match('/^\'(?:[^\\\\\'\r\n]++|\\\\(?:.|\r?\n))*\'/', $input, $match))
					{
						$tt = TOKEN_STRING;
					}
					else
					{
						if ($chunksize)
							return $this->get(null); // retry with a full chunk fetch

						throw $this->newSyntaxError('Unterminated string literal');
					}
				break;

				case '"':
					if (preg_match('/^"(?:[^\\\\"\r\n]++|\\\\(?:.|\r?\n))*"/', $input, $match))
					{
						$tt = TOKEN_STRING;
					}
					else
					{
						if ($chunksize)
							return $this->get(null); // retry with a full chunk fetch

						throw $this->newSyntaxError('Unterminated string literal');
					}
				break;

				case '/':
					if ($this->scanOperand && preg_match('/^\/((?:\\\\.|\[(?:\\\\.|[^\]])*\]|[^\/])+)\/([gimy]*)/', $input, $match))
					{
						$tt = TOKEN_REGEXP;
						break;
					}
				// FALL THROUGH

				case '|':
				case '^':
				case '&':
				case '<':
				case '>':
				case '+':
				case '-':
				case '*':
				case '%':
				case '=':
				case '!':
					// should always match
					preg_match($this->opRegExp, $input, $match);
					$op = $match[0];
					if (in_array($op, $this->assignOps) && $input[strlen($op)] == '=')
					{
						$tt = OP_ASSIGN;
						$match[0] .= '=';
					}
					else
					{
						$tt = $op;
						if ($this->scanOperand)
						{
							if ($op == OP_PLUS)
								$tt = OP_UNARY_PLUS;
							elseif ($op == OP_MINUS)
								$tt = OP_UNARY_MINUS;
						}
						$op = null;
					}
				break;

				case '.':
					if (preg_match('/^\.\d+(?:[eE][-+]?\d+)?/', $input, $match))
					{
						$tt = TOKEN_NUMBER;
						break;
					}
				// FALL THROUGH

				case ';':
				case ',':
				case '?':
				case ':':
				case '~':
				case '[':
				case ']':
				case '{':
				case '}':
				case '(':
				case ')':
					// these are all single
					$match = array($input[0]);
					$tt = $input[0];
				break;

				case '@':
					// check end of conditional comment
					if (substr($input, 0, 3) == '@*/')
					{
						$match = array('@*/');
						$tt = TOKEN_CONDCOMMENT_END;
					}
					else
						throw $this->newSyntaxError('Illegal token');
				break;

				case "\n":
					if ($this->scanNewlines)
					{
						$match = array("\n");
						$tt = TOKEN_NEWLINE;
					}
					else
						throw $this->newSyntaxError('Illegal token');
				break;

				default:
					// FIXME: add support for unicode and unicode escape sequence \uHHHH
					if (preg_match('/^[$\w]+/', $input, $match))
					{
						$tt = in_array($match[0], $this->keywords) ? $match[0] : TOKEN_IDENTIFIER;
					}
					else
						throw $this->newSyntaxError('Illegal token');
			}
		}

		$this->tokenIndex = ($this->tokenIndex + 1) & 3;

		if (!isset($this->tokens[$this->tokenIndex]))
			$this->tokens[$this->tokenIndex] = new JSToken();

		$token = $this->tokens[$this->tokenIndex];
		$token->type = $tt;

		if ($tt == OP_ASSIGN)
			$token->assignOp = $op;

		$token->start = $this->cursor;

		$token->value = $match[0];
		$this->cursor += strlen($match[0]);

		$token->end = $this->cursor;
		$token->lineno = $this->lineno;

		return $tt;
	}

	public function unget()
	{
		if (++$this->lookahead == 4)
			throw $this->newSyntaxError('PANIC: too much lookahead!');

		$this->tokenIndex = ($this->tokenIndex - 1) & 3;
	}

	public function newSyntaxError($m)
	{
		return new Exception('Parse error: ' . $m . ' in file \'' . $this->filename . '\' on line ' . $this->lineno);
	}
}

class JSToken
{
	public $type;
	public $value;
	public $start;
	public $end;
	public $lineno;
	public $assignOp;
}

/*!
 * cssmin.php rev ebaf67b 12/06/2013
 * Author: Tubal Martin - http://tubalmartin.me/
 * Repo: https://github.com/tubalmartin/YUI-CSS-compressor-PHP-port
 *
 * This is a PHP port of the CSS minification tool distributed with YUICompressor, 
 * itself a port of the cssmin utility by Isaac Schlueter - http://foohack.com/
 * Permission is hereby granted to use the PHP version under the same
 * conditions as the YUICompressor.
 */

/*!
 * YUI Compressor
 * http://developer.yahoo.com/yui/compressor/
 * Author: Julien Lecomte - http://www.julienlecomte.net/
 * Copyright (c) 2013 Yahoo! Inc. All rights reserved.
 * The copyrights embodied in the content of this file are licensed
 * by Yahoo! Inc. under the BSD (revised) open source license.
 */

class CSSmin
{
    const NL = '___YUICSSMIN_PRESERVED_NL___';
    const TOKEN = '___YUICSSMIN_PRESERVED_TOKEN_';
    const COMMENT = '___YUICSSMIN_PRESERVE_CANDIDATE_COMMENT_';
    const CLASSCOLON = '___YUICSSMIN_PSEUDOCLASSCOLON___';
    const QUERY_FRACTION = '___YUICSSMIN_QUERY_FRACTION___';

    private $comments;
    private $preserved_tokens;
    private $memory_limit;
    private $max_execution_time;
    private $pcre_backtrack_limit;
    private $pcre_recursion_limit;
    private $raise_php_limits;

    /**
     * @param bool|int $raise_php_limits
     * If true, PHP settings will be raised if needed
     */
    public function __construct($raise_php_limits = TRUE)
    {
        // Set suggested PHP limits
        $this->memory_limit = 128 * 1048576; // 128MB in bytes
        $this->max_execution_time = 60; // 1 min
        $this->pcre_backtrack_limit = 1000 * 1000;
        $this->pcre_recursion_limit =  500 * 1000;

        $this->raise_php_limits = (bool) $raise_php_limits;
    }

    /**
     * Minify a string of CSS
     * @param string $css
     * @param int|bool $linebreak_pos
     * @return string
     */
    public function run($css = '', $linebreak_pos = FALSE)
    {
        if (empty($css)) {
            return '';
        }

        if ($this->raise_php_limits) {
            $this->do_raise_php_limits();
        }

        $this->comments = array();
        $this->preserved_tokens = array();

        $start_index = 0;
        $length = strlen($css);

        $css = $this->extract_data_urls($css);

        // collect all comment blocks...
        while (($start_index = $this->index_of($css, '/*', $start_index)) >= 0) {
            $end_index = $this->index_of($css, '*/', $start_index + 2);
            if ($end_index < 0) {
                $end_index = $length;
            }
            $comment_found = $this->str_slice($css, $start_index + 2, $end_index);
            $this->comments[] = $comment_found;
            $comment_preserve_string = self::COMMENT . (count($this->comments) - 1) . '___';
            $css = $this->str_slice($css, 0, $start_index + 2) . $comment_preserve_string . $this->str_slice($css, $end_index);
            // Set correct start_index: Fixes issue #2528130
            $start_index = $end_index + 2 + strlen($comment_preserve_string) - strlen($comment_found);
        }

        // preserve strings so their content doesn't get accidentally minified
        $css = preg_replace_callback('/(?:"(?:[^\\\\"]|\\\\.|\\\\)*")|'."(?:'(?:[^\\\\']|\\\\.|\\\\)*')/S", array($this, 'replace_string'), $css);

        // Let's divide css code in chunks of 25.000 chars aprox.
        // Reason: PHP's PCRE functions like preg_replace have a "backtrack limit"
        // of 100.000 chars by default (php < 5.3.7) so if we're dealing with really
        // long strings and a (sub)pattern matches a number of chars greater than
        // the backtrack limit number (i.e. /(.*)/s) PCRE functions may fail silently
        // returning NULL and $css would be empty.
        $charset = '';
        $charset_regexp = '/(@charset)( [^;]+;)/i';
        $css_chunks = array();
        $css_chunk_length = 25000; // aprox size, not exact
        $start_index = 0;
        $i = $css_chunk_length; // save initial iterations
        $l = strlen($css);


        // if the number of characters is 25000 or less, do not chunk
        if ($l <= $css_chunk_length) {
            $css_chunks[] = $css;
        } else {
            // chunk css code securely
            while ($i < $l) {
                $i += 50; // save iterations. 500 checks for a closing curly brace }
                if ($l - $start_index <= $css_chunk_length || $i >= $l) {
                    $css_chunks[] = $this->str_slice($css, $start_index);
                    break;
                }
                if ($css[$i - 1] === '}' && $i - $start_index > $css_chunk_length) {
                    // If there are two ending curly braces }} separated or not by spaces,
                    // join them in the same chunk (i.e. @media blocks)
                    $next_chunk = substr($css, $i);
                    if (preg_match('/^\s*\}/', $next_chunk)) {
                        $i = $i + $this->index_of($next_chunk, '}') + 1;
                    }

                    $css_chunks[] = $this->str_slice($css, $start_index, $i);
                    $start_index = $i;
                }
            }
        }

        // Minify each chunk
        for ($i = 0, $n = count($css_chunks); $i < $n; $i++) {
            $css_chunks[$i] = $this->minify($css_chunks[$i], $linebreak_pos);
            // Keep the first @charset at-rule found
            if (empty($charset) && preg_match($charset_regexp, $css_chunks[$i], $matches)) {
                $charset = strtolower($matches[1]) . $matches[2];
            }
            // Delete all @charset at-rules
            $css_chunks[$i] = preg_replace($charset_regexp, '', $css_chunks[$i]);
        }

        // Update the first chunk and push the charset to the top of the file.
        $css_chunks[0] = $charset . $css_chunks[0];

        return implode('', $css_chunks);
    }

    /**
     * Sets the memory limit for this script
     * @param int|string $limit
     */
    public function set_memory_limit($limit)
    {
        $this->memory_limit = $this->normalize_int($limit);
    }

    /**
     * Sets the maximum execution time for this script
     * @param int|string $seconds
     */
    public function set_max_execution_time($seconds)
    {
        $this->max_execution_time = (int) $seconds;
    }

    /**
     * Sets the PCRE backtrack limit for this script
     * @param int $limit
     */
    public function set_pcre_backtrack_limit($limit)
    {
        $this->pcre_backtrack_limit = (int) $limit;
    }

    /**
     * Sets the PCRE recursion limit for this script
     * @param int $limit
     */
    public function set_pcre_recursion_limit($limit)
    {
        $this->pcre_recursion_limit = (int) $limit;
    }

    /**
     * Try to configure PHP to use at least the suggested minimum settings
     */
    private function do_raise_php_limits()
    {
        $php_limits = array(
            'memory_limit' => $this->memory_limit,
            'max_execution_time' => $this->max_execution_time,
            'pcre.backtrack_limit' => $this->pcre_backtrack_limit,
            'pcre.recursion_limit' =>  $this->pcre_recursion_limit
        );

        // If current settings are higher respect them.
        foreach ($php_limits as $name => $suggested) {
            $current = $this->normalize_int(ini_get($name));
            // memory_limit exception: allow -1 for "no memory limit".
            if ($current > -1 && ($suggested == -1 || $current < $suggested)) {
                ini_set($name, $suggested);
            }
        }
    }

    /**
     * Does bulk of the minification
     * @param string $css
     * @param int|bool $linebreak_pos
     * @return string
     */
    private function minify($css, $linebreak_pos)
    {
        // strings are safe, now wrestle the comments
        for ($i = 0, $max = count($this->comments); $i < $max; $i++) {

            $token = $this->comments[$i];
            $placeholder = '/' . self::COMMENT . $i . '___/';

            // ! in the first position of the comment means preserve
            // so push to the preserved tokens keeping the !
            if (substr($token, 0, 1) === '!') {
                $this->preserved_tokens[] = $token;
                $token_tring = self::TOKEN . (count($this->preserved_tokens) - 1) . '___';
                $css = preg_replace($placeholder, $token_tring, $css, 1);
                // Preserve new lines for /*! important comments
                $css = preg_replace('/\s*[\n\r\f]+\s*(\/\*'. $token_tring .')/S', self::NL.'$1', $css);
                $css = preg_replace('/('. $token_tring .'\*\/)\s*[\n\r\f]+\s*/', '$1'.self::NL, $css);
                continue;
            }

            // \ in the last position looks like hack for Mac/IE5
            // shorten that to /*\*/ and the next one to /**/
            if (substr($token, (strlen($token) - 1), 1) === '\\') {
                $this->preserved_tokens[] = '\\';
                $css = preg_replace($placeholder,  self::TOKEN . (count($this->preserved_tokens) - 1) . '___', $css, 1);
                $i = $i + 1; // attn: advancing the loop
                $this->preserved_tokens[] = '';
                $css = preg_replace('/' . self::COMMENT . $i . '___/',  self::TOKEN . (count($this->preserved_tokens) - 1) . '___', $css, 1);
                continue;
            }

            // keep empty comments after child selectors (IE7 hack)
            // e.g. html >/**/ body
            if (strlen($token) === 0) {
                $start_index = $this->index_of($css, $this->str_slice($placeholder, 1, -1));
                if ($start_index > 2) {
                    if (substr($css, $start_index - 3, 1) === '>') {
                        $this->preserved_tokens[] = '';
                        $css = preg_replace($placeholder,  self::TOKEN . (count($this->preserved_tokens) - 1) . '___', $css, 1);
                    }
                }
            }

            // in all other cases kill the comment
            $css = preg_replace('/\/\*' . $this->str_slice($placeholder, 1, -1) . '\*\//', '', $css, 1);
        }


        // Normalize all whitespace strings to single spaces. Easier to work with that way.
        $css = preg_replace('/\s+/', ' ', $css);

        // Shorten & preserve calculations calc(...) since spaces are important
        $css = preg_replace_callback('/calc(\(((?:[^\(\)]+|(?1))*)\))/i', array($this, 'replace_calc'), $css);

        // Replace positive sign from numbers preceded by : or a white-space before the leading space is removed
        // +1.2em to 1.2em, +.8px to .8px, +2% to 2%
        $css = preg_replace('/((?<!\\\\)\:|\s)\+(\.?\d+)/S', '$1$2', $css);

        // Remove leading zeros from integer and float numbers preceded by : or a white-space
        // 000.6 to .6, -0.8 to -.8, 0050 to 50, -01.05 to -1.05
        $css = preg_replace('/((?<!\\\\)\:|\s)(\-?)0+(\.?\d+)/S', '$1$2$3', $css);

        // Remove trailing zeros from float numbers preceded by : or a white-space
        // -6.0100em to -6.01em, .0100 to .01, 1.200px to 1.2px
        $css = preg_replace('/((?<!\\\\)\:|\s)(\-?)(\d?\.\d+?)0+([^\d])/S', '$1$2$3$4', $css);

        // Remove trailing .0 -> -9.0 to -9
        $css = preg_replace('/((?<!\\\\)\:|\s)(\-?\d+)\.0([^\d])/S', '$1$2$3', $css);

        // Replace 0 length numbers with 0
        $css = preg_replace('/((?<!\\\\)\:|\s)\-?\.?0+([^\d])/S', '${1}0$2', $css);

        // Remove the spaces before the things that should not have spaces before them.
        // But, be careful not to turn "p :link {...}" into "p:link{...}"
        // Swap out any pseudo-class colons with the token, and then swap back.
        $css = preg_replace_callback('/(?:^|\})(?:(?:[^\{\:])+\:)+(?:[^\{]*\{)/', array($this, 'replace_colon'), $css);
        
        // Remove spaces before the things that should not have spaces before them.
        $css = preg_replace('/\s+([\!\{\}\;\:\>\+\(\)\]\~\=,])/', '$1', $css);

        // Restore spaces for !important
        $css = preg_replace('/\!important/i', ' !important', $css);

        // bring back the colon
        $css = preg_replace('/' . self::CLASSCOLON . '/', ':', $css);

        // retain space for special IE6 cases
        $css = preg_replace_callback('/\:first\-(line|letter)(\{|,)/i', array($this, 'lowercase_pseudo_first'), $css);

        // no space after the end of a preserved comment
        $css = preg_replace('/\*\/ /', '*/', $css);

        // lowercase some popular @directives
        $css = preg_replace_callback('/@(font-face|import|(?:-(?:atsc|khtml|moz|ms|o|wap|webkit)-)?keyframe|media|page|namespace)/i', array($this, 'lowercase_directives'), $css);

        // lowercase some more common pseudo-elements
        $css = preg_replace_callback('/:(active|after|before|checked|disabled|empty|enabled|first-(?:child|of-type)|focus|hover|last-(?:child|of-type)|link|only-(?:child|of-type)|root|:selection|target|visited)/i', array($this, 'lowercase_pseudo_elements'), $css);

        // lowercase some more common functions
        $css = preg_replace_callback('/:(lang|not|nth-child|nth-last-child|nth-last-of-type|nth-of-type|(?:-(?:moz|webkit)-)?any)\(/i', array($this, 'lowercase_common_functions'), $css);

        // lower case some common function that can be values
        // NOTE: rgb() isn't useful as we replace with #hex later, as well as and() is already done for us
        $css = preg_replace_callback('/([:,\( ]\s*)(attr|color-stop|from|rgba|to|url|(?:-(?:atsc|khtml|moz|ms|o|wap|webkit)-)?(?:calc|max|min|(?:repeating-)?(?:linear|radial)-gradient)|-webkit-gradient)/iS', array($this, 'lowercase_common_functions_values'), $css);
        
        // Put the space back in some cases, to support stuff like
        // @media screen and (-webkit-min-device-pixel-ratio:0){
        $css = preg_replace('/\band\(/i', 'and (', $css);

        // Remove the spaces after the things that should not have spaces after them.
        $css = preg_replace('/([\!\{\}\:;\>\+\(\[\~\=,])\s+/S', '$1', $css);

        // remove unnecessary semicolons
        $css = preg_replace('/;+\}/', '}', $css);

        // Fix for issue: #2528146
        // Restore semicolon if the last property is prefixed with a `*` (lte IE7 hack)
        // to avoid issues on Symbian S60 3.x browsers.
        $css = preg_replace('/(\*[a-z0-9\-]+\s*\:[^;\}]+)(\})/', '$1;$2', $css);

        // Replace 0 length units 0(px,em,%) with 0.
        $css = preg_replace('/(^|[^0-9])(?:0?\.)?0(?:em|ex|ch|rem|vw|vh|vm|vmin|cm|mm|in|px|pt|pc|%|deg|g?rad|m?s|k?hz)/iS', '${1}0', $css);

        // Replace 0 0; or 0 0 0; or 0 0 0 0; with 0.
        $css = preg_replace('/\:0(?: 0){1,3}(;|\}| \!)/', ':0$1', $css);

        // Fix for issue: #2528142
        // Replace text-shadow:0; with text-shadow:0 0 0;
        $css = preg_replace('/(text-shadow\:0)(;|\}| \!)/i', '$1 0 0$2', $css);

        // Replace background-position:0; with background-position:0 0;
        // same for transform-origin
        // Changing -webkit-mask-position: 0 0 to just a single 0 will result in the second parameter defaulting to 50% (center)
        $css = preg_replace('/(background\-position|webkit-mask-position|(?:webkit|moz|o|ms|)\-?transform\-origin)\:0(;|\}| \!)/iS', '$1:0 0$2', $css);

        // Shorten colors from rgb(51,102,153) to #336699, rgb(100%,0%,0%) to #ff0000 (sRGB color space)
        // Shorten colors from hsl(0, 100%, 50%) to #ff0000 (sRGB color space)
        // This makes it more likely that it'll get further compressed in the next step.
        $css = preg_replace_callback('/rgb\s*\(\s*([0-9,\s\-\.\%]+)\s*\)(.{1})/i', array($this, 'rgb_to_hex'), $css);
        $css = preg_replace_callback('/hsl\s*\(\s*([0-9,\s\-\.\%]+)\s*\)(.{1})/i', array($this, 'hsl_to_hex'), $css);

        // Shorten colors from #AABBCC to #ABC or short color name.
        $css = $this->compress_hex_colors($css);

        // border: none to border:0, outline: none to outline:0
        $css = preg_replace('/(border\-?(?:top|right|bottom|left|)|outline)\:none(;|\}| \!)/iS', '$1:0$2', $css);

        // shorter opacity IE filter
        $css = preg_replace('/progid\:DXImageTransform\.Microsoft\.Alpha\(Opacity\=/i', 'alpha(opacity=', $css);

        // Find a fraction that is used for Opera's -o-device-pixel-ratio query
        // Add token to add the "\" back in later
        $css = preg_replace('/\(([a-z\-]+):([0-9]+)\/([0-9]+)\)/i', '($1:$2'. self::QUERY_FRACTION .'$3)', $css);

        // Remove empty rules.
        $css = preg_replace('/[^\};\{\/]+\{\}/S', '', $css);

        // Add "/" back to fix Opera -o-device-pixel-ratio query
        $css = preg_replace('/'. self::QUERY_FRACTION .'/', '/', $css);

        // Some source control tools don't like it when files containing lines longer
        // than, say 8000 characters, are checked in. The linebreak option is used in
        // that case to split long lines after a specific column.
        if ($linebreak_pos !== FALSE && (int) $linebreak_pos >= 0) {
            $linebreak_pos = (int) $linebreak_pos;
            $start_index = $i = 0;
            while ($i < strlen($css)) {
                $i++;
                if ($css[$i - 1] === '}' && $i - $start_index > $linebreak_pos) {
                    $css = $this->str_slice($css, 0, $i) . "\n" . $this->str_slice($css, $i);
                    $start_index = $i;
                }
            }
        }

        // Replace multiple semi-colons in a row by a single one
        // See SF bug #1980989
        $css = preg_replace('/;;+/', ';', $css);

        // Restore new lines for /*! important comments
        $css = preg_replace('/'. self::NL .'/', "\n", $css);

        // Lowercase all uppercase properties
        $css = preg_replace_callback('/(\{|\;)([A-Z\-]+)(\:)/', array($this, 'lowercase_properties'), $css);

        // restore preserved comments and strings
        for ($i = 0, $max = count($this->preserved_tokens); $i < $max; $i++) {
            $css = preg_replace('/' . self::TOKEN . $i . '___/', $this->preserved_tokens[$i], $css, 1);
        }

        // Trim the final string (for any leading or trailing white spaces)
        return trim($css);
    }

    /**
     * Utility method to replace all data urls with tokens before we start
     * compressing, to avoid performance issues running some of the subsequent
     * regexes against large strings chunks.
     *
     * @param string $css
     * @return string
     */
    private function extract_data_urls($css)
    {
        // Leave data urls alone to increase parse performance.
        $max_index = strlen($css) - 1;
        $append_index = $index = $last_index = $offset = 0;
        $sb = array();
        $pattern = '/url\(\s*(["\']?)data\:/i';

        // Since we need to account for non-base64 data urls, we need to handle
        // ' and ) being part of the data string. Hence switching to indexOf,
        // to determine whether or not we have matching string terminators and
        // handling sb appends directly, instead of using matcher.append* methods.

        while (preg_match($pattern, $css, $m, 0, $offset)) {
            $index = $this->index_of($css, $m[0], $offset);
            $last_index = $index + strlen($m[0]);
            $start_index = $index + 4; // "url(".length()
            $end_index = $last_index - 1;
            $terminator = $m[1]; // ', " or empty (not quoted)
            $found_terminator = FALSE;

            if (strlen($terminator) === 0) {
                $terminator = ')';
            }

            while ($found_terminator === FALSE && $end_index+1 <= $max_index) {
                $end_index = $this->index_of($css, $terminator, $end_index + 1);

                // endIndex == 0 doesn't really apply here
                if ($end_index > 0 && substr($css, $end_index - 1, 1) !== '\\') {
                    $found_terminator = TRUE;
                    if (')' != $terminator) {
                        $end_index = $this->index_of($css, ')', $end_index);
                    }
                }
            }

            // Enough searching, start moving stuff over to the buffer
            $sb[] = $this->str_slice($css, $append_index, $index);

            if ($found_terminator) {
                $token = $this->str_slice($css, $start_index, $end_index);
                $token = preg_replace('/\s+/', '', $token);
                $this->preserved_tokens[] = $token;

                $preserver = 'url(' . self::TOKEN . (count($this->preserved_tokens) - 1) . '___)';
                $sb[] = $preserver;

                $append_index = $end_index + 1;
            } else {
                // No end terminator found, re-add the whole match. Should we throw/warn here?
                $sb[] = $this->str_slice($css, $index, $last_index);
                $append_index = $last_index;
            }

            $offset = $last_index;
        }

        $sb[] = $this->str_slice($css, $append_index);

        return implode('', $sb);
    }

    /**
     * Utility method to compress hex color values of the form #AABBCC to #ABC or short color name.
     *
     * DOES NOT compress CSS ID selectors which match the above pattern (which would break things).
     * e.g. #AddressForm { ... }
     *
     * DOES NOT compress IE filters, which have hex color values (which would break things).
     * e.g. filter: chroma(color="#FFFFFF");
     *
     * DOES NOT compress invalid hex values.
     * e.g. background-color: #aabbccdd
     *
     * @param string $css
     * @return string
     */
    private function compress_hex_colors($css)
    {
        // Look for hex colors inside { ... } (to avoid IDs) and which don't have a =, or a " in front of them (to avoid filters)
        $pattern = '/(\=\s*?["\']?)?#([0-9a-f])([0-9a-f])([0-9a-f])([0-9a-f])([0-9a-f])([0-9a-f])(\}|[^0-9a-f{][^{]*?\})/iS';
        $_index = $index = $last_index = $offset = 0;
        $sb = array();
        // See: http://ajaxmin.codeplex.com/wikipage?title=CSS%20Colors
        $short_safe = array(
            '#808080' => 'gray',
            '#008000' => 'green',
            '#800000' => 'maroon',
            '#000080' => 'navy',
            '#808000' => 'olive',
            '#ffa500' => 'orange',
            '#800080' => 'purple',
            '#c0c0c0' => 'silver',
            '#008080' => 'teal',
            '#f00' => 'red'
        );

        while (preg_match($pattern, $css, $m, 0, $offset)) {
            $index = $this->index_of($css, $m[0], $offset);
            $last_index = $index + strlen($m[0]);
            $is_filter = $m[1] !== null && $m[1] !== '';

            $sb[] = $this->str_slice($css, $_index, $index);

            if ($is_filter) {
                // Restore, maintain case, otherwise filter will break
                $sb[] = $m[1] . '#' . $m[2] . $m[3] . $m[4] . $m[5] . $m[6] . $m[7];
            } else {
                if (strtolower($m[2]) == strtolower($m[3]) &&
                    strtolower($m[4]) == strtolower($m[5]) &&
                    strtolower($m[6]) == strtolower($m[7])) {
                    // Compress.
                    $hex = '#' . strtolower($m[3] . $m[5] . $m[7]);
                } else {
                    // Non compressible color, restore but lower case.
                    $hex = '#' . strtolower($m[2] . $m[3] . $m[4] . $m[5] . $m[6] . $m[7]);
                }
                // replace Hex colors to short safe color names
                $sb[] = array_key_exists($hex, $short_safe) ? $short_safe[$hex] : $hex;
            }

            $_index = $offset = $last_index - strlen($m[8]);
        }

        $sb[] = $this->str_slice($css, $_index);

        return implode('', $sb);
    }

    /* CALLBACKS
     * ---------------------------------------------------------------------------------------------
     */

    private function replace_string($matches)
    {
        $match = $matches[0];
        $quote = substr($match, 0, 1);
        // Must use addcslashes in PHP to avoid parsing of backslashes
        $match = addcslashes($this->str_slice($match, 1, -1), '\\');

        // maybe the string contains a comment-like substring?
        // one, maybe more? put'em back then
        if (($pos = $this->index_of($match, self::COMMENT)) >= 0) {
            for ($i = 0, $max = count($this->comments); $i < $max; $i++) {
                $match = preg_replace('/' . self::COMMENT . $i . '___/', $this->comments[$i], $match, 1);
            }
        }

        // minify alpha opacity in filter strings
        $match = preg_replace('/progid\:DXImageTransform\.Microsoft\.Alpha\(Opacity\=/i', 'alpha(opacity=', $match);

        $this->preserved_tokens[] = $match;
        return $quote . self::TOKEN . (count($this->preserved_tokens) - 1) . '___' . $quote;
    }

    private function replace_colon($matches)
    {
        return preg_replace('/\:/', self::CLASSCOLON, $matches[0]);
    }

    private function replace_calc($matches)
    {
        $this->preserved_tokens[] = trim(preg_replace('/\s*([\*\/\(\),])\s*/', '$1', $matches[2]));
        return 'calc('. self::TOKEN . (count($this->preserved_tokens) - 1) . '___' . ')';
    }

    private function rgb_to_hex($matches)
    {
        // Support for percentage values rgb(100%, 0%, 45%);
        if ($this->index_of($matches[1], '%') >= 0){
            $rgbcolors = explode(',', str_replace('%', '', $matches[1]));
            for ($i = 0; $i < count($rgbcolors); $i++) {
                $rgbcolors[$i] = $this->round_number(floatval($rgbcolors[$i]) * 2.55);
            }
        } else {
            $rgbcolors = explode(',', $matches[1]);
        }

        // Values outside the sRGB color space should be clipped (0-255)
        for ($i = 0; $i < count($rgbcolors); $i++) {
            $rgbcolors[$i] = $this->clamp_number(intval($rgbcolors[$i], 10), 0, 255);
            $rgbcolors[$i] = sprintf("%02x", $rgbcolors[$i]);
        }

        // Fix for issue #2528093
        if (!preg_match('/[\s\,\);\}]/', $matches[2])){
            $matches[2] = ' ' . $matches[2];
        }

        return '#' . implode('', $rgbcolors) . $matches[2];
    }

    private function hsl_to_hex($matches)
    {
        $values = explode(',', str_replace('%', '', $matches[1]));
        $h = floatval($values[0]);
        $s = floatval($values[1]);
        $l = floatval($values[2]);

        // Wrap and clamp, then fraction!
        $h = ((($h % 360) + 360) % 360) / 360;
        $s = $this->clamp_number($s, 0, 100) / 100;
        $l = $this->clamp_number($l, 0, 100) / 100;

        if ($s == 0) {
            $r = $g = $b = $this->round_number(255 * $l);
        } else {
            $v2 = $l < 0.5 ? $l * (1 + $s) : ($l + $s) - ($s * $l);
            $v1 = (2 * $l) - $v2;
            $r = $this->round_number(255 * $this->hue_to_rgb($v1, $v2, $h + (1/3)));
            $g = $this->round_number(255 * $this->hue_to_rgb($v1, $v2, $h));
            $b = $this->round_number(255 * $this->hue_to_rgb($v1, $v2, $h - (1/3)));
        }

        return $this->rgb_to_hex(array('', $r.','.$g.','.$b, $matches[2]));
    }

    private function lowercase_pseudo_first($matches)
    {
        return ':first-'. strtolower($matches[1]) .' '. $matches[2];
    }

    private function lowercase_directives($matches) 
    {
        return '@'. strtolower($matches[1]);
    }

    private function lowercase_pseudo_elements($matches) 
    {
        return ':'. strtolower($matches[1]);
    }

    private function lowercase_common_functions($matches) 
    {
        return ':'. strtolower($matches[1]) .'(';
    }

    private function lowercase_common_functions_values($matches) 
    {
        return $matches[1] . strtolower($matches[2]);
    }

    private function lowercase_properties($matches)
    {
        return $matches[1].strtolower($matches[2]).$matches[3];
    }

    /* HELPERS
     * ---------------------------------------------------------------------------------------------
     */

    private function hue_to_rgb($v1, $v2, $vh)
    {
        $vh = $vh < 0 ? $vh + 1 : ($vh > 1 ? $vh - 1 : $vh);
        if ($vh * 6 < 1) return $v1 + ($v2 - $v1) * 6 * $vh;
        if ($vh * 2 < 1) return $v2;
        if ($vh * 3 < 2) return $v1 + ($v2 - $v1) * ((2/3) - $vh) * 6;
        return $v1;
    }

    private function round_number($n)
    {
        return intval(floor(floatval($n) + 0.5), 10);
    }

    private function clamp_number($n, $min, $max)
    {
        return min(max($n, $min), $max);
    }

    /**
     * PHP port of Javascript's "indexOf" function for strings only
     * Author: Tubal Martin http://blog.margenn.com
     *
     * @param string $haystack
     * @param string $needle
     * @param int    $offset index (optional)
     * @return int
     */
    private function index_of($haystack, $needle, $offset = 0)
    {
        $index = strpos($haystack, $needle, $offset);

        return ($index !== FALSE) ? $index : -1;
    }

    /**
     * PHP port of Javascript's "slice" function for strings only
     * Author: Tubal Martin http://blog.margenn.com
     * Tests: http://margenn.com/tubal/str_slice/
     *
     * @param string   $str
     * @param int      $start index
     * @param int|bool $end index (optional)
     * @return string
     */
    private function str_slice($str, $start = 0, $end = FALSE)
    {
        if ($end !== FALSE && ($start < 0 || $end <= 0)) {
            $max = strlen($str);

            if ($start < 0) {
                if (($start = $max + $start) < 0) {
                    return '';
                }
            }

            if ($end < 0) {
                if (($end = $max + $end) < 0) {
                    return '';
                }
            }

            if ($end <= $start) {
                return '';
            }
        }

        $slice = ($end === FALSE) ? substr($str, $start) : substr($str, $start, $end - $start);
        return ($slice === FALSE) ? '' : $slice;
    }

    /**
     * Convert strings like "64M" or "30" to int values
     * @param mixed $size
     * @return int
     */
    private function normalize_int($size)
    {
        if (is_string($size)) {
            switch (substr($size, -1)) {
                case 'M': case 'm': return $size * 1048576;
                case 'K': case 'k': return $size * 1024;
                case 'G': case 'g': return $size * 1073741824;
            }
        }

        return (int) $size;
    }
}

// minify JS files
$jsContent = "";
$jsFiles = glob(SRC_JS);
foreach($jsFiles as $file){
	$jsContent .= file_get_contents($file);
}
$jsContent = \JSMinPlus :: minify($jsContent);
file_put_contents(DST_JS, $jsContent);
// minify CSS files	// MERGE CSS FILES
$cssContent = "";
$cssFiles = glob(SRC_CSS);
foreach($cssFiles as $file){
	$cssContent .= file_get_contents($file);
}
$minifier = new \CSSmin();
$cssContent = $minifier -> run($cssContent);
file_put_contents(DST_CSS, $cssContent);
print "minify completed";

