<?php

class Email_queue extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

    }

    public function index()
    {
        //echo "demo";die();
        $from_email = "abhishek@example.com";
        $to_email = "abhishek.patel@theopeneyes.in";

        //Load email library
        $this->load->library('email');
    //    $this->load->helper('email_details');

        $this->email->from($from_email, 'Your Name');
        $this->email->to($to_email);
        $this->email->subject('Email Test');
        $this->email->message('Testing the email class.');
        $this->email->attach('http://localhost/LMS_API//assets/images/logo.png');
        $this->email->attach('assets/Instructor/1/image/1594971027046_photography.jpg');
        $this->email->attach('C:/Users/developer4/Downloads/Oess_logo.png');
        $this->email->attach('C:/Users/developer4/Downloads/OpenEyes-Capabilities-2020.pdf');

        if($this->email->send())
            echo "Email sent successfully.";
        else
            echo "Error in sending Email.";

    }

    public function send_queue()
    {
        $this->email->send_queue();
    }

    public function retry_queue()
    {
        $this->email->retry_queue();
    }

    public function delete_queue(){

        $this->db->where('date < DATE_SUB(NOW(), INTERVAL 7 DAY)');
        $this->db->where('status !=','pending');
        $this->db->delete("email_queue");

    }
}