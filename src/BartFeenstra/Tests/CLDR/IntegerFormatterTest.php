<?php

/**
 * @file
 * Contains class \BartFeenstra\Tests\CLDR\IntegerFormatterTest.
 */

namespace BartFeenstra\Tests\CLDR;

use BartFeenstra\CLDR\IntegerFormatter;

require_once __DIR__ . '/../../../../vendor/autoload.php';

/**
 * Tests \BartFeenstra\CLDR\IntegerFormatter
 */
class IntegerFormatterTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test cloning.
     */
    function testClone()
    {
        $formatter = new TestIntegerFormatter('#,##0.00;#,##0.00-', array(
            IntegerFormatter::SYMBOL_SPECIAL_GROUPING_SEPARATOR => '.',
        ));
        $formatterClone = clone $formatter;
        $symbols = $formatter->get('symbols');
        $symbols[IntegerFormatter::POSITIVE][0]->symbol = 'AAA';
        $clonedSymbols = $formatterClone->get('symbols');
        $this->assertNotSame($symbols[IntegerFormatter::POSITIVE][0]->symbol, $clonedSymbols[IntegerFormatter::POSITIVE][0]->symbol, 'When an IntegerFormatter is cloned, so are its NumberPatternSymbol elements.');
    }

    /**
     * Test amount formatting.
     */
    function testFormat()
    {
        $numbers = array(123456789123456789, -123456789123456789);
        $patterns = array(
            // Test using the decimal separator as a non-special symbol and a custom
            // negative pattern.
            array(
                'formatter' => new IntegerFormatter('#,##0.00;#,##0.00-', array(
                        IntegerFormatter::SYMBOL_SPECIAL_GROUPING_SEPARATOR => '.',
                    )),
                'results' => array(
                    '123.45678.91234.567.89',
                    '123.45678.91234.567.89-',
                ),
            ),
            // Test without grouping separators and a default negative pattern.
            array(
                'formatter' => new IntegerFormatter('#0.00', array(
                        IntegerFormatter::SYMBOL_SPECIAL_GROUPING_SEPARATOR => '',
                    )),
                'results' => array(
                    '1234567891234567.89',
                    '-1234567891234567.89',
                ),
            ),
            // Test redundant hashes and escaped hashes..
            array(
                'formatter' => new IntegerFormatter("###,###,###,###,###,###,#'#'##", array(
                        IntegerFormatter::SYMBOL_SPECIAL_GROUPING_SEPARATOR => '#',
                    )),
                'results' => array(
                    '123#456#789#123#456#7#89',
                    '-123#456#789#123#456#7#89',
                ),
            ),
        );
        foreach ($patterns as $patternInfo) {
            foreach ($numbers as $i => $number) {
                $formatter = $patternInfo['formatter'];
                $expectedResult = $patternInfo['results'][$i];
                $result = $formatter->format($number);
                $this->assertSame($result, $expectedResult, 'BartFeenstra\CLDR\IntegerFormatter::format() formats amount ' . $number . ' as ' . $expectedResult . ' using pattern ' . $formatter->pattern . ' (result was ' . $result . ').');
            }
        }
    }
}
