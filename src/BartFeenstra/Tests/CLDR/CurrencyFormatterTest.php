<?php

/**
 * @file
 * Contains class \BartFeenstra\Tests\CLDR\CurrencyFormatterTest.
 */

namespace BartFeenstra\Tests\CLDR;

use BartFeenstra\CLDR\CurrencyFormatter;

require_once __DIR__ . '/../../../../vendor/autoload.php';

/**
 * Tests \BartFeenstra\CLDR\CurrencyFormatter
 */
class CurrencyFormatterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test amount formatting.
     */
    function testFormat()
    {
        $formatter = new CurrencyFormatter('¤0.00');
        $currencySign = '€';
        $number = 123456.789;
        $expectedResult = $currencySign . '123456.789';
        $result = $formatter->format($number, $currencySign);
        $this->assertSame($result, $expectedResult, 'BartFeenstra\CLDR\CurrencyFormatter::format() formats amount ' . $number . ' as ' . $expectedResult . ' using pattern ' . $formatter->pattern . ' (result was ' . $result . ').');
    }
}
