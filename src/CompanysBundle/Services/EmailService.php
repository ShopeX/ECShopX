<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CompanysBundle\Services;

class EmailService
{
    /* Public Variables */
    public $smtp_port; //smtp_port 端口号
    public $time_out;
    public $host_name; //服务器主机名
    public $relay_host; //服务器主机地址
    public $debug;
    public $auth; //验证
    public $sender; //发件人
    public $user; //服务器用户名
    public $pass; //服务器密码
    /* Private Variables */
    public $sock;

    /* Constractor 构造方法*/
    public function __construct($from = null)
    {
        if ($from) {
            $this->smtp_port = $from['email_smtp_port'];
            $this->relay_host = $from['email_relay_host'];
            $this->user = $from['email_user'];
            $this->sender = $from['email_user'];
            $this->pass = $from['email_password']; 
        } else {
            $this->smtp_port = config('common.email_smtp_port');
            $this->relay_host = config('common.email_relay_host');
            $this->user = config('common.email_user');
            $this->sender = config('common.email_sender');
            $this->pass = config('common.email_password');
        }

        $this->debug = false;
        $this->auth = true; //auth
        $this->time_out = 30; //is used in fsockopen()
        $this->host_name = "localhost"; //is used in HELO command
        // $this->host_name = "smtp.163.com"; //is used in HELO command
        $this->sock = false;
    }

    /* Main Function */
    public function sendmail($to, $subject = "", $body = "", $mailtype = "HTML", $cc = "", $bcc = "", $additional_headers = "")
    {
        $header = "";
        // $mail_from = $this->get_address($this->strip_comment($this->user));
        $mail_from = $this->sender;
        $body = mb_ereg_replace("(^|(\r\n))(\\.)", "\\1.\\3", $body);
        $header .= "MIME-Version:1.0\r\n";
        if ($mailtype == "HTML") { //邮件发送类型
            //$header .= "Content-Type:text/html\r\n";
            $header .= 'Content-type: text/html; charset=utf-8'."\r\n";
        }
        $header .= "To: ".$to."\r\n";
        if ($cc != "") {
            $header .= "Cc: ".$cc."\r\n";
        }

        $header .= "From: ".$this->sender."\r\n";
        // $header .= "From: $this->user<".$this->user.">\r\n";    //这里只显示邮箱地址，不够人性化

        $header .= "Subject: ".$subject."\r\n";
        $header .= $additional_headers;
        $header .= "Date: ".date("r")."\r\n";
        $header .= "X-Mailer:By (PHP/".phpversion().")\r\n";

        list($msec, $sec) = explode(" ", microtime());

        $header .= "Message-ID: <".date("YmdHis", $sec).".".($msec * 1000000).".".$mail_from.">\r\n";
        $TO = explode(",", $this->strip_comment($to));

        if ($cc != "") {
            $TO = array_merge($TO, explode(",", $this->strip_comment($cc))); //合并一个或多个数组
        }

        if ($bcc != "") {
            $TO = array_merge($TO, explode(",", $this->strip_comment($bcc)));
        }

        $sent = true;

        foreach ($TO as $rcpt_to) {
            $rcpt_to = $this->get_address($rcpt_to);

            if (!$this->smtp_sockopen($rcpt_to)) {
                $this->log_write("Error: Cannot send email to ".$rcpt_to."\n");
                $sent = false;
                continue;
            }

            if ($this->smtp_send($this->host_name, $mail_from, $rcpt_to, $header, $body)) {
                $this->log_write("E-mail has been sent to <".$rcpt_to.">\n");
            } else {
                $this->log_write("Error: Cannot send email to <".$rcpt_to.">\n");
                $sent = false;
            }

            fclose($this->sock);
            $this->log_write("Disconnected from remote host\n");
        }
        return $sent;
    }

    /* Private Functions */
    private function smtp_send($helo, $from, $to, $header, $body = "")
    {
        if (!$this->smtp_putcmd("HELO", $helo)) {
            return $this->smtp_error("sending HELO command");
        }

        if ($this->auth) {
            if (!$this->smtp_putcmd("AUTH LOGIN", base64_encode($this->user))) {
                return $this->smtp_error("sending HELO command");
            }
            if (!$this->smtp_putcmd("", base64_encode($this->pass))) {
                return $this->smtp_error("sending HELO command");
            }
        }

        if (!$this->smtp_putcmd("MAIL", "FROM:<".$from.">")) {
            return $this->smtp_error("sending MAIL FROM command");
        }

        if (!$this->smtp_putcmd("RCPT", "TO:<".$to.">")) {
            return $this->smtp_error("sending RCPT TO command");
        }

        if (!$this->smtp_putcmd("DATA")) {
            return $this->smtp_error("sending DATA command");
        }

        if (!$this->smtp_message($header, $body)) {
            return $this->smtp_error("sending message");
        }

        if (!$this->smtp_eom()) {
            return $this->smtp_error("sending <CR><LF>.<CR><LF> [EOM]");
        }

        if (!$this->smtp_putcmd("QUIT")) {
            return $this->smtp_error("sending QUIT command");
        }

        return true;
    }

    private function smtp_sockopen($address)
    {
        if ($this->relay_host == "") {
            return $this->smtp_sockopen_mx($address);
        } else {
            return $this->smtp_sockopen_relay();
        }
    }

    private function smtp_sockopen_relay()
    {
        $this->log_write("Trying to ".$this->relay_host.":".$this->smtp_port."\n");
        $this->sock = @fsockopen($this->relay_host, $this->smtp_port, $errno, $errstr, $this->time_out);

        if (!($this->sock && $this->smtp_ok())) {
            $this->log_write("Error: Cannot connenct to relay host ".$this->relay_host."\n");
            $this->log_write("Error: ".$errstr." (".$errno.")\n");
            return false;
        }
        $this->log_write("Connected to relay host ".$this->relay_host."\n");
        return true;
    }

    private function smtp_sockopen_mx($address)
    {
        $domain = preg_replace("/^.+@([^@]+)$/", "\\1", $address);

        if (!@getmxrr($domain, $MXHOSTS)) {
            $this->log_write("Error: Cannot resolve MX \"".$domain."\"\n");
            return false;
        }

        foreach ($MXHOSTS as $host) {
            $this->log_write("Trying to ".$host.":".$this->smtp_port."\n");
            $this->sock = @fsockopen($host, $this->smtp_port, $errno, $errstr, $this->time_out);

            if (!($this->sock && $this->smtp_ok())) {
                $this->log_write("Warning: Cannot connect to mx host ".$host."\n");
                $this->log_write("Error: ".$errstr." (".$errno.")\n");
                continue;
            }

            $this->log_write("Connected to mx host ".$host."\n");
            return true;
        }

        $this->log_write("Error: Cannot connect to any mx hosts (".implode(", ", $MXHOSTS).")\n");
        return false;
    }

    private function smtp_message($header, $body)
    {
        fputs($this->sock, $header."\r\n".$body);
        $this->smtp_debug("> ".str_replace("\r\n", "\n"."> ", $header."\n> ".$body."\n> "));
        return true;
    }

    private function smtp_eom()
    {
        fputs($this->sock, "\r\n.\r\n");
        $this->smtp_debug(". [EOM]\n");
        return $this->smtp_ok();
    }

    private function smtp_ok()
    {
        $response = str_replace("\r\n", "", fgets($this->sock, 512));
        $this->smtp_debug($response."\n");
        if (!mb_ereg("^[23]", $response)) {
            fputs($this->sock, "QUIT\r\n");
            fgets($this->sock, 512);
            $this->log_write("Error: Remote host returned \"".$response."\"\n");
            return false;
        }
        return true;
    }

    private function smtp_putcmd($cmd, $arg = "")
    {
        if ($arg != "") {
            if ($cmd == "") {
                $cmd = $arg;
            } else {
                $cmd = $cmd." ".$arg;
            }
        }

        fputs($this->sock, $cmd."\r\n");
        $this->smtp_debug("> ".$cmd."\n");
        return $this->smtp_ok();
    }

    private function smtp_error($string)
    {
        $this->log_write("Error: Error occurred while ".$string.".\n");
        return false;
    }

    private function log_write($message)
    {
        $this->smtp_debug($message);

        app('log')->info($message);

        return true;
    }

    private function strip_comment($address)
    {
        $comment = "\\([^()]*\\)";

        while (mb_ereg($comment, $address)) {
            $address = mb_ereg_replace($comment, "", $address);
        }
        return $address;
    }

    private function get_address($address)
    {
        $address = mb_ereg_replace("([ \t\r\n])+", "", $address);
        $address = mb_ereg_replace("^.*<(.+)>.*$", "\\1", $address);
        return $address;
    }

    private function smtp_debug($message)
    {
        if ($this->debug) {
            app('log')->debug($message);
        }
    }
}
