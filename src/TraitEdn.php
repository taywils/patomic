<?php

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * TraitEdn provides a common interface to EDN functions
 * such that independent EDN classes/libraries can be mixed together
 * or an existing implementation swapped out
 */
trait TraitEdn
{
    protected function _keyword($keyword) {
        return \igorw\edn\keyword($keyword);
    }

    protected function _symbol($symbol) {
        return \igorw\edn\symbol($symbol);
    }

    protected function _map($array = null) {
        $map = new \igorw\edn\Map();

        if(isset($array) && is_array($array)) {
            $map->init($array);
        }

        return $map;
    }

    protected function _vector($array) {
        return new \igorw\edn\Vector($array);
    }

    protected function _list($array) {
        return new \igorw\edn\LinkedList($array);
    }

    protected function _tag($tag) {
        return new \igorw\edn\tag($tag);
    }

    protected function _tagged($tag, $value) {
        return new \igorw\edn\Tagged($tag, $value);
    }

    protected function _encode($edn) {
        return \igorw\edn\encode($edn);
    }

    protected function _parse($edn) {
        return \igorw\edn\parse($edn);
    }
}