<?php

/**
 * @file
 * Contains class \BartFeenstra\CLDR\CurrencyFormatter.
 */

namespace BartFeenstra\CLDR;

/**
 * Formats a currency according CLDR number pattern guidelines.
 */
class CurrencyFormatter extends DecimalFormatter {

  function __construct($pattern, array $symbol_replacements = array()) {
    parent::__construct($pattern, $symbol_replacements);
    foreach ($this->symbols as $sign_symbols) {
      $fraction_symbols = $sign_symbols[1];
      if (count($fraction_symbols) != 2 || $fraction_symbols[0]->symbol != self::SYMBOL_DIGIT || $fraction_symbols[1]->symbol != self::SYMBOL_DIGIT) {
        throw new \InvalidArgumentException('Currency formats should have two zeros in the fractional position.');
      }
    };
  }


  /**
   * The currency's symbol.
   *
   * @var string
   */
  const SYMBOL_SPECIAL_CURRENCY = '¤';

  /**
   * Overrides parent::replacePlaceholders()
   */
  function replacePlaceholders(array $symbols, array $replacements = array()) {
    parent::replacePlaceholders($symbols, array(self::SYMBOL_SPECIAL_CURRENCY));
  }

  /**
   * Overrides parent::format().
   *
   * @param float|string $number
   * @param string $currency_sign
   *   An ISO 4217 code or currency sign.
   */
  public function format($number, $currency_sign = NULL, DecimalFormatterParameters $parameters = NULL) {
    $this->symbol_replacements[self::SYMBOL_SPECIAL_CURRENCY] = $currency_sign;

    return parent::format($number, $parameters);
  }
}
