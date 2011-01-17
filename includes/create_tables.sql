drop table sexy_mail_manager_emails;
drop table sexy_mail_manager_email_debug_options;
drop table sexy_mail_manager_file_execs;

create table sexy_mail_manager_emails ( 
  email_id int not null auto_increment, 
  mail_to tinytext, 
  subject tinytext,
  message mediumtext, 
  headers mediumtext, 
  date_queued datetime not null, 
  date_sent datetime, 
  sent_status int not null, 
  file_exec_id int not null,
  primary key(email_id)
);

create table sexy_mail_manager_email_debug_options  (
  debug_option_id int not null auto_increment,
  email_id int not null,
  name tinytext not null,
  value mediumtext,
  primary key(debug_option_id)
);

create table sexy_mail_manager_file_execs  (
  file_exec_id int not null auto_increment,
  start_time datetime not null,
  file_name mediumtext not null,
  file_comment mediumtext not null,
  primary key(file_exec_id)
);

