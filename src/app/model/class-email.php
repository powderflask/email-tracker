<?php
/**
 * UTrackIt - tracking technologies tutorial
 * -----------------------------------------
 * 
 * Basic email class
 *   - configure and send HTML formatted messages.
 *  
 * Version: 0.1
 * Author: backupbrain
 * Author URI: https://github.com/backupbrain/send-html-email-php
 * License: GPL3 see license.txt
 */

class Email {
    var $recipient,
        $sender,
        $subject,
        $message_text,
        $message_html,
        $tracking_key;

    const LINE_BREAK ="\n";
    const SEND_OK = TRUE;
    const SEND_FAIL = FALSE;
    const DEFAULT_RECIP = 'Johnny Appleseed <johnnny@example.com>';
    const DEFAULT_SENDER = 'notifier@driftwoodcove.ca';

    public function __construct($recipient=self::DEFAULT_RECIP, $sender=self::DEFAULT_SENDER) {
        $this->sender = $sender;
        $this->recipient = $recipient;
        $this->tracking_key = sha1($recipient);
        $this->set_default_message();
    }

    public function set_default_message() {
        $this->subject = "This email is being tracked!";
        $this->message_text = "
            Hello!
            Your email client is not showing the HTML version of this email.
            That means you are NOT being tracked!
            Have a great day.
        ";
        $this->message_html = "
            <h1>Hello!</h1>
            <p>This email contains a hidden tracking code.
               If your email client loaded the remote web beacon in this email, you've been tracked!</p>
            <p>To see the tracking information collected about you, see :
            ".HACKERS_ANCHOR."</p>
            <p>Your email address is associated with tracking code:
            ".$this->tracking_key."</p>
            <p>This tracking software is for educational purposes only, and none of
               your personal data has been compromised by this email tracker.</p>
        ";
        $this->add_tracking_code();
        $this->add_tracking_link();
        $this->wrap_html();
    }

    private function wrap_html() {
        $this->message_html = "
            <html>
             <body>
          " . $this->message_html ."
             </body>
            </html>
          ";
    }

    private function add_tracking_link() {
        $path = CLICK_TRACKING_URL . '?tk='.urlencode($this->tracking_key).'&url='.urlencode(siteURL());
        $link = "
            <p>
                <a href='". $path ."' title='Click Tracker'>This link tracks when and where you clicked it!</a>
            </p>
        ";
        $this->message_html = $this->message_html.$link;
    }

    private function add_tracking_code() {
        $this->message_html = $this->message_html."<img src='".TRACKING_URL."?tk=".urlencode($this->tracking_key)."'>";
    }

    /**
     * Make text rfj2047 compliant
     * Convert HTML character entities into ISO-8859-1,
     * then to Base64 for rfc2047 email subject compatibility.
     */
    private static function rfc2047_sanitize($text) {
        $output = mb_encode_mimeheader(
            html_entity_decode(
                $text,
                ENT_QUOTES,
                'ISO-8859-1'),
            'ISO-8859-1','B',"\n");
        return $output;
    }

    /**
     * Set this Email object
     **/
    public function send( ) {
        // let's create the headers to show where the email
        // originated from.
        $headers[] = "From: ".$this->sender;
        $headers[] = "Reply-To: ".$this->sender;

        // Subjects are tricky.
        // Even some sophisticated email clients don't understand unicode subject lines.
        $subject = self::rfc2047_sanitize($this->subject);

        $message = "";

        // if the email is HTML, then let's tell the MTA about the mime-type and all that
        // see https://www.qcode.co.uk/post/70  for RFC 5332 standard
        if ($this->message_html) {
            // Single part html - simple
            $headers[] = "Content-Type: text/html; charset=utf-8";
            $headers[] = "Content-Transfer-Encoding: 8bit";   // ENCODING MATTERS!! for URL query strings
            $message .= Email::LINE_BREAK;
            $message .= $this->message_html;

            // multi-part messages are a pain!  Ugh.  Maybe try: https://github.com/PHPMailer/PHPMailer
            // this works on some mail clients, but doesn't seem universal.  No idea why.
            /*
            $mime_boundary = "X3ctmeXmJ4ww=_?:";
            // set up a mime boundary so that we can encode
            // the email inside it, hiding it from clients
            // that can only read plain text emails
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/alternative; boundary=\"".$mime_boundary."\" ";

            $message .= Email::LINE_BREAK;

            $message .= "--".$mime_boundary.Email::LINE_BREAK;
            $message .= "Content-Transfer-Encoding: 8BIT".Email::LINE_BREAK;
            $message .= "Content-Type: TEXT/PLAIN;\n   charset=utf-8".Email::LINE_BREAK;
            $message .= Email::LINE_BREAK;
            $message .= $this->message_text;
            $message .= Email::LINE_BREAK;

            $message .= "--".$mime_boundary.Email::LINE_BREAK;
            $message .= "Content-Transfer-Encoding: 8BIT".Email::LINE_BREAK;
            $message .= "Content-Type: TEXT/HTML;\n   charset=utf-8".Email::LINE_BREAK;
            $message .= Email::LINE_BREAK;
            $message .= $this->message_html;
            $message .= Email::LINE_BREAK;

            $message .= "--".$mime_boundary;
            */
        } else {
            /* Single part plain text - simple */
            $headers[] = "Content-type: TEXT/PLAIN; charset=utf-8".Email::LINE_BREAK;
            // $message .= "Content-Transfer-Encoding: quoted-printable".Email::LINE_BREAK;
            $message .= Email::LINE_BREAK;
            $message .= $this->message_text;
        }


        // try to send the email.
        $result = mail( $this->recipient,
            $subject,
            $message,
            implode(Email::LINE_BREAK,$headers)
        );

        return $result?self::SEND_OK:self::SEND_FAIL;
    } // send

}

?>