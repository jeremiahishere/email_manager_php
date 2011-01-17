<?php
include("includes/emailManager.php");
include("/home/uff/public_html/intranet/includes/conn.php");

//constructor has default parameters
//database, log level, enable email sending
$mailer = new Emailer($CDB, Emailer::$LOG_FULL, true);
//database, LOG PARTIAL, enable email sending
//$mailer = new Emailer($CDB);

//optionally set the file execution comment
//allows easier differentiation between file runs if set correctly
//if not set, it includes timestamp, log level, and filename
$mailer->set_file_execution_comment("This file is being run on " . date("Y-m-d H:i:s") . " to import data.");
//enable debug mode (also lets you set the file execution comment)
//it will overwrite any previous comments
//it stops emails from being sent and turns on full debugging mode
$mailer->enable_debug_mode("This file is being debugged by Jeremian");

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
