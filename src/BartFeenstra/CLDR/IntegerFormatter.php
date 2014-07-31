<?php

/**
 * @file
 * Contains class \BartFeenstra\CLDR\IntegerFormatter.
 */

namespace BartFeenstra\CLDR;

/**
 * Formats an integer according CLDR number pattern guidelines.
 */
class IntegerFormatter
{

    /**
     * Indicates a negative pattern.
     *
     * @var integer
     */
    const NEGATIVE = 1;

    /**
     * PCRE meta characters.
     *
     * @var string
     */
    const PCRE_META_CHARACTERS = '\^$.[]|()?*+{}';

    /**
     * Indicates a positive pattern.
     *
     * @var integer
     */
    const POSITIVE = 0;

    /**
     * A digit.
     *
     * @var string
     */
    const SYMBOL_DIGIT = '0';

    /**
     * An optional digit.
     *
     * @var string
     */
    const SYMBOL_DIGIT_OPTIONAL = '#';

    /**
     * An escape character.
     *
     * @var string
     */
    const SYMBOL_ESCAPE = "'";

    /**
     * The pattern separator.
     *
     * @var string
     */
    const SYMBOL_PATTERN_SEPARATOR = ';';

    /**
     * The grouping separator's symbol.
     *
     * @var string
     */
    const SYMBOL_SPECIAL_GROUPING_SEPARATOR = ',';

    /**
     * Infinity's symbol.
     *
     * @var string
     */
    const SYMBOL_SPECIAL_INFINITY = '∞';

    /**
     * The minus sign's symbol.
     *
     * @var string
     */
    const SYMBOL_SPECIAL_MINUS = '-';

    /**
     * "not a number"'s symbol.
     *
     * @var string
     */
    const SYMBOL_SPECIAL_NAN = 'NaN';

    /**
     * The plus sign's symbol.
     *
     * @var string
     */
    const SYMBOL_SPECIAL_PLUS = '+';

    /**
     * The original pattern.
     *
     * This property is read-only.
     *
     * @var string
     */
    public $pattern = NULL;

    /**
     * This pattern's symbols.
     *
     * @var array
     */
    protected $symbols = NULL;

    /**
     * Replacements for occurrences of the self::SYMBOL_SPECIAL_* constants in
     * $this->pattern.
     *
     * @var array
     *   Keys are any of the self::SYMBOL_SPECIAL_* constants, values are those
     *   constants' replacements.
     */
    public $symbolReplacements = array();

    /**
     * Implements __construct().
     *
     * @throws \InvalidArgumentException
     *
     * @param string $pattern
     *   A Unicode CLDR number pattern, without short number support.
     *   See http://cldr.unicode.org/translation/number-patterns.
     * @param array $symbolReplacements
     *   Keys are one of the self::SYMBOL_SPECIAL_* constants, values are their
     *   replacements.
     */
    function __construct($pattern, array $symbolReplacements = array())
    {
        $this->pattern = $pattern;
        $this->symbolReplacements = $symbolReplacements;
        $this->symbols = $this->patternSymbolsSplit($this->patternSymbols($pattern), self::SYMBOL_PATTERN_SEPARATOR, TRUE);
        // If there is no negative pattern, add a default.
        if ($this->symbols[self::NEGATIVE] === FALSE) {
            $pattern .= ';-' . $pattern;
            $this->symbols = $this->patternSymbolsSplit($this->patternSymbols($pattern), self::SYMBOL_PATTERN_SEPARATOR, TRUE);
        }
        foreach ($this->symbols as $signSymbols) {
            if (empty($signSymbols)) {
                throw new \InvalidArgumentException('Empty number pattern.');
            }
        }
    }

    /**
     * Implements __clone().
     */
    function __clone()
    {
        $this->symbols = $this->cloneNumberPatternSymbols($this->symbols);
    }

    /**
     * Converts a number pattern to an array of NumberPatternSymbol objects.
     *
     * @throws \RuntimeException
     *
     * @param string $pattern
     *
     * @return array
     */
    function patternSymbols($pattern)
    {
        // Convert the pattern to NumberPatternSymbol objects.
        $symbols = array();
        foreach ($this->splitString($pattern) as $position => $symbol) {
            $symbols[] = new NumberPatternSymbol($symbol, $position);
        }

        // Loop through the NumberPatternSymbol objects and mark escaped symbols.
        foreach ($symbols as $i => $symbol) {
            // Check if the previous character is an unused escape symbol for this
            // symbol.
            if (isset($symbols[$i - 1]) && $symbols[$i - 1]->symbol == self::SYMBOL_ESCAPE && !$symbols[$i - 1]->escaped && !$symbols[$i - 1]->escapesOtherSymbol
                // Check if the next character is an escape symbol for this symbol.
                && isset($symbols[$i + 1]) && $symbols[$i + 1]->symbol == self::SYMBOL_ESCAPE && !$symbols[$i + 1]->escaped && !$symbols[$i + 1]->escapesOtherSymbol
            ) {
                $symbol->escaped = TRUE;
                $symbols[$i - 1]->escapesOtherSymbol = TRUE;
                $symbols[$i + 1]->escapesOtherSymbol = TRUE;
            }
        }

        // Find illegal escape symbols, such as escape symbols that do not escape
        // other symbols and are not escaped themselves.
        foreach ($symbols as $symbol) {
            if ($symbol->symbol == self::SYMBOL_ESCAPE && !$symbol->escaped && !$symbol->escapesOtherSymbol) {
                throw new \RunTimeException("Invalid escape symbol (') in pattern " . $pattern . "at position $symbol->position.");
            }
        }

        // Remove escape symbols from the array, because we have transferred their
        // meaning to other symbols' NumberPatternSymbol objects.
        foreach ($symbols as $i => $symbol) {
            if ($symbol->escapesOtherSymbol) {
                unset($symbols[$i]);
            }
        }

        // Reset the array keys so they make sense again.
        return array_values($symbols);
    }

    /**
     * Splits a pattern into two fragments.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @param array $symbols
     *   An array of NumberPatternSymbol objects.
     * @param string $separator
     * @param boolean $optionalRightFragment
     *   Whether the symbols on the right side of the separator, and, because of
     *   that, the separator itself are optional.
     *
     * @return array
     *   An array of NumberPatternSymbol objects.
     */
    protected function patternSymbolsSplit(array $symbols, $separator, $optionalRightFragment = FALSE)
    {
        $separatorPosition = NULL;
        foreach ($symbols as $position => $symbol) {
            if (!($symbol instanceof NumberPatternSymbol)) {
                throw new \InvalidArgumentException();
            }
            if ($symbol->symbol === $separator && !$symbol->escaped) {
                if (is_null($separatorPosition)) {
                    $separatorPosition = $position;
                } else {
                    throw new \RunTimeException("Illegal separator ($separator) at position $symbol->position.");
                }
            }
        }

        if (!$separatorPosition) {
            if ($optionalRightFragment) {
                return array($symbols, FALSE);
            }
            throw new \RunTimeException("Missing separator ($separator).");
        } else {
            return array(
                array_slice($symbols, 0, $separatorPosition),
                array_slice($symbols, $separatorPosition + 1),
            );
        }
    }

    /**
     * Splits a string in a unicode-safe way.
     *
     * @see str_split()
     *
     * @param string $string
     *   The input string.
     * @param integer $splitLength
     *   Maximum length of the chunk.
     *
     * @return array|false
     */
    static function splitString($string, $splitLength = 1)
    {
        if ($splitLength < 1) {
            return FALSE;
        }
        preg_match_all('/.{1,' . $splitLength . '}/u', $string, $matches);
        return $matches[0];
    }

    /**
     * Reverses a string in a unicode-safe way.
     *
     * @see strrev()
     *
     * @param string
     *
     * @return string
     */
    static function strrev($string)
    {
        return implode(array_reverse(self::splitString($string)));
    }

    /**
     * Gets a replacement symbol for a placeholder.
     *
     * @param string $symbol
     *   One of the self::SYMBOL_SPECIAL_* constants.
     *
     * @return string
     */
    function getReplacement($symbol)
    {
        return isset($this->symbolReplacements[$symbol]) ? $this->symbolReplacements[$symbol] : $symbol;
    }

    /**
     * Checks if a symbol is a special.
     *
     * @param NumberPatternSymbol $symbol
     * @param array $is
     *   An array of self::SYMBOL_* constants, one of which the symbol should
     *   match.
     *
     * @return boolean
     */
    function symbolIsSpecial(NumberPatternSymbol $symbol, array $is)
    {
        return !$symbol->escaped && in_array($symbol->symbol, $is, TRUE);
    }

    /**
     * Replaces placeholders.
     *
     * @param array $symbols
     *   An array of NumberPatternSymbol objects which are altered.
     * @param array $placeholders
     *   Characters that should be matched by a NumberPatternSymbol object.
     */
    function replacePlaceholders(array $symbols, array $placeholders = array())
    {
        $placeholders = array_merge(array(
            self::SYMBOL_SPECIAL_GROUPING_SEPARATOR,
            self::SYMBOL_SPECIAL_INFINITY,
            self::SYMBOL_SPECIAL_MINUS,
            self::SYMBOL_SPECIAL_NAN,
            self::SYMBOL_SPECIAL_PLUS,
        ), $placeholders);
        $replacements = array();
        foreach (array_unique($placeholders) as $placeholder) {
            $replacements[$placeholder] = $this->getReplacement($placeholder);
        }
        foreach ($symbols as $symbol) {
            foreach ($replacements as $placeholder => $replacement) {
                if (!$symbol->replacement && $symbol->symbol == $placeholder) {
                    $symbol->replacement = $replacement;
                }
            }
        }
    }

    /**
     * Formats a number.
     *
     * @throws \InvalidArgumentException
     *
     * @param integer|string $number
     *
     * @return string
     */
    public function format($number)
    {
        if ((int)$number != $number) {
            throw new \InvalidArgumentException('Number has no valid integer value.');
        }
        $sign = (int)($number < 0);
        $number = abs($number);
        $digits = str_split($number);
        $symbols = $this->cloneNumberPatternSymbols($this->symbols[$sign]);
        $this->process($symbols[$sign], $digits);
        $this->replacePlaceholders($symbols[$sign]);
        $output = '';
        foreach ($symbols[$sign] as $symbol) {
            $output .= !is_null($symbol->replacement) ? $symbol->replacement : $symbol->symbol;
        }

        return $output;
    }

    /**
     * Process the pattern's symbols using a number.
     *
     * @param array $symbols
     *   An array of NumberPatternSymbol objects that is altered.
     * @param array $digits
     *   The number's digits to use for $symbols.
     *
     * @return array
     *   $symbols, but optionally some symbols removed or added.
     */
    protected function process(array &$symbols, array $digits)
    {
        // Reverse all data, because we want to process numbers away from the
        // decimal separator.
        $symbols = array_reverse($symbols);
        $digits = array_reverse($digits);

        $lastDigitPlaceholderIndex = 0;
        $lastOptionalDigitPlaceholderIndex = 0;
        foreach ($symbols as $i => $symbol) {
            if ($this->symbolIsSpecial($symbol, array(self::SYMBOL_DIGIT, self::SYMBOL_DIGIT_OPTIONAL))) {
                $lastDigitPlaceholderIndex = $i;
            }
            if ($this->symbolIsSpecial($symbol, array(self::SYMBOL_DIGIT_OPTIONAL))) {
                $lastOptionalDigitPlaceholderIndex = $i;
            }
        }
        $lastGroupingSize = 0;
        $currentGroupingSize = 0;
        $lastDigitIndex = 0;
        foreach ($symbols as $i => $symbol) {
            // Replace placeholders, but only if we still have digits left to replace
            // them with.
            if ($digits && $this->symbolIsSpecial($symbol, array(self::SYMBOL_DIGIT, self::SYMBOL_DIGIT_OPTIONAL))) {
                // This is the last placeholder, so replace it with all remaining digits.
                if ($i == $lastDigitPlaceholderIndex) {
                    // If the pattern uses groupings, group remaining digits before
                    // adding them.
                    if ($lastGroupingSize) {
                        // Make sure the current grouping is full.
                        while ($currentGroupingSize < $lastGroupingSize) {
                            $symbol->replacement = array_shift($digits) . $symbol->replacement;
                            $currentGroupingSize++;
                        }
                        // If there are still digits left, add them as new groupings.
                        if ($digits) {
                            $chunks = $this->splitString(implode($digits), $lastGroupingSize);
                            foreach (array_reverse($chunks) as $chunk) {
                                array_splice($symbols, $i + 1, 0, array(new NumberPatternSymbol(strrev($chunk))));
                                array_splice($symbols, $i + 1, 0, array(new NumberPatternSymbol(self::SYMBOL_SPECIAL_GROUPING_SEPARATOR)));
                            }
                            // We processed all digits, so clear the array.
                            $digits = array();
                        }
                    }
                    // The pattern does not use groupings, so add all digits without
                    // formatting.
                    else {
                        $symbol->replacement = strrev(implode($digits));
                    }
                } // This is not the last placeholder, so replace it with a single digit.
                else {
                    $symbol->replacement = array_shift($digits);
                    $currentGroupingSize++;
                }
                $lastDigitIndex = $i;
            } // Keep track of the last grouping size.
            elseif ($this->symbolIsSpecial($symbol, array(self::SYMBOL_SPECIAL_GROUPING_SEPARATOR))) {
                $lastGroupingSize = $currentGroupingSize;
                $currentGroupingSize = 0;
            }
        }

        // Removes the last optional digit placeholder and everything between that
        // symbol and the last inserted digit.
        while ($lastOptionalDigitPlaceholderIndex > $lastDigitIndex) {
            unset($symbols[$lastOptionalDigitPlaceholderIndex]);
            $lastOptionalDigitPlaceholderIndex--;
        }

        // Put the symbols back in the order we received them in.
        $symbols = array_reverse($symbols);
    }

    /**
     * Clones this formatter's NumberPatternSymbol objects.
     *
     * @return array
     *  An array identical to $this->symbols.
     */
    function cloneNumberPatternSymbols()
    {
        $clone = array(
            self::POSITIVE => array(),
            self::NEGATIVE => array(),
        );
        foreach ($this->symbols as $sign => $signSymbols) {
            foreach ($signSymbols as $symbol) {
                $clone[$sign][] = clone $symbol;
            }
        }

        return $clone;
    }
}
