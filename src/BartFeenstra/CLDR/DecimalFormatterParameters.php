<?php
/**
 * @file
 * Contains class \BartFeenstra\CLDR\DecimalFormatterParameters.
 */

namespace BartFeenstra\CLDR;


class DecimalFormatterParameters {

  private $maximumIntegerDigits = NULL;

  private $minimumIntegerDigits = NULL;

  private $maximumFractionDigits = NULL;

  private $minimumFractionDigits = NULL;

  /**
   * Create a mew decimal formatter parameters sets.
   *
   * @param array $parameters
   *   The value of the formatting parameters, indexed by name. The name of the parameters is the same as the accessor
   *   methods available in this class.
   */
  public function __construct(array $parameters) {
    $this->maximumIntegerDigits = isset($parameters['maximumIntegerDigits']) ? (integer) $parameters['maximumIntegerDigits'] : NULL;
    $this->minimumIntegerDigits = isset($parameters['minimumIntegerDigits']) ? (integer) $parameters['minimumIntegerDigits'] : NULL;
    $this->maximumFractionDigits = isset($parameters['maximumFractionDigits']) ? (integer) $parameters['maximumFractionDigits'] : NULL;
    $this->minimumFractionDigits = isset($parameters['minimumFractionDigits']) ? (integer) $parameters['minimumFractionDigits'] : NULL;
  }

  /**
   * Returns the maximum integer digits.
   *
   * @return int|null
   *   The maximum integer digits, or NULL if none is set.
   */
  public function maximumIntegerDigits() {
    return $this->maximumIntegerDigits;
  }

  /**
   * Returns the minimum integer digits.
   *
   * @return int|null
   *   The minimum integer digits, or NULL if none is set.
   */
  public function minimumIntegerDigits() {
    return $this->minimumIntegerDigits;
  }

  /**
   * Returns the maximum fraction digits.
   *
   * @return int|null
   *   The maximum fraction digits, or NULL if none is set.
   */
  public function maximumFractionDigits() {
     return $this->maximumFractionDigits;
  }

  /**
   * Returns the minimum fraction digits.
   *
   * @return int|null
   *   The minimum fraction digits, or NULL if none is set.
   */
  public function minimumFractionDigits() {
    return $this->minimumFractionDigits;
  }
}