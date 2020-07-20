

1> Import database file email_queue.sql
2> copy application\libraries\MY_Email.php file 
3> Cheack Your smtp Setting on MY_Email.php line no - 146 
3> copy controller modules\Email_queue\controllers\Email_queue.php




testing : http://localhost/Project_name/Email_queue/
Sending : http://localhost/Project_name/Email_queue/send_queue
Retry   : http://localhost/Project_name/Email_queue/retry_queue
Delete  : http://localhost/Project_name/Email_queue/delete_queue (delete data before current date to 1 week before data has status is not pending)