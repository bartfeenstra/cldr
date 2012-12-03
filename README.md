CLDR
====

# Introduction
BartFeenstra/cldr is a PHP library to parse decimals, currency amounts,
percentages, and even integers using [Unicode Common Locale Data Repository
number patterns](http://cldr.unicode.org/translation/number-patterns).

# Usage
It offers four classes (`CurrencyFormatter`, `DecimalFormatter`,
`IntegerFormatter`, and `PercentageFormatter`) which accept a CLDR pattern and
optional replacements for replaceable special symbols, and can be reused to
format different numbers.

# Requirements
* PHP 5.3.x or higher
* PHPUnit 3.7.* (for running tests only)

# Integrates with
* [Composer](http://getcomposer.org) (as
[bartfeenstra/cldr](https://packagist.org/packages/bartfeenstra/cldr))