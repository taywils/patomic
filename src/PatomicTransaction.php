<?php

require_once __DIR__ . "/../vendor/autoload.php";

//TODO: Adding entity references @see http://docs.datomic.com/transactions.html

/**
 * PHP object representation of a Datomic transaction
 *
 * @see http://docs.datomic.com/transactions.html
 */
class PatomicTransaction 
{
    private $body;
    private static $KEYWORD_ADD      = "add";
    private static $KEYWORD_RETRACT  = "retract";
    private static $ENTITY_CLASSNAME = "PatomicEntity";
    private static $VECTOR_CLASSNAME = 'igorw\edn\Vector';

    use TraitEdn;

    /**
     * Creates a new Transaction
     */
    public function __construct() {
        $this->body = $this->_vector(array());
    }

    /**
     * Inserts an entity to the current body of the Transaction
     * @param object $elem
     * @param object $key
     * @return $this
     * @throws PatomicException
     */
    public function append($elem, $key = null) {
        if(!isset($elem) || !is_object($elem) || get_class($elem) != self::$ENTITY_CLASSNAME) {
            throw new PatomicException(__METHOD__ . " argument must be a valid ". self::$ENTITY_CLASSNAME ." object");
        }
        if(is_null($key)) {
            $this->body->data[] = $elem;
        } else {
            $this->body->data[$key] = $elem;
        }

        return $this;
    }

    public function add($entityName, $attributeName, $value = null, $tempIdNum = null) {
        return $this->addOrRetract($entityName, $attributeName, $value, $tempIdNum, self::$KEYWORD_ADD);
    }

    public function retract($entityName, $attributeName, $value = null, $tempIdNum = null) {
        return $this->addOrRetract($entityName, $attributeName, $value, $tempIdNum, self::$KEYWORD_RETRACT);
    }

    /**
     * Removes all existing data from the current transaction
     */
    public function clearData() {
        unset($this->body);
        $this->body = $this->_vector(array());
    }

    /**
     * Displays the transaction according to the style shown throughout the Datomic Documentation
     */
    public function prettyPrint() {
        echo "[" . PHP_EOL . PHP_EOL;

        foreach($this->body->data as $elem) {
            switch(get_class($elem)) {
                case self::$ENTITY_CLASSNAME:
                    echo PHP_EOL . $elem->prettyPrint();
                    break;

                case self::$VECTOR_CLASSNAME:
                    echo PHP_EOL . $this->_encode($elem);
                    break;

                default:
                    echo $elem;
            }

            echo PHP_EOL;
        }

        echo "]" . PHP_EOL;
    }

    /**
     * Essentially prettyPrint with excess newlines stripped
     *
     * @return string
     */
    public function __toString() {
        $out = "[";

        foreach($this->body->data as $elem) {
            switch(get_class($elem)) {
                case self::$ENTITY_CLASSNAME:
                    $out .= $elem;
                    break;

                case self::$VECTOR_CLASSNAME:
                    $out .= $this->_encode($elem);
                    break;

                default:
                    $out .= $elem;
            }
        }

        $out .= "]";

        return $out;
    }

    /**
     * Add or retract a single new data(a.k.a fact) to a transaction for an existing entity within your schema
     *
     * The entityName must be a valid name of an entity found within your schema
     *
     * According to the docs the only difference between adding and retracting is first keyword in the EDN vector
     *
     * @see http://docs.datomic.com/tutorial.html
     *
     * @param string $entityName
     * @param string $attributeName
     * @param $value
     * @param int $tempIdNum
     * @param string $methodKeyword Determines whether to add or retract
     * @return $this
     * @throws PatomicException
     */
    private function addOrRetract($entityName, $attributeName, $value, $tempIdNum = null, $methodKeyword) {
        if(is_null($entityName) || !is_string($entityName)) {
            throw new PatomicException(__METHOD__ . " entityName must be a string");
        }

        if(is_null($attributeName) || !is_string($attributeName)) {
            throw new PatomicException(__METHOD__ . " attributeName must be a string");
        }

        if(is_null($value)) {
            throw new PatomicException(__METHOD__ . " value argument cannot be null");
        }

        if(!is_null($tempIdNum) && !is_int($tempIdNum)) {
            throw new PatomicException(__METHOD__ . " tempId argument must be an integer");
        }

        $vec = $this->_vector(array());

        $idTag = $this->_tag("db/id");

        $dbUser = $this->_vector(array($this->_keyword("db.part/user")));
        if(!is_null($tempIdNum) && is_int($tempIdNum)) {
            $dbUser->data[] = $tempIdNum;
        }

        $idTagged = $this->_tagged($idTag, $dbUser);

        $vec->data[] = $this->_keyword("db/" . $methodKeyword);
        $vec->data[] = $idTagged;
        $vec->data[] = $this->_keyword($entityName . "/" . $attributeName);
        $vec->data[] = $value;

        $this->body->data[] = $vec;

        return $this;
    }
}
