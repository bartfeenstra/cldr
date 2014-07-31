<?php

/**
 * @file
 * Contains class \BartFeenstra\CLDR\CurrencyFormatter.
 */

namespace BartFeenstra\CLDR;

/**
 * Formats a currency according CLDR number pattern guidelines.
 */
class CurrencyFormatter extends DecimalFormatter
{

    /**
     * The currency's symbol.
     *
     * @var string
     */
    const SYMBOL_SPECIAL_CURRENCY = '¤';

    /**
     * Overrides parent::replacePlaceholders()
     */
    function replacePlaceholders(array $symbols, array $replacements = array())
    {
        parent::replacePlaceholders($symbols, array(self::SYMBOL_SPECIAL_CURRENCY));
    }

    /**
     * Overrides parent::format().
     *
     * @param float|string $number
     * @param string $currencySign
     *   An ISO 4217 code or currency sign.
     *
     * @return string
     *   The formatted number.
     */
    public function format($number, $currencySign = NULL)
    {
        $this->symbolReplacements[self::SYMBOL_SPECIAL_CURRENCY] = $currencySign;

        return parent::format($number);
    }
}
