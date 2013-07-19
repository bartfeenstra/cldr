<?php

/**
 * @file
 * Contains class \BartFeenstra\CLDR\IntegerFormatter.
 */

namespace BartFeenstra\CLDR;

/**
 * Formats a decimal according CLDR number pattern guidelines.
 */
class DecimalFormatter extends IntegerFormatter {

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

  private $defaultParameters;

  /**
   * Overrides parent::__construct().
   */
  function __construct($pattern, array $symbol_replacements = array()) {
    $this->pattern = $pattern;
    $this->symbol_replacements = $symbol_replacements;
    $symbols = $this->patternSymbolsSplit($this->patternSymbols($pattern), self::SYMBOL_PATTERN_SEPARATOR, TRUE);
    // If there is no negative pattern, add a default.
    if ($symbols[self::NEGATIVE] === FALSE) {
      $pattern .= ';-' . $pattern;
      $symbols = $this->patternSymbolsSplit($this->patternSymbols($pattern), self::SYMBOL_PATTERN_SEPARATOR, TRUE);
    }
    foreach ($symbols as $sign_symbols) {
      // All formats should have one 0 before the decimal point (for example, avoid #,###.##)
      $sign_symbols = array_filter($sign_symbols, function($symbol) {
        return in_array($symbol->symbol, array(
          self::SYMBOL_DIGIT
        ));
      });
      if (empty($sign_symbols)) {
        throw new \InvalidArgumentException('Empty number pattern.');
      }
    }
    $this->symbols = array(
      $this->patternSymbolsSplit($symbols[self::POSITIVE], self::SYMBOL_SPECIAL_DECIMAL_SEPARATOR, TRUE),
      $this->patternSymbolsSplit($symbols[self::NEGATIVE], self::SYMBOL_SPECIAL_DECIMAL_SEPARATOR, TRUE),
    );
    $this->defaultParameters = new DecimalFormatterParameters(array());
  }

  /**
   * Overrides parent::format().
   *
   * A decimal is formatted by splitting it into two integers: the major
   * and minor unit. They are formatted individually and then joined together.
   *
   * @param float|string $number
   */
  public function format($number, DecimalFormatterParameters $parameters = NULL) {
    if ((float) $number != $number) {
      throw new \InvalidArgumentException('Number has no valid float value.');
    }
    if ($parameters == NULL) {
      $parameters = $this->defaultParameters;
    }

    $sign = (int) ($number < 0);

    // Split the number in major and minor units, and make sure there is a
    // minor unit at all.
    $number = explode('.', abs($number));
    $number += array(
      self::MINOR => '',
    );

    if (!is_null($parameters->maximumIntegerDigits()) && strlen($number[self::MAJOR]) > $parameters->maximumIntegerDigits()) {
      $number[self::MAJOR] = substr($number[self::MAJOR], -$parameters->maximumIntegerDigits());
    }
    if (!is_null($parameters->maximumFractionDigits()) && strlen($number[self::MINOR]) > $parameters->maximumFractionDigits()) {
      $number[self::MINOR] = round(substr_replace($number[self::MINOR], '.', $parameters->maximumFractionDigits(), 0));
    }
    if (!is_null($parameters->minimumIntegerDigits())) {
      $number[self::MAJOR] = str_pad($number[self::MAJOR], $parameters->minimumIntegerDigits(), 0, STR_PAD_LEFT);
    }
    if (!is_null($parameters->minimumFractionDigits())) {
      $number[self::MINOR] = str_pad($number[self::MINOR], $parameters->minimumFractionDigits(), 0, STR_PAD_RIGHT);
    }

    $digits = array(
      str_split($number[self::MAJOR]),
      strlen($number[self::MINOR]) ? str_split($number[self::MINOR]) : array(),
    );

    $symbols = $this->cloneNumberPatternSymbols($this->symbols[$sign]);
    $this->process($symbols[$sign][self::MAJOR], $digits[self::MAJOR]);
    // Integer formatting defaults from right to left, but minor units should
    // be formatted from left to right, so reverse all data and results.
    $symbols_minor = array_reverse($symbols[$sign][self::MINOR]);
    $this->process($symbols_minor, array_reverse($digits[self::MINOR]));
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
    foreach ($symbols[$sign] as $fragment => $fragment_symbols) {
      $this->replacePlaceholders($fragment_symbols);
      $fractionDigits = 0;
      foreach ($fragment_symbols as $symbol) {
        $keep_symbol = TRUE;
        if ($fragment == self::MINOR && !is_null($parameters->maximumFractionDigits()) && $symbol->symbol == self::SYMBOL_DIGIT) {
          $fractionDigits++;
          if ($fractionDigits > $parameters->minimumFractionDigits()) {
            $keep_symbol = FALSE;
          }
        }
        $output[$fragment] .= !is_null($symbol->replacement) ? $symbol->replacement : ($keep_symbol ? $symbol->symbol : '');
      }
    }
    if ($output[self::MINOR] == '') {
      if  (strpos($this->pattern, self::SYMBOL_SPECIAL_DECIMAL_SEPARATOR) !== FALSE && $parameters->minimumFractionDigits() !== 0) {
        return $output[self::MAJOR] . $this->getReplacement(self::SYMBOL_SPECIAL_DECIMAL_SEPARATOR);
      }
      else {
        return $output[self::MAJOR];
      }
    }
    else {
      return $output[self::MAJOR] . $this->getReplacement(self::SYMBOL_SPECIAL_DECIMAL_SEPARATOR) . $output[self::MINOR];
    }
  }

  /**
   * Clones this formatter's NumberPatternSymbol objects.
   *
   * @return array
   *  An array identical to $this->symbols.
   */
  function cloneNumberPatternSymbols() {
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
    foreach ($this->symbols as $sign => $sign_symbols) {
      foreach ($sign_symbols as $fragment => $fragment_symbols) {
        foreach ($fragment_symbols as $symbol) {
          $clone[$sign][$fragment][] = clone $symbol;
        }
      }
    }

    return $clone;
  }
}
