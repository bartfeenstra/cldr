<?php

/**
 * @file
 * Contains class \BartFeenstra\Tests\CLDR\DecimalFormatterTest.
 */

namespace BartFeenstra\Tests\CLDR;

use BartFeenstra\CLDR\DecimalFormatter;

require_once __DIR__ . '/../../../../vendor/autoload.php';

/**
 * Tests \BartFeenstra\CLDR\DecimalFormatter
 */
class DecimalFormatterTest extends \PHPUnit_Framework_TestCase {

  /**
   * Test cloning.
   */
  function testClone() {
    $formatter = new TestDecimalFormatter('#,##0.00;#,##0.00-', array(
      DecimalFormatter::SYMBOL_SPECIAL_GROUPING_SEPARATOR => '.',
    ));
    $formatter_clone = clone $formatter;
    $symbols = $formatter->get('symbols');
    $symbols[DecimalFormatter::POSITIVE][DecimalFormatter::MAJOR][0]->symbol = 'AAA';
    $symbols_clone = $formatter_clone->get('symbols');
    $this->assertNotSame($symbols[DecimalFormatter::POSITIVE][DecimalFormatter::MAJOR][0]->symbol, $symbols_clone[DecimalFormatter::POSITIVE][DecimalFormatter::MAJOR][0]->symbol, 'When a DecimalFormatter is cloned, so are its NumberPatternSymbol elements.');
  }

  /**
   * @dataProvider validPattern
   */
  function testValidPatternValidation($pattern) {
    new DecimalFormatter($pattern);
  }

  public function validPattern() {
    return array(
      array('foo.00;bar.00'),
    );
  }

  /**
   * @dataProvider invalidPattern
   * @expectedException \Exception
   */
  function testInvalidPatternValidation($pattern) {
    new DecimalFormatter($pattern);
  }

  public function invalidPattern() {
    return array(
      // An empty pattern.
      array(''),
      // No decimal separator.
      array('foo'),
      array('foo:bar'),
      // Empty negative pattern.
      array('foo.00;'),
      // Empty positive pattern.
      array(';bar.00'),
    );
  }

  /**
   * Test amount formatting.
   *
   * @dataProvider formatPattern
   *
   * @depends testValidPatternValidation
   * @depends testInvalidPatternValidation
   */
  function testFormat($numbers, $formatter, $results_expected) {
    foreach ($numbers as $i => $number) {
      $result_expected = $results_expected[$i];
      $result = $formatter->format($number);
      $this->assertSame($result, $result_expected, 'BartFeenstra\CLDR\DecimalFormatter::format() formats amount ' . $number . ' as ' . $result_expected . ' using pattern ' . $formatter->pattern . ' (result was ' . $result . ').');
    }
  }

  public function formatPattern() {
    return array(
      // Test inconsistent group sizes and a custom negative pattern.
      array(
        array(123456789, -12345678.9, 1234567.89, -123456.789),
        new DecimalFormatter('#,##0.00;#,##0.00-', array(
          DecimalFormatter::SYMBOL_SPECIAL_DECIMAL_SEPARATOR => ',',
          DecimalFormatter::SYMBOL_SPECIAL_GROUPING_SEPARATOR => '.',
        )),
        array(
          '123.456.789,00',
          '12.345.678,90-',
          '1.234.567,89',
          '123.456,789-',
        ),
      ),
      // Test without grouping separators, a default negative pattern, no
      // decimals, and a pattern that is shorter than the numbers.
      array(
        array(123456789, -12345678.9, 1234567.89, -123456.789),
        'formatter' => new DecimalFormatter('#0.', array(
          DecimalFormatter::SYMBOL_SPECIAL_DECIMAL_SEPARATOR => ',',
          DecimalFormatter::SYMBOL_SPECIAL_GROUPING_SEPARATOR => '',
        )),
        'results' => array(
          '123456789,',
          '-12345678,',
          '1234567,',
          '-123456,',
        ),
      ),
      // Test identical decimal and grouping separators, identical positive
      // and negative patterns, and redundant hashes and grouping separators.
      array(
        array(123456789, -12345678.9, 1234567.89, -123456.789),
        'formatter' => new DecimalFormatter('###,###,###,##0.00;###,###,###,##0.00', array(
          DecimalFormatter::SYMBOL_SPECIAL_DECIMAL_SEPARATOR => '.',
          DecimalFormatter::SYMBOL_SPECIAL_GROUPING_SEPARATOR => '.',
        )),
        'results' => array(
          '123.456.789.00',
          '12.345.678.90',
          '1.234.567.89',
          '123.456.789',
        ),
      ),
      // Test some unusual character combinations and positions, and an
      // empty decimal separator.
      array(
        array(123456789, -12345678.9, 1234567.89, -123456.789),
        'formatter' => new DecimalFormatter("####000/@##0.<span style=\"text-transform: uppercase';'\">00</span>--;-####000/@##0.<span style=\"text-transform: uppercase';'\">00</span>--", array(
          DecimalFormatter::SYMBOL_SPECIAL_DECIMAL_SEPARATOR => '',
          DecimalFormatter::SYMBOL_SPECIAL_GROUPING_SEPARATOR => '.',
        )),
        'results' => array(
          '123456/@789<span style="text-transform: uppercase;">00</span>--',
          '-12345/@678<span style="text-transform: uppercase;">90</span>--',
          '1234/@567<span style="text-transform: uppercase;">89</span>--',
          '-123/@456<span style="text-transform: uppercase;">789</span>--',
        ),
      ),
      // Test character escaping.
      array(
        array(123456789, -12345678.9, 1234567.89, -123456.789),
        'formatter' => new DecimalFormatter("##'#'.00", array(
          DecimalFormatter::SYMBOL_SPECIAL_DECIMAL_SEPARATOR => ',',
          DecimalFormatter::SYMBOL_SPECIAL_GROUPING_SEPARATOR => '.',
        )),
        'results' => array(
          '123456789#,00',
          '-12345678#,90',
          '1234567#,89',
          '-123456#,789',
        ),
      ),
    );
  }
}
