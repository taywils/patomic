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
    private $loadedFromFile;

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
        $this->loadedFromFile = false;
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

        $this->clearIfLoadedFromFile();

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
        $printFormating = ($this->loadedFromFile) ? "" : "[" . PHP_EOL . PHP_EOL;
        echo $printFormating;

        foreach($this->body->data as $elem) {
            if(is_object($elem)) {
                switch (get_class($elem)) {
                    case self::$ENTITY_CLASSNAME:
                        echo PHP_EOL . $elem->prettyPrint();
                        break;

                    case self::$VECTOR_CLASSNAME:
                        echo PHP_EOL . $this->_encode($elem);
                        break;

                    default:
                        echo $elem;
                }
            } else {
                echo $elem;
            }

            echo PHP_EOL;
        }

        $printFormating = ($this->loadedFromFile) ? "" : "]" . PHP_EOL;
        echo $printFormating;
    }

    /**
     * Essentially prettyPrint with excess newlines stripped
     *
     * @return string
     */
    public function __toString() {
        $out = ($this->loadedFromFile) ? "" : "[";

        foreach($this->body->data as $elem) {
            if(is_object($elem)) {
                switch (get_class($elem)) {
                    case self::$ENTITY_CLASSNAME:
                        $out .= $elem;
                        break;

                    case self::$VECTOR_CLASSNAME:
                        $out .= $this->_encode($elem);
                        break;

                    default:
                        $out .= $elem;
                }
            } else {
                $out .= $elem;
            }
        }

        $out .= ($this->loadedFromFile) ? "" : "]";

        return $out;
    }

    /**
     * Loads a .edn file from disk and stores its contents as the current transaction.
     * This function will clear the current transaction body and replace it with the .edn file's contents
     * 
     * @param string $fileName
     * @throws PatomicException
     */
    public function loadFromFile($fileName) {
        if(!isset($fileName) || !is_string($fileName) || strlen(trim($fileName)) == 0) {
            throw new PatomicException(__METHOD__ . " \$fileName argument must be a non-empty string");
        }
        
        $ednFileInfo = new SplFileInfo($fileName);
        
        if("edn" != $ednFileInfo->getExtension()) {
            throw new PatomicException(__METHOD__ . " $fileName does not have the extension .edn");
        }

        if(false == $ednFileInfo->isReadable()) {
            throw new PatomicException(__METHOD__ . " $fileName was not found or cannot be read, please change file the permissions");
        }

        $this->clearData();

        $ednFileObject = new SplFileObject($fileName);
        $fileLineArray = array();

        while(!$ednFileObject->eof()) {
            $fileLineArray[] = $ednFileObject->fgets();
        }

        $this->body = $this->_vector($fileLineArray);

        $this->loadedFromFile = true;
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

        $this->clearIfLoadedFromFile();

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

    /**
     * Clears the current transaction body if user attempts to add retract or append to a transaction
     * loaded from disk. The idea is for transactions loaded from disk to be their own PatomicTranscation objects.
     */
    private function clearIfLoadedFromFile() {
        if($this->loadedFromFile) {
            $this->clearData();
            $this->loadedFromFile = false;
        }
    }
}