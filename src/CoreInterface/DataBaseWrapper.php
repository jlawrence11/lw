<?php
/**
 * Created by: Jon Lawrence on 2015-06-17 8:55 AM
 */
namespace jlawrence\lw\CoreInterface;


/**
 * DataBaseWrapper
 *
 * Methods used throughout the framework to interact with a database.  Since this is a wrapper,
 * it can be implemented by others for a different database/connection than the default PDO
 * one that comes with LW.
 *
 * @author Jon Lawrence <jlawrence11@gmail.com>
 * @package LW_FW
 * @subpackage core
 */
interface DataBaseWrapper
{
    /**
     * Insert in to database
     *
     * Helper function to insert information in to the database, uses PDO
     * parametrization to  prevent SQL injections, don't add slashes.
     *
     * @param String $tableName Table name to insert to without prefix
     * @param Array $fields Associative array of the fields to insert, format 'fieldName => value'
     * @return Boolean Whether or not it was a success
     */
    public function insert($tableName, $fields);

    /**
     * Create Table
     *
     * Helper function to create a new table in the database.  DO NOT use this
     * with user input, it is both bad for your health, and the safety of your
     * database.
     *
     * @param String $tableName The name of the table to create without prefix
     * @param Array $fields Associative array of the fields in format 'fieldName => properties'
     * @return Boolean Whether or not it was a success.
     */
    public function createTable($tableName, $fields);

    /**
     * Last Insert ID
     *
     * Will return the last auto-increment ID from an insert
     *
     * @return Integer The last insert ID (should be the equivalent of 'select LAST_INSERT_ID()')
     */
    public function getInsertID();

    /**
     * Affected Rows
     *
     * Gives the number of rows affected by the last insert/update/delete
     * query ran through $this->query.
     *
     * @return Integer Number of affected rows.
     */
    public function affectedRows();

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
    public function query($stm, $vars = null, $expectOne = false, $multExec = false);

    /**
     * Number of rows in SELECT statement
     *
     * Gives the number or rows from the last $this->query select statement
     * processed.
     *
     * @return Integer Number of rows.
     */
    public function numRows();
}