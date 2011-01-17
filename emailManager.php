<?php

//usage:
/*
<?php
include("emailManager.php");
include("/home/uff/public_html/intranet/includes/conn.php");

//constructor has default parameters
//database, log level, enable email sending
$mailer = new Emailer($CDB, Emailer::$LOG_FULL, true);
//database, LOG PARTIAL, enable email sending
//$mailer = new Emailer($CDB);

//optionally set the file execution comment
//allows easier differentiation between file runs if set correctly
//if not set, it includes timestamp, log level, and filename
$mailer->set_file_execution_comment("This file is being tested by jeremiah");

//set headers
$headers = "";
$headers .= "From: uInterview <exitint@uff.us>\n";
$headers .= "X-Mailer: PHP\n";
$headers .= "Return-Path: <you@example.com>\n";
$headers .= "Content-Type: text/html; charset=iso-8859-1\n";
$headers .= "bcc: you@example.com <you@example.com>";
$mailer->set_default_headers($headers);

//if using the mail manager to compose the message:
$mailer->set_default_email_header_and_footer("This is the header of your email", "This is the footer of your email");
$message = $mailer->compose_message("This is the body of your email");
$to = "you@example.com";
$subject = "test of the mail system";
//the debug array can have almost anything in it
$debug_array = array("interviewID" => "2010100721593764");

//send mail immediately
$mailer->send_email_with_default_headers($to, $subject, $message, $debug_array);

//queue multiple messages for mailing later
//only sends queued mail from the current file and execution
$mailer->send_email_with_default_headers($to, $subject, $message, $debug_array);
$mailer->send_email_with_default_headers($to2, $subject2, $message2, $debug_array2);
$mailer->send_email_with_default_headers($to3, $subject2, $message3, $debug_array3);
$mailer->send_all_queued_email();

//use different headers
$mailer->send_email($to, $subject, $message, $other_headers, $debug_array);
$mailer->queue_email($to, $subject, $message, $other_headers, $debug_array);
$mailer->send_all_queued_email();

?>
*/

class Emailer  {

  //all email information and all values in user option array
  public static $LOG_FULL = 0;
  //all email information and basic logging information
  public static $LOG_PARTIAL = 1;
  //all email information and no logging information
  public static $LOG_MINIMAL = 2;
  //email information only saved until message is sent, then deleted
  //if the email is not sent, some information is kept
  public static $LOG_DISABLED = 3;
  //current log level
  private $debug_log_level;

  //mail sent statuses
  //mail already sent
  public static $EMAIL_SENT = 1;
  //mail will be sent when send_all_queued_email is called
  public static $EMAIL_QUEUED = 0;
  //should never get sent
  public static $EMAIL_DO_NOT_SEND = -1;
  //when set to false, set emails to EMAIL_DO_NOT_SEND instead of queued
  public $enable_email_send;

  //information used for the content of every email
  //default php mail headers
  private $default_headers;
  //uff logo and text that goes at the top of the message
  private $default_email_header;
  //disclaimer and text that goes at the bottom of the message
  private $default_email_footer;

  //database we are storing information to
  private $database;
  //tables
  //when changing these values, make sure to also change the includes/tables.php values
  private static $emails_table = "sexy_mail_manager_emails";
  private static $debug_options_table = "sexy_mail_manager_email_debug_options";
  private static $file_execs_table = "sexy_mail_manager_file_execs";
  //debug options that will be included for every email
  private $default_debug_options;
  private $mail_count;
  private $file_exec_id; //links the email to the file name and start time

  //watch out for the fun hack for setting the default value for debug_log_level to LOG_PARTIAL
  public function __construct($client_database, $debug_log_level = NULL, $enable_email_send = true)  {
    $this->database = $client_database;
    //make this actually evaluate to true/false
    $this->enable_email_send = (bool)$enable_email_send;
    //dont allow nonstandard log levels
    if($debug_log_level == NULL)  {
      $debug_log_level = self::$LOG_PARTIAL;
    }
    if($debug_log_level == self::$LOG_FULL || $debug_log_level == self::$LOG_PARTIAL || $debug_log_level == self::$LOG_MINIMAL || $debug_log_level == self::$LOG_DISABLED)  {
      $this->debug_log_level = $debug_log_level;
    } else  {
      //currently printing a message, maybe just fail silently?
      print("Log level {$debug_log_level} not supported.  Allowable values are self::\$LOG_FULL, self::\$LOG_PARTIAL, self::\$LOG_MINIMAL, self::\$LOG_DISABLED");
    }
    $this->set_default_debug_options();
    $this->file_exec_id = $this->save_file_exec_info();
  }


  //INITIALIZE AND SET DEBUGGING INFORMATION///////////////////////////////////
  //turns off email sending, puts logging level to full
  public function enable_debug_mode($comment = "")  {
    $this->enable_email_send = false;
    $this->debug_log_level = self::$LOG_FULL;
    if(!empty($comment))  {
      $this->default_debug_options['file_execution_comment'] = $comment;
    }
  }

  //allows a custom file execution comment to help with debugging
  public function set_file_execution_comment($input)  {
    //string escapgin done in the save_debug_options method
    $this->default_debug_options['file_execution_comment'] = $input;
    //$this->default_debug_options['file_execution_comment'] = mysql_real_escape_string($input);
  }

  //sets the default debug options used in every email log\
  private function set_default_debug_options()  {
    $this->default_debug_options = array();
    $backtrace = debug_backtrace();
    //backtrace[0] will be this file, this location
    //backtrace[max] should be the calling file
    $size = count($backtrace) - 1;
    //$debug_options['line_number'] = $backtrace[$size]['line'];
    $this->default_debug_options['file_name'] = $backtrace[$size]['file'];

    $this->default_debug_options['file_execution_start'] = date("Y-m-d H:i:s");
    $this->default_debug_options['file_execution_comment'] = "{$this->default_debug_options['file_name']} executed on {$this->default_debug_options['file_execution_start']}";

    $this->mail_count = 0;

    $this->default_debug_options['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $this->default_debug_options['remote_addr'] = $_SERVER['REMOTE_ADDR'];
    //this contains the files absolute path but is currently being pulled by the backtrace
    //the backtrace is needed for line number so I will keep it ofr now
    //$this->default_debug_options['file_name'] = $_SERVER['SCRIPT_FILENAME'];
  }

  //saves the file exec information
  private function save_file_exec_info()  {
    $insert_sql = "insert into " . self::$file_execs_table . " set file_name='{$this->default_debug_options['file_name']}', start_time='{$this->default_debug_options['file_execution_start']}', file_comment='{$this->default_debug_options['file_execution_comment']}'";
    mysql_query($insert_sql, $this->database) or die(mysql_error());
    $file_exec_id = mysql_insert_id();
    return $file_exec_id;
  }

  //adds the default debug entries to the input array
  //also keeps track of the email count
  //added default value for when this is called without other values in the options array
  //not that line number and mail count will be overwritten if used in the parameter  
  private function add_default_debug_options($array = array())  {
    if(is_array($array))  {
      $array = array_merge($array, $this->default_debug_options);
    } else  {
      $array = $this->default_debug_options;
    }

    $backtrace = debug_backtrace();
    //backtrace[0] will be this file, this location
    //backtrace[max] should be the calling file
    $size = count($backtrace) - 1;
    $array['line_number'] = $backtrace[$size]['line'];
    
    $array['mail_count'] = $this->mail_count;
    $this->mail_count++;

    return $array;
  }

  //END INITIALIZE AND SET DEBUGGING INFORMATION///////////////////////////////
  
  //SET MAIL COMPOSITION VALUES///////////////////////////////////////////////
  
  //sets default php mail headers
  //possibly add some error checking if it does not get some standard fields?
  public function set_default_headers($default_headers)  {
    $this->default_headers = $default_headers;
  }

  //sets header and footer.  Together because you should never use one without the other
  public function set_default_email_header_and_footer($header, $footer)  {
    $this->default_email_header = $header;
    $this->default_email_footer = $footer;
  }

  //adds header and footer to email body
  public function compose_message($body)  {
    return $this->default_email_header . $body . $this->default_email_footer;
  }

  //END SET MAIL COMPOSITION VALUES////////////////////////////////////////////
  
  //USER MAIL INTERFACE////////////////////////////////////////////////////////
  
  //sends an email and performs some logging on it
  public function send_email($to, $subject, $message, $headers, $debug_options)  {
    $email_array = array('mail_to' => $to, 'subject' => $subject, 'message' => $message, 'headers' => $headers);
    $email_id = $this->save_queued_email($email_array, $debug_options);
    $result = $this->send_queued_email($email_id);
    return $result;
  }

  //sends mail but uses default headers
  public function send_email_with_default_headers($to, $subject, $message, $debug_options)  {
    $this->send_email($to, $subject, $message, $this->default_headers, $debug_options);
  }

  //save an email message into the database and set it ready to be sent when send_queued_email is called
  public function queue_email_with_default_headers($to, $subject, $message, $debug_options)  {
    $this->queue_email($to, $subject, $message, $this->default_headers, $debug_options);
  }

  //puts the email in the queue
  //could theoretically use this function to call send_email but it would have to expose the email id
  //  so a little bit of duplication
  public function queue_email($to, $subject, $message, $headers, $debug_options)  {
    $email_array = array('mail_to' => $to, 'subject' => $subject, 'message' => $message, 'headers' => $headers);
    $email_id = $this->save_queued_email($email_array, $debug_options);
  }

  //sends all mail queued by the current file
  //this function has not been completely tested with the new table structure query
  public function send_all_queued_email()  {
    //get the file name of the calling file
    $backtrace = debug_backtrace();
    $size = count($backtrace) - 1;
    $file_name = $backtrace[$size]['file'];

    //find all unsent emails from the current file run
    $sent_status = self::$EMAIL_QUEUED;
    $ququed_emails_sql = "
      select a.email_id
      from
        " . self::$emails_table . " as a
        join " . self::$file_execs_table . " as b on a.file_exec_id=b.file_exec_id
      where
      ";
    /*$queued_emails_sql = "
      select a.email_id 
      from 
        " . self::$emails_table . " as a 
        join " . self::$debug_options_table . " as b on a.email_id=b.email_id 
      where 
      a.sent_status='{$sent_status}' 
      and '{$this->default_debug_options['file_execution_start']}' = (select value from " . self::$debug_options_table . " where email_id=a.email_id and name='file_execution_start')
      and '{$this->default_debug_options['file_name']}' = (select value from " . self::$debug_options_table . " where email_id=a.email_id and name='file_name')";*/
    $queued_emails_query = mysql_query($queued_emails_sql, $this->database) or die(mysql_error());
    while($email_id = mysql_fetch_array($queued_emails_query))  {
      $this->send_queued_email($email_id['email_id']);
    }
  }

  //END USER EMAIL INTERFACE///////////////////////////////////////////////////

  //EMAIL BACKEND//////////////////////////////////////////////////////////////
  //saves queued mail to the database
  private function save_queued_email($email, $debug_options)  {
    //create db safe values
    $email_safe = array();
    foreach($email as $key => $value)  {
      $email_safe[$key] = mysql_real_escape_string($email[$key]);
    }

    if($this->enable_email_send)  {
      $sent_status = self::$EMAIL_QUEUED;
    } else  {
      $sent_status = self::$EMAIL_DO_NOT_SEND;
    }

    $email_insert_sql = "insert into " . self::$emails_table . "  set mail_to='{$email_safe['mail_to']}', subject='{$email_safe['subject']}', message='{$email_safe['message']}', headers='{$email_safe['headers']}', date_queued=NOW(), sent_status='{$sent_status}', file_exec_id='{$this->file_exec_id}'";
    $insert_successful = mysql_query($email_insert_sql, $this->database) or die(mysql_error());
    $email_id = mysql_insert_id($this->database);
    //negative values mean an overflow error and 0 is the error condition
    if(isset($email_id) && $email_id > 0)  {
      $this->save_debug_options($email_id, $debug_options);
      return $email_id;
    }
    return false;
  }

  //saves the debug_options as key value pairs with the email id
  //amount saved depends on the log level
  //also saves the line number and file name of the calling file
  private function save_debug_options($email_id, $debug_options)  {
    if($this->debug_log_level == self::$LOG_FULL)  {
      $debug_options = $this->add_default_debug_options($debug_options);
    }  else if($this->debug_log_level == self::$LOG_PARTIAL)  {
      $debug_options = $this->add_default_debug_options();
    }  else  { //this->debug_log_level == self::$LOG_MINIMAL or self::$LOG_DISABLED
      $debug_options = array();
    }
    //save the contents of the options array to the options table
    foreach($debug_options as $key => $value)  {
      //if value is an array, get lazy
      $key_safe = mysql_real_escape_string($key);
      if(is_array($value))  {
        $value = serialize($value);
      }
      $value_safe = mysql_real_escape_string($value);

      $options_insert_sql = "insert into " . self::$debug_options_table . " set email_id='{$email_id}', name='{$key_safe}', value='{$value_safe}'";
      mysql_query($options_insert_sql, $this->database) or die(mysql_error());
    }
  }

  //sends a queued mail by email id
  //on failure adds a failure notice to the options table
  private function send_queued_email($email_id)  {
    $sent_status = self::$EMAIL_QUEUED;
    $email_info_sql = "select * from " . self::$emails_table . " where email_id='{$email_id}' and sent_status='{$sent_status}'";
    $email_info_query = mysql_query($email_info_sql, $this->database) or die(mysql_error());
    if(mysql_num_rows($email_info_query))  {
      $email_info = mysql_fetch_array($email_info_query);
      $email_sent = mail($email_info['mail_to'], $email_info['subject'], $email_info['message'], $email_info['headers']);
      if($email_sent)  {
        //once the email is sent, if logging is disabled, delete all information about the email
        if($this->debug_log_level == self::$LOG_DISABLED)  {
          $delete_email_sql = "delete from " . self::$emails_table . " where email_id='{$email_id}'";
          mysql_query($delete_email_sql) or die(mysql_error());
          $delete_options_sql = "delete from " . self::$debug_options_table . " where email_id='{$email_id}'";
          mysql_query($delete_options_sql) or die(mysql_error());
        } else  {
          //otherwise, just send the email
          $sent_status = self::$EMAIL_SENT;
          $update_email_sql = "update " . self::$emails_table . " set sent_status='{$sent_status}', date_sent=NOW() where email_id='{$email_id}'";
          mysql_query($update_email_sql, $this->database) or die(mysql_error());
        }
      } else  {
        //if the email doesnt send correctly, add an entry to the debug table
        $add_failure_message_sql = "insert into " . self::$debug_options_table . " set email_id='{$email_id}', name='Email Send Failure', value=NOW()";
        mysql_query($add_failure_message_sql, $this->database) or die(mysql_error());
      }
      //return whether the email sends correctly (should just be the return value of mail()
      return $email_sent;
    }
    //just return false if the email doesn't exist
    //if you get here, there is probably an issue somewhere else in code
    //or you are calling this method with random values
    return false;
  }

  //END EMAIL BACKEND//////////////////////////////////////////////////////////
  //
  //BEGIN MANAGER FRONTEND/////////////////////////////////////////////////////
  //these don't need to be static because we don't have E_STRICT reporting on
  //making them static anyway

  //return all file executions and list the number of emails each has sent
  public static function get_file_exec_query_with_filter($database, $file_name = "", $exec_time = "")  {
    $file_name = mysql_real_escape_string($file_name);
    $exec_time = mysql_real_escape_string($exec_time);
    $file_exec_sql = "
      select 
        a.*,
        (select count(*) from " . self::$emails_table . " as b where a.file_exec_id=b.file_exec_id) as email_count
      from " . self::$file_execs_table . " as a
      where 
        file_name like '%{$file_name}%'
        and start_time like '%{$exec_time}%'
      order by start_time desc";
    $file_exec_query = mysql_query($file_exec_sql, $database) or die(mysql_error());
    return $file_exec_query;
  }

  //given an email id, return all emails sent with the same file name and execution time
  public static function get_emails_by_email_id_query($database, $email_id)  {
    $email_id = mysql_real_escape_string($email_id);
    $email_sql = " select * from " . self::$emails_table . " where file_exec_id = (select file_exec_id from " . self::$emails_table . " where email_id='{$email_id}')";
    $email_query = mysql_query($email_sql) or die(mysql_error());
    return $email_query;
  }

  //give a file exec id, return all emails with that id
  public static function get_emails_by_file_exec_id_query($database, $file_exec_id)  {
    $file_exec_id = mysql_real_escape_string($file_exec_id);
    $email_sql = "
      select * 
      from " . self::$emails_table . "
      where file_exec_id='{$file_exec_id}'";
    $email_query = mysql_query($email_sql) or die(mysql_error());
    return $email_query;
  }

  public static function get_email_info_query($database, $email_id)  {
    $email_id = mysql_real_escape_string($email_id);
    $email_sql = "select * from " . self::$emails_table . " where email_id='$email_id' limit 1";
    $email_query = mysql_query($email_sql, $database) or die(mysql_error());
    return $email_query;
  }

  public static function get_email_debug_options_query($database, $email_id)  {
    $email_id = mysql_real_escape_string($email_id);
    $options_sql = "select * from " . self::$debug_options_table . " where email_id='{$email_id}'";
    $options_query = mysql_query($options_sql, $database) or die(mysql_error());
    return $options_query;
  }

  //END MANAGER FRONTEND///////////////////////////////////////////////////////
}
