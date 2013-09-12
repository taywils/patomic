<?php
/**
 * Exception handler for Patomic
 *
 * @see http://php.net/manual/en/language.exceptions.extending.php
 */
class PatomicException extends Exception
{
        public function __construct($message, $code = 0, Exception $previous = null) {
                parent::__construct($message, $code, $previous);
        }

        public function __toString() {
                return __CLASS__ . ": [{$this->code}]: {$this->message}" . PHP_EOL;
        }
}
