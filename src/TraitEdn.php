<?php

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * TraitEdn provides a common interface to EDN functions
 * such that independent EDN classes/libraries can be mixed together
 * or an existing implementation swapped out
 */
trait TraitEdn
{
    public function _keyword($k) {
        return \igorw\edn\keyword($k);
    }

    public function _symbol($s) {
        return \igorw\edn\symbol($s);
    }

    public function _map($a = null) {
        $map = new \igorw\edn\Map();

        if(isset($a) && is_array($a)) {
            $map->init($a);
        }

        return $map;
    }

    public function _vector($a) {
        return new \igorw\edn\Vector($a);
    }

    public function _tag($t) {
        return new \igorw\edn\tag($t);
    }

    public function _tagged($t, $v) {
        return new \igorw\edn\Tagged($t, $v);
    }

    public function _encode($edn) {
        return \igorw\edn\encode($edn);
    }

    public function _parse($edn) {
        return \igorw\edn\parse($edn);
    }
}