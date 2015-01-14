<?php
/**
 * @copyright wiseape GmbH
 * @author Ruben Rögels <ruben.roegels@wiseape.de>
 * @license MIT
 */

namespace bbit\contao\util\dao\exception;

class FieldAccessDeniedException extends \Exception {
    public function __construct($message, $code = null, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
