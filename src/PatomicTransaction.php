<?php

//TODO: Adding complex data i.g https://github.com/jonase/learndatalogtoday/blob/master/resources%2Fdb%2Fdata.edn

namespace taywils\Patomic;

/**
 * PHP object representation of a Datomic transaction
 *
 * @see http://docs.datomic.com/transactions.html
 */
class PatomicTransaction 
{
    private $body;
    private $loadedFromFile;
    private $reflection;

    private static $KEYWORD_ADD         = "add";
    private static $KEYWORD_RETRACT     = "retract";
    private static $INST_TAGNAME        = "inst";
    private static $ENTITY_CLASSNAME    = 'taywils\Patomic\PatomicEntity';
    private static $VECTOR_CLASSNAME    = 'igorw\edn\Vector';
    private static $MAP_CLASSNAME       = 'igorw\edn\Map';
    private static $DATEFORMAT          = 'Y-m-d';
    private static $DATETIME_CLASSNAME  = 'DateTime';

    use TraitEdn;

    /**
     * Creates a new Transaction
     */
    public function __construct() {
        $this->body = $this->_vector(array());
        $this->loadedFromFile = false;
        $this->reflection = new \ReflectionClass($this);
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
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " argument must be a valid ". self::$ENTITY_CLASSNAME ." object");
        }

        $this->clearIfLoadedFromFile();

        if(is_null($key)) {
            $this->body->data[] = $elem;
        } else {
            $this->body->data[$key] = $elem;
        }

        return $this;
    }

    /**
     * Allows for the addition of multiple attribute-value pairs as a combined entity to create a single datom
     *
     * Example where -106 is the tempId:
     *
     * {:db/id #db/id [:db.part/user -106]
     *   :person/name "Richard Smith"
     *   :person/born #inst "1979-11-12"
     *   :person/occupation "Salesman"
     *   :person/ssn "535-18-7230"}
     *
     * @param int $tempId Optional temporary ID for the datom
     * @return $this
     * @throws PatomicException
     */
    public function addMany($tempId = null) {
        $numargs = func_num_args();

        if($numargs <= 1) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " expects at minimum two arguments");
        }

        if(!is_null($tempId) && !is_int($tempId)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " tempId argument must be an integer");
        }

        $argsArray = func_get_args();

        for($i = 1; $i < $numargs; $i++) {
            if(!is_array($argsArray[$i]) || empty($argsArray[$i])) {
                throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " was given an empty or non-array argument");
            }
        }

        $datom = $this->_map();

        $idTag = $this->_tag("db/id");
        $dbUser = $this->_vector(array($this->_keyword("db.part/user")));
        if(!is_null($tempId)) {
            $dbUser->data[] = $tempId;
        }
        $idTagged = $this->_tagged($idTag, $dbUser);

        $datom[$this->_keyword("db/id")] = $idTagged;

        // Where array("entity" => "attribute" , value)
        for($i = 1; $i < $numargs; $i++) {
            $keys = array_keys($argsArray[$i]);
            $vals = array_values($argsArray[$i]);

            $entity     = $keys[0];
            $attribute  = $argsArray[$i][$keys[0]];
            $value      = $vals[1];

            $datom[$this->_keyword($entity . "/" . $attribute)] = $this->createInstTag($value);
        }

        $this->clearIfLoadedFromFile();

        $this->body->data[] = $datom;

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

                    case self::$MAP_CLASSNAME:
                        echo $this->_encode($elem) . PHP_EOL;
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
                    case self::$MAP_CLASSNAME:
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
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " \$fileName argument must be a non-empty string");
        }
        
        $ednFileInfo = new \SplFileInfo($fileName);
        
        if("edn" != $ednFileInfo->getExtension()) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " $fileName does not have the extension .edn");
        }

        if(false == $ednFileInfo->isReadable()) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " $fileName was not found or cannot be read, please change file the permissions");
        }

        $this->clearData();

        $ednFileObject = new \SplFileObject($fileName);
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
     * @param mixed $value
     * @param int $tempIdNum
     * @param string $methodKeyword Determines whether to add or retract
     * @return $this
     * @throws PatomicException
     */
    private function addOrRetract($entityName, $attributeName, $value, $tempIdNum = null, $methodKeyword) {
        if(is_null($entityName) || !is_string($entityName)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " entityName must be a string");
        }

        if(is_null($attributeName) || !is_string($attributeName)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " attributeName must be a string");
        }

        if(is_null($value)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " value argument cannot be null");
        }

        if(!is_null($tempIdNum) && !is_int($tempIdNum)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " tempId argument must be an integer");
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

        $vec->data[] = $this->createInstTag($value);

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

    private function createInstTag($value) {
        if(is_object($value) && get_class($value) == self::$DATETIME_CLASSNAME) {
            $instTag    = $this->_tag(self::$INST_TAGNAME);
            $instTagged = $this->_tagged($instTag, $value->format(self::$DATEFORMAT));

            return $instTagged;
        } else {
            return $value;
        }
    }
}
