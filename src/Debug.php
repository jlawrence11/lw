<?php
/**
 * Created by: Jon Lawrence on 2015-03-18 10:07 AM
 */

namespace jlawrence\lw;

class Debug
{
    private $site;
    private $notices = array();
    private $warnings = array();
    private $useTemp = false;
    private $debug = false;

    public function __construct(Factory $site)
    {
        $this->site = $site;
        ini_set('display_errors', 0);
        set_exception_handler(array($this, "handleException"));
        register_shutdown_function(array($this, "handleShutdown"));
        set_error_handler(array($this, "handleError"));
        $this->notice("Debugging class loaded");
    }

    /**
     * Exception Handler
     *
     * Handles the exception
     *
     * @param $e \Exception The exception thrown
     */
    public function handleException(\Exception $e)
    {
        $this->error($e->getMessage() ." in: '". $e->getFile() ."' on line: ". $e->getLine());
    }

    /**
     * Shutdown handler
     *
     * If a fatal error occurred, will pass it through the error handler for
     * display
     */
    public function handleShutdown()
    {
        $e = error_get_last();
        //currently, blanket any error to pass, might change
        //in the future to only process certain types of errors
        if (1 == $e['type']) {
            //There was an error, let's kill with it.
            //put together a string to send to our error handler
            $type = $this->getErrorType($e['type']);
            $msg = "$type: {$e['message']} in '{$e['file']}' on line {$e['line']}";
            $this->error($msg);
        }
    }

    /**
     * Error Handler
     *
     * Handles every notice/error/warning that gets thrown and stops nicely on error
     *
     * @param $errNo int Error number
     * @param $errStr String Error text
     * @param $errFile String File where error occurred
     * @param $errLine String Line number of occurrence
     * @return bool Returns false if execution doesn't need to end.
     */
    public function handleError($errNo, $errStr, $errFile, $errLine)
    {
        $type = $this->getErrorType($errNo);
        $msg = "$type: $errStr in '$errFile' on line $errLine";
        switch ($errNo) {
            case 1:
            case 16:
            case 64:
            case 256:
                $this->error($errStr);
                break;
            case 2:
            case 32:
            case 128:
                $this->warning($msg);
                return false;
                break;
            case 2048:
                //Comment the following out if you want STRICT notices included in the debug
                return false;
                break;
            case 8:
            case 1024:
            case 8192:
            case 16384:
                $this->notice($msg);
                return false;
                break;
            default:
                $this->error($msg);
        }
        //didn't error out.  False alarm!
        return false;
    }

    /**
     * Convert Error Code
     *
     * Will convert the error code in to its string equivalent
     *
     * @param String $type Integer value of error type
     * @return String Error number converted to string-format
     */
    private function getErrorType($type)
    {
        //Take the type in int format and return a string equivalent
        switch ($type) {
            case E_ERROR: // 1
                return 'E_ERROR';
            case E_WARNING: // 2
                return 'E_WARNING';
            case E_PARSE: // 4
                return 'E_PARSE';
            case E_NOTICE: // 8
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384
                return 'E_USER_DEPRECATED';
        }
        return "";
    }

    /**
     * Set a notice
     *
     * Set a notice, should be used for class loads/etc
     * @param String $msg Message to add
     */
    public function notice($msg)
    {
        $this->notices[] = $msg;
    }

    /**
     * Set a warning
     *
     * Sets a warning to the stack
     * @param String $msg Warning to add
     */
    public function warning($msg)
    {
        $this->warnings[] = $msg;
    }

    /**
     * Turns on or off the 'debug' mode
     *
     * Sets the class to know how to error out, ie display all notices/
     * warnings along with the error
     *
     * @param Boolean $debug True/false for if class is in debug mode
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Get HTML warnings
     */
    public function getHtmlWarnings()
    {
        $ret = implode("<br>\n", $this->warnings);
        return $ret;
    }

    /**
     * Get HTML notices
     *
     * Get's the notices separated by <<br>>
     */
    public function getHtmlNotices()
    {
        $ret = implode("<br>\n", $this->notices);
        return $ret;
    }

    /**
     * Get raw HTML of error+warning+notices
     */
    public function getRaw($msg="")
    {
        $html = "";
        if (true == $this->debug) {
            if($msg!="") $html = "<h2>Error:</h2>\n$msg\n";
            $html .= "<h2>Warnings:</h2>\n";
            $html .= $this->getHtmlWarnings();
            $html .= "<h2>Notices:</h2>\n";
            $html .= $this->getHtmlNotices();
        } else {
            if($msg!="") $html = "<h2>Error:</h2>\n$msg\n";
        }
        return $html;
    }

    /**
     * Error and report
     *
     * Please know, once this method is used, your application is over.
     * This is implemented so that you can catch an error, send it to
     * this method, and all the warnings/notices will be displayed for
     * your debugging use as well as the error.
     *
     * @param String $msg The message of the error that occurred
     */
    public function error($msg)
    {
        // We are creating a simple HTML document to display things neatly
        $html = $this->getRaw($msg);
        if (true === $this->useTemp) {
           // $this->site->template->assign('body', $html);
           // $this->site->template->display();
            die();
        } else {
            die($html);
        }
    }
}