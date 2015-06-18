<?php
namespace jlawrence\lw;

/**
 * Email Class
 *
 * Simple email class that will use the PHP 'mail' method, but allow for the
 * proper construction of headers, and also allows tying in to the template
 * class for email body. May be turned in to a wrapper for phpmailer or swiftmailer
 * in the future.
 *
 * @author Jon Lawrence <jlawrence11@gmail.com>
 * @package LW_FW
 * @subpackage core
 */
class Email
{
    /**
     * Hold reference to Factory object
     */
    private $site;
    protected $from;
    protected $replyTo;
    protected $mime;
    protected $contentType;
    protected $template;

    //construct
    public function __construct (Factory $site, $cfg){
        $this->site = $site;
        //Set up the default values
        $this->from = 'From: '. $cfg['from'];
        $this->replyTo = 'Reply-To: '. $cfg['replyTo'];
        $this->mime = 'MIME-Version: '. ((isset($cfg['mime'])) ? $cfg['mime'] : "1.0");
        $this->contentType = "Content-type:text/html;charset=UTF-8";
        $this->template = false;
    }

    /**
     * Assign template
     *
     * Allows a template to be assigned for the email to be sent. Set to null or
     * "" if you want to clear a template for another email.
     */
    public function setTemplate($tFile) {
        //First, make sure the template dependency is loaded
        //$this->site->checkDependency("template", true);
        $this->template = $tFile;
    }

    /**
     * Send Mail
     *
     * This method is the workhorse for the class, sending of emails.  The third
     * parameter is either an associative array of variables to be used in the
     * template (if one is assigned), or the body text of the email if a
     * template has not been assigned.
     *
     * @param String $to The email address the email will be sent to
     * @param String $subject The Subject text of the email
     * @param Array|String $body The array of variables used in the template or body text
     */
    public function send($to, $subject, $body) {
        //First, we'll set up the header string using variables that were passed
        //in the constructor
        $head = array($this->mime, $this->contentType, $this->from, $this->replyTo);
        $head = implode("\r\n", $head);
        if($this->template != false) {
            $body = $this->site->template->loneTemplate("email". DS . $this->template, $body);
        }
        //echo "<pre>\nTo: {$to}\nSub:{$subject}\nBody:\n\n{$body}\n\nHeaders:\n{$head}\n</pre>";
        mail($to, $subject, $body, $head);
    }

}