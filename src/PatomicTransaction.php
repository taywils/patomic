<?php

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * PHP object representation of a Datomic transaction
 *
 * @see http://docs.datomic.com/transactions.html
 */
class PatomicTransaction 
{
    private $body;
    private static $addKeyword      = "add";
    private static $retractKeyword  = "retract";

    use TraitEdn;

    /**
     * Creates a new Transaction
     */
    public function __construct() {
        $this->body = $this->_vector(array());
    }

    /**
     * Inserts an entity to the current body of the Transaction
     * @param object $key
     * @param object $elem
     * @return $this
     */
    public function append($elem, $key = null) {
        if(is_null($key)) {
            $this->body->data[] = $elem;
        } else {
            $this->body->data[$key] = $elem;
        }

        return $this;
    }

    public function add($entityName, $attributeName, $value, $tempIdNum = null) {
        return $this->addOrRetract($entityName, $attributeName, $value, $tempIdNum = null, self::$addKeyword);
    }

    public function retract($entityName, $attributeName, $value, $tempIdNum = null) {
        return $this->addOrRetract($entityName, $attributeName, $value, $tempIdNum = null, self::$retractKeyword);
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
                case "PatomicEntity":
                    echo PHP_EOL . $elem->prettyPrint();
                    break;

                case 'igorw\edn\Vector':
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
                case "PatomicEntity":
                    $out .= $elem;
                    break;

                case 'igorw\edn\Vector':
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
            throw new PatomicException("entityName must be a string");
        }

        if(is_null($attributeName) || !is_string($attributeName)) {
            throw new PatomicException("attributeName must be a string");
        }

        if(is_null($value)) {
            throw new PatomicException("value cannot be null");
        }

        if(!is_null($tempIdNum) && !is_int($tempIdNum)) {
            throw new PatomicException("tempIdNum must be an int");
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
