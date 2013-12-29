<?php

require_once "../vendor/autoload.php";

require_once "PatomicException.php";
require_once "TraitEdn.php";
require_once "PatomicSchema.php";

/**
 * PHP object representation of a Datomic transaction
 *
 * @see http://docs.datomic.com/transactions.html
 */
class PatomicTransaction 
{
    private $body;

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

    /**
     * Add a single new data(a.k.a fact) to a transaction for an existing entity within your schema
     *
     * <b>If you wish to add multiple facts at once create an entity instead</b>
     *
     * The entityName must be a valid name of an entity found within your schema
     *
     * @see http://docs.datomic.com/tutorial.html
     *
     * @param string $entityName
     * @param string $attributeName
     * @param $value
     * @param int $tempIdNum
     * @return $this
     * @throws PatomicException
     */
    public function add($entityName, $attributeName, $value, $tempIdNum = null) {
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

        $idTagged = $this->_tagged($idTag, $dbUser);

        $vec->data[] = $this->_keyword("db/add");
        $vec->data[] = $idTagged;
        $vec->data[] = $this->_keyword($entityName . "/" . $attributeName);
        $vec->data[] = $value;

        $this->body->data[] = $vec;

        return $this;
    }

    public function modify() {

    }

    public function  retract() {

    }

    /**
     * Displays the transaction as according to the Datomic Documentation
     */
    public function prettyPrint() {
        echo "[" . PHP_EOL . PHP_EOL;

        foreach($this->body->data as $elem) {
            switch(get_class($elem)) {
                case "PatomicSchema":
                    echo $elem->prettyPrint();
                    break;

                case 'igorw\edn\Vector':
                    echo $this->_encode($elem);
                    break;

                default:
                    echo $elem;
            }

            echo PHP_EOL;
        }

        echo PHP_EOL . "]" . PHP_EOL;
    }
}

$test2 = new PatomicSchema();

$test2->ident("taywils", "script", "name")
    ->doc("This is the doc for my datom")
    ->valueType("string")
    ->cardinality("one")
    ->unique("value")
    ->isComponent(false)
    ->noHistory(true);

$trans = new PatomicTransaction();
$trans->append($test2)
    ->add("script", "name", "Hamlet");

$trans->prettyPrint();
//echo $test2;