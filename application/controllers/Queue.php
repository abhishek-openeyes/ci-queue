<?php

class Queue extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

//        if (! $this->input->is_cli_request())
//            show_404();

        $this->load->library('email');

    }

    public function index()
    {
        $from_email = "abhishek@example.com";
        $to_email = "vidhi@example.com";

        //Load email library
        $this->load->library('email');

        $this->email->from($from_email, 'Your Name');
        $this->email->to($to_email);
        $this->email->subject('Email Test');
        $this->email->message('Testing the email class.');

        //Send mail
        if($this->email->send())
            echo "Email sent successfully.";
        else
            echo "Error in sending Email.";
        $this->load->view('welcome_message');
    }

    public function send_queue()
    {
        $this->email->send_queue();
    }

    public function retry_queue()
    {
        $this->email->retry_queue();
    }
}