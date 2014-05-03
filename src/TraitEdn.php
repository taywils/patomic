<?php

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * TraitEdn provides a common interface to EDN functions
 * such that independent EDN classes/libraries can be mixed together
 * or an existing implementation swapped out
 */
trait TraitEdn
{
    protected function _keyword($k) {
        return \igorw\edn\keyword($k);
    }

    protected function _symbol($s) {
        return \igorw\edn\symbol($s);
    }

    protected function _map($a = null) {
        $map = new \igorw\edn\Map();

        if(isset($a) && is_array($a)) {
            $map->init($a);
        }

        return $map;
    }

    protected function _vector($a) {
        return new \igorw\edn\Vector($a);
    }

    protected function _list($a) {
        return new \igorw\edn\LinkedList($a);
    }

    protected function _tag($t) {
        return new \igorw\edn\tag($t);
    }

    protected function _tagged($t, $v) {
        return new \igorw\edn\Tagged($t, $v);
    }

    protected function _encode($edn) {
        return \igorw\edn\encode($edn);
    }

    protected function _parse($edn) {
        return \igorw\edn\parse($edn);
    }
}