<?php defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Email extends CI_Email
{
    // DB table
    private $table_email_queue = 'email_queue';

    // Main controller
    private $main_controller = 'sys/queue_email/send_pending_emails';

    // PHP Nohup command line
    private $phpcli = 'nohup php';
    private $expiration = NULL;

    // Status (pending, sending, sent, failed)
    private $status;

    /**
     * Constructor
     */
    public function __construct($config = array())
    {
        parent::__construct($config);

        log_message('debug', 'Email Queue Class Initialized');

        $this->expiration = 60*5;
        $this->CI = & get_instance();


        $this->CI->load->database('default');
        $this->CI->load->helper('email_details');
    }

    public function set_status($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get
     *
     * Get queue emails.
     * @return  mixed
     */
    public function get($limit = NULL, $offset = NULL)
    {
        if ($this->status != FALSE)
            $this->CI->db->where('status', $this->status);

        $query = $this->CI->db->get("{$this->table_email_queue}", $limit, $offset);

        return $query->result();
    }

    /**
     * Save
     *
     * Add queue email to database.
     * @return  mixed
     */
    public function send($skip_job = FALSE)
    {
        try {
            if ( $skip_job === TRUE ) {
                return parent::send();
            }


            if ($this->_attachments != '') {

                for($i=0;$i<count($this->_attachments);$i++){

                    $directoryname1 = "./assets/emailqueue/";

                    if (!is_dir($directoryname1)) {
                        mkdir($directoryname1, 0755, true);
                    }

                    $target_file[] = $directoryname1 . str_replace(" ", "_", basename($this->_attachments[$i]['name'][0]));

                    if (!copy($this->_attachments[$i]['name'][0], $target_file[$i]))
                        echo "failed to copy $target_file[$i]...\n";


                }
            }

            $to = is_array($this->_recipients) ? implode(", ", $this->_recipients) : $this->_recipients;

            $cc = implode(", ", $this->_cc_array);
            $bcc = implode(", ", $this->_bcc_array);



            $dbdata = array(
                'to' => $to,
                'cc' => $cc,
                'bcc' => $bcc,
                'message' => $this->_body,
                'headers' => serialize($this->_headers),
                'attachments' => json_encode($target_file),
                'status' => 'pending',
                'date' => date("Y-m-d H:i:s")
            );

            return $this->CI->db->insert($this->table_email_queue, $dbdata);
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
            return false;
        }
}
/**
 * Start process
 *
 * Start php process to send emails
 * @return  mixed
 */
public function start_process()
{
    $filename = FCPATH . 'index.php';
    $exec = shell_exec("{$this->phpcli} {$filename} {$this->main_controller} > /dev/null &");

    return $exec;
}

/**
 * Send queue
 *
 * Send queue emails.
 * @return  void
 */
public function send_queue()
{
    try {
        $this->set_status('pending');
        $emails = $this->get();


        $this->CI->db->where('status', 'pending');
        $this->CI->db->set('status', 'sending');
        $this->CI->db->set('date', date("Y-m-d H:i:s"));
        $this->CI->db->update($this->table_email_queue);

        /*Start Smtp Config*/
        $this->CI->db->select('Key, Value');
        $names = ['EmailFrom', 'EmailPassword', 'SmtpHost', 'SmtpPort'];
        $this->CI->db->where_in('Key', $names);
        $smtp1 = $this->CI->db->get('tblmstconfiguration');
        $row1 = $smtp1->result_array();
        $getResultArray = array_column($row1, 'Value', 'Key');

        $config['protocol'] = 'smtp';
        $config['smtp_host'] = $getResultArray['SmtpHost'];
        $config['smtp_port'] = $getResultArray['SmtpPort'];
        $config['smtp_user'] = $getResultArray['EmailFrom'] ;
        $config['smtp_pass'] = $getResultArray['EmailPassword'];
        $config['charset'] = 'utf-8';
        $config['mailtype'] = 'html';
        $config['starttls'] = true;
        $config['charset'] = 'iso-8859-1';
        $config['newline'] = "\r\n";

        $this->initialize($config);

        /*End Smtp Config*/


        foreach ($emails as $email)
        {

            $recipients = explode(", ", $email->to);

            $cc = !empty($email->cc) ? explode(", ", $email->cc) : array();
            $bcc = !empty($email->bcc) ? explode(", ", $email->bcc) : array();

            $this->_headers = unserialize($email->headers);

            $this->to($recipients);
            $this->cc($cc);
            $this->bcc($bcc);

            $this->message($email->message);

            if ($email->attachments != null) {

                foreach (json_decode($email->attachments) as $filename) {

                    $this->attach($filename);
                }
            }

            if ($this->send(TRUE)) {

                if ($email->attachments != null) {

                    foreach (json_decode($email->attachments) as $filename) {

                        unlink($filename);

                    }
                }

                echo $status = 'sent';
            } else {
                echo $status = 'Fail';

            }

            $this->CI->db->where('id', $email->id);

            $this->CI->db->set('status', $status);
            $this->CI->db->set('date', date("Y-m-d H:i:s"));
            $this->CI->db->update($this->table_email_queue);

        }

    } catch (Exception $e) {
        trigger_error($e->getMessage(), E_USER_ERROR);
        return false;
    }
}

/**
 * Retry failed emails
 *
 * Resend failed or expired emails
 * @return void
 */
public function retry_queue()
{
    $expire = (time() - $this->expiration);
    $date_expire = date("Y-m-d H:i:s", $expire);

    $this->CI->db->set('status', 'pending');
    $this->CI->db->where("(date < '{$date_expire}' AND status = 'sending')");
    $this->CI->db->or_where("status = 'failed'");

    $this->CI->db->update($this->table_email_queue);

    log_message('debug', 'Email queue retrying...');
}


}