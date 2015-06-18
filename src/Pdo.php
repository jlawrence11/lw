<?php
namespace jlawrence\lw;
use jlawrence\lw\CoreInterface\DataBaseWrapper;

/**
 * LW PDO Class
 *
 * This is going to be the replacement for 'mysql.class.php'.  This will help
 * emulate some useful MySQLi features using PDO, and centralizes the DB
 * connection within the LW Framework.
 *
 * @author Jon Lawrence <jlawrence11@gmail.com>
 * @package LW_FW
 * @subpackage core
 */
class Pdo implements DataBaseWrapper
{
    /**
     * Hold reference to LW_Site object
     */
    private $site;
    private $pdo;
    private $num_rows;
    private $affected_rows;
    public $TP; //table prefix

    //construct
    public function __construct (Factory $site, $cfg){
        $this->site = $site;

        $cState = $cfg['engine'] . ":dbname=". $cfg['database'] .";host=". $cfg['host'];
        try {
            $this->pdo = new \PDO($cState, $cfg['user'], $cfg['password']);
        } catch (\PDOException $e) {
            //$this->site->debug->error($e->getMessage());
            $this->site->debug->error("Failed to connect to database.");
        }
        $this->TP = $cfg['prefix'];
        $this->site->debug->notice("Connected to {$cfg['engine']} on '{$cfg['host']}' with user '{$cfg['user']}'. Class Loaded.");
    }

    /**
     * Query
     *
     * Query command that will use PDO to prepare the Statement, and then execute
     * it with the variable array if there is one.  This function will also set
     * $this->num_rows and $this->affected_rows in order to emulate functionality
     * from MySQLi that I am personally used to and enjoy the functionality of.
     * It will return either false if it fails at any point, or will return
     * the result of a select statement, or 'true' if was successful and not
     * a select statement.  Will also send output to the debug handler.
     * Please note, if PDO determines that something goes wrong enough to
     * throw an error, it is up to the user of this class to catch it.
     *
     * @param String $stm The SQL query statement
     * @param Array $vars The variable array, creating to match PDO styling
     * @param Bool $expectOne Whether or not only expecting one result.
     * @param Bool $multExec Multiple executes being performed?
     *
     * @return Mixed See description for possible returns
     */
    public function query($stm, $vars=null, $expectOne=false, $multExec=false) {
        //we want to be safe, so each call, we clear internals
        $this->num_rows = 0;
        $this->affected_rows = 0;
        //Prepare for battle!
        if(($st = $this->pdo->prepare($stm)) !== false) {
            if(true == $multExec){
                foreach($vars as $var) {
                    if(!is_array($var)) {
                        $var = array($var);
                    }
                    if($st->execute($var)) {
                        //echo $stm;
                        //$this->site->debug->notice("Query '{$stm}' completed successfully.");//. print_r($vars, true));
                        //in case was insert/update/delete (force) since we are not keeping PDOStatement
                        $this->affected_rows = $st->rowCount();
                        try {
                            $ar = $st->fetchAll(\PDO::FETCH_ASSOC);
                        } catch (\PDOException $e) {
                            //if we can't fetch, the force is strong with this one, but was still caught
                            return true;
                        }

                        $this->num_rows = count($ar); //emulate MySQLi->num_rows, except private
                        //if no results, return true, the force is strong with this one.
                    } else {
                        $this->site->debug->notice("Query '{$stm}' failed. ||". print_r($st->errorInfo(), true));
                    }
                }
            } else {
                if($st->execute($vars)) {
                    //echo $stm;
                    $this->site->debug->notice("Query '{$stm}' completed successfully.");//. print_r($vars, true));
                    //in case was insert/update/delete (force) since we are not keeping PDOStatement
                    $this->affected_rows = $st->rowCount();
                    try {
                        $ar = $st->fetchAll(\PDO::FETCH_ASSOC);
                    } catch (\PDOException $e) {
                        //if we can't fetch, the force is strong with this one, but was still caught
                        return true;
                    }

                    $this->num_rows = count($ar); //emulate MySQLi->num_rows, except private
                    //if no results, return true, the force is strong with this one.
                    if(($this->num_rows == 0) || (!is_array($ar))) {
                        return true;
                        //else results! Let's go Han, carry it back!
                    } elseif(($this->num_rows == 1) && ($expectOne !== false)) {
                        //only one result set, return it as you would expect a single result
                        return $ar[0];
                    } else {
                        return $ar;
                    }
                } else {
                    $this->site->debug->notice("Query '{$stm}' failed. || ". print_r($st->errorInfo(), true));
                }
            }
        } else {
            $this->site->debug->warning("Query '{$stm}' failed to prepare.");  //for battle
        }
        return false;  //the mission was not successful.

    }

    /**
     * Number of rows in SELECT statement
     *
     * Gives the number or rows from the last $this->query select statement
     * processed.
     *
     * @return Integer Number of rows.
     */
    public function numRows() {
        return $this->num_rows;
    }

    /**
     * Affected Rows
     *
     * Gives the number of rows affected by the last insert/update/delete
     * query ran through $this->query.
     *
     * @return Integer Number of affected rows.
     */
    public function affectedRows() {
        return $this->affected_rows;
    }

    /**
     * Create Table
     *
     * Helper function to create a new table in the database.  DO NOT use this
     * with user input, it is both bad for your health, and the saftey of your
     * database.
     *
     * @param String $tableName The name of the table to create without prefix
     * @param Array $fields Associative array of the fields in format 'fieldName => properties'
     * @return Boolean Whether or not it was a success.
     */
    public function createTable($tableName, $fields) {
        $tbName = $this->TP . $tableName;
        $names = array();
        foreach($fields as $name => $value) {
            $names[] = "{$name} {$value}";
        }
        $field = implode(',', $names);
        $sql = "CREATE TABLE {$tbName} ({$field})";
        return $this->query($sql);
    }


    /**
     * Insert in to database
     *
     * Helper function to insert information in to the database, uses PDO
     * parameterization to  prevent SQL injections, don't add slahses.
     *
     * @param String $tableName Table name to insert to without prefix
     * @param Array $fields Associative array of the fields to insert, format 'fieldName => value'
     * @return Boolean Whether or not it was a success
     */
    public function insert($tableName, $fields) {
        $tbName = $this->TP . $tableName;
        $names = array();
        $values = array();
        foreach($fields as $name => $value) {
            $names[] = $name;
            $values[] = $value;
        }
        $name = implode(', ', $names);
        $para = implode(', ', array_fill(0, count($values), "?"));
        $sql = "INSERT INTO {$tbName} ({$name}) VALUES ({$para});";
        return $this->query($sql, $values);
    }

    /**
     * Last Insert ID
     *
     * Will return the last auto-increment ID from an insert
     *
     * @return Integer The last insert ID (should be the equivalent of 'select LAST_INSERT_ID()')
     */
    public function getInsertID() {
        return $this->pdo->lastInsertId();
    }
}