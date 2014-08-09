<?php

/**
 * @file
 * Contains class \BartFeenstra\CLDR\IntegerFormatter.
 */

namespace BartFeenstra\CLDR;

/**
 * Formats a decimal according CLDR number pattern guidelines.
 */
class DecimalFormatter extends IntegerFormatter
{

    /**
     * Indicates a major pattern fragment.
     *
     * @var integer
     */
    const MAJOR = 0;

    /**
     * Indicates a minor pattern fragment.
     *
     * @var integer
     */
    const MINOR = 1;

    /**
     * The decimal separator's symbol.
     *
     * @var string
     */
    const SYMBOL_SPECIAL_DECIMAL_SEPARATOR = '.';

    /**
     * Overrides parent::__construct().
     */
    public function __construct($pattern, array $symbolReplacements = array())
    {
        $this->pattern = $pattern;
        $this->symbolReplacements = $symbolReplacements;
        $symbols = $this->patternSymbolsSplit($this->patternSymbols($pattern), self::SYMBOL_PATTERN_SEPARATOR, TRUE);
        // If there is no negative pattern, add a default.
        if ($symbols[self::NEGATIVE] === FALSE) {
            $pattern .= ';-' . $pattern;
            $symbols = $this->patternSymbolsSplit($this->patternSymbols($pattern), self::SYMBOL_PATTERN_SEPARATOR, TRUE);
        }
        foreach ($symbols as $signSymbols) {
            if (empty($signSymbols)) {
                throw new \InvalidArgumentException('Empty number pattern.');
            }
        }
        $this->symbols = array(
            $this->patternSymbolsSplit($symbols[self::POSITIVE], self::SYMBOL_SPECIAL_DECIMAL_SEPARATOR),
            $this->patternSymbolsSplit($symbols[self::NEGATIVE], self::SYMBOL_SPECIAL_DECIMAL_SEPARATOR),
        );
    }

    /**
     * Overrides parent::format().
     *
     * A decimal is formatted by splitting it into two integers: the major
     * and minor unit. They are formatted individually and then joined together.
     *
     * @throws \InvalidArgumentException
     *
     * @param float|string $number
     *
     * @return string
     *   The formatted number.
     */
    public function format($number)
    {
        if ((float)$number != $number) {
            throw new \InvalidArgumentException('Number has no valid float value.');
        }
        $sign = (int)($number < 0);

        // Split the number in major and minor units, and make sure there is a
        // minor unit at all.
        $number = explode('.', abs($number));
        $number += array(
            self::MINOR => '',
        );

        $digits = array(
            str_split($number[self::MAJOR]),
            strlen($number[self::MINOR]) ? str_split($number[self::MINOR]) : array(),
        );

        $symbols = $this->cloneNumberPatternSymbols($this->symbols[$sign]);
        $this->process($symbols[$sign][self::MAJOR], $digits[self::MAJOR]);
        // Integer formatting defaults from right to left, but minor units should
        // be formatted from left to right, so reverse all data and results.
        $minorSymbols = array_reverse($symbols[$sign][self::MINOR]);
        $this->process($minorSymbols, array_reverse($digits[self::MINOR]));
        foreach ($symbols[$sign][self::MINOR] as $symbol) {
            if (!is_null($symbol->replacement)) {
                $symbol->replacement = strrev($symbol->replacement);
            }
        }

        // Prepare the output string.
        $output = array(
            self::MAJOR => '',
            self::MINOR => '',
        );
        foreach ($symbols[$sign] as $fragment => $fragmentSymbols) {
            $this->replacePlaceholders($fragmentSymbols);
            foreach ($fragmentSymbols as $symbol) {
                $output[$fragment] .= !is_null($symbol->replacement) ? $symbol->replacement : $symbol->symbol;
            }
        }

        return $output[self::MAJOR] . $this->getReplacement(self::SYMBOL_SPECIAL_DECIMAL_SEPARATOR) . $output[self::MINOR];
    }

    /**
     * Clones this formatter's NumberPatternSymbol objects.
     *
     * @return array
     *  An array identical to $this->symbols.
     */
    protected function cloneNumberPatternSymbols()
    {
        $clone = array(
            self::POSITIVE => array(
                self::MAJOR => array(),
                self::MINOR => array(),
            ),
            self::NEGATIVE => array(
                self::MAJOR => array(),
                self::MINOR => array(),
            ),
        );
        foreach ($this->symbols as $sign => $signSymbols) {
            foreach ($signSymbols as $fragment => $fragmentSymbols) {
                foreach ($fragmentSymbols as $symbol) {
                    $clone[$sign][$fragment][] = clone $symbol;
                }
            }
        }

        return $clone;
    }
}
