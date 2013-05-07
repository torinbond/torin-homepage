<?php
/**
 * Email
 * API for sending email through the server or through third-party services
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
class Email
{
    /**
     * Fields required to be passed into send()
     *
     * @var array
     **/
    public static $required = array('to', 'from', 'subject');

    /**
     * Fields allowed to be passed into send()
     *
     * @var array
     **/
    public static $allowed = array('to', 'from', 'subject', 'cc', 'bcc', 'headers', 'text', 'html', 'mail_handler', 'mail_handler_key');


    /**
     * Available Transactional email services
     *
     * @var array
     **/
    public static $email_handlers = array('postmark', 'mandrill', 'sendgrid', 'mailgun');


    /**
     * Send an email using a Transactional email service
     * or native PHP as a fallback.
     *
     * @param array  $attributes  A list of attributes for sending
     * @return bool on success
     **/
    public static function send($attributes = array())
    {
        /*
        |--------------------------------------------------------------------------
        | Required attributes
        |--------------------------------------------------------------------------
        |
        | We first need to ensure we have the minimum fields necessary to send
        | an email.
        |
        */
        $required = array_intersect_key($attributes, array_flip(self::$required));

        if (count($required) >= 3) {

            /*
            |--------------------------------------------------------------------------
            | Load handler from config
            |--------------------------------------------------------------------------
            |
            | We check the passed data for a mailer + key first, and then fall back
            | to the global Statamic config.
            |
            */
            $email_handler     = array_get($attributes, 'email_handler', Config::get('email_handler', NULL));
            $email_handler_key = array_get($attributes, 'email_handler_key', Config::get('email_handler_key', NULL));

            if (in_array($email_handler, self::$email_handlers) && $email_handler_key) {

                /*
                |--------------------------------------------------------------------------
                | Initialize Stampie
                |--------------------------------------------------------------------------
                |
                | Stampie provides numerous adapters for popular email handlers, such as
                | Mandrill, Postmark, and SendGrid. Each is written as an abstract
                | interface in an Adapter Pattern.
                |
                */
                $mailer = self::initializeEmailHandler($email_handler, $email_handler_key);

                /*
                |--------------------------------------------------------------------------
                | Initialize Message class
                |--------------------------------------------------------------------------
                |
                | The message class is an implementation of the Stampie MessageInterface
                |
                */
                $email = new Message($attributes['to']);

                /*
                |--------------------------------------------------------------------------
                | Set email attributes
                |--------------------------------------------------------------------------
                |
                | I hardly think this requires much explanation.
                |
                */
                $email->setFrom($attributes['from']);

                $email->setSubject($attributes['subject']);

                if (isset($attributes['text'])) {
                    $email->setText($attributes['text']);
                }

                if (isset($attributes['html'])) {
                    $email->setHtml($attributes['html']);
                }

                if (isset($attributes['cc'])) {
                    $email->setCc($attributes['cc']);
                }

                if (isset($attributes['bcc'])) {
                    $email->setBcc($attributes['bcc']);
                }

                if (isset($attributes['headers'])) {
                    $email->setHeaders($attributes['headers']);
                }

                $mailer->send($email);

                return TRUE;

            } else {

                /*
                |--------------------------------------------------------------------------
                | Native PHP Mail
                |--------------------------------------------------------------------------
                |
                | We're utilizing the popular PHPMailer class to handle the messy
                | email headers and do-dads. Emailing from PHP in general isn't the best
                | idea known to man, so this is really a lackluster fallback.
                |
                */

                $email = new PHPMailer(TRUE);
                $email->IsMAIL();
                $email->CharSet = 'UTF-8';

                $email->AddAddress($attributes['to']);
                $email->From    = $attributes['from'];
                $email->Subject = $attributes['subject'];

                if (isset($attributes['text'])) {
                    $email->AltBody = $attributes['text'];
                }

                if (isset($attributes['html'])) {
                    $email->Body = $attributes['html'];
                    $email->IsHTML(TRUE);
                }

                if (isset($attributes['cc'])) {
                    $email->AddCC($attributes['cc']);
                }

                if (isset($attributes['bcc'])) {
                    $email->AddBCC($attributes['bcc']);
                }

                if ($email->Send()) {

                    return TRUE;
                }
            }
        }

        return FALSE;
    }


    /**
     * Instantiates a Stampie Mailer instance
     *
     * @param string  $email_handler  Name of the email handler to use
     * @param string  $email_handler_key  Email Handler Token
     * @return object
     **/
    private static function initializeEmailHandler($email_handler, $email_handler_key)
    {
        $adapter = new Stampie\Adapter\Buzz(new Buzz\Browser());

        if ($email_handler == 'postmark') {
            return new Stampie\Mailer\Postmark($adapter, $email_handler_key);
        } elseif ($email_handler == 'mandrill') {
            return new Stampie\Mailer\Mandrill($adapter, $email_handler_key);
        } elseif ($email_handler == 'sendgrid') {
            return new Stampie\Mailer\SendGrid($adapter, $email_handler_key);
        } elseif ($email_handler == 'mailgun') {
            return new Stampie\Mailer\MailGun($adapter, $email_handler_key);
        }

        Log::error("Could not initialize email handler `" . $email_handler . "`. Unknown service.", "core", "email");
    }
}


class Message extends \Stampie\Message
{
  public $headers = array();
  public $cc = NULL;
  public $bcc = NULL;

  /**
   * @param string $html
   */
  public function setHtml($html)
  {
      $this->html = $html;
  }

  /**
   * @param string $text
   * @throws \InvalidArgumentException
   */
  public function setText($text)
  {
      if ($text !== strip_tags($text)) {
          throw new \InvalidArgumentException('HTML Detected');
      }

      $this->text = $text;
  }

  /**
   * @return string
   */
  public function getHtml()
  {
      return $this->html;
  }

  /**
   * @return string
   */
  public function getText()
  {
      return $this->text;
  }

  /**
   * @param string $html
   */
  public function setFrom($from)
  {
      $this->from = $from;
  }

  /**
   * @return string
   */
  public function getFrom()
  {
      return $this->from;
  }

  /**
   * @param string  $subject  Subject to use
   * @return string
   */

  public function setSubject($subject = NULL)
  {
    $this->subject = $subject;
  }

  /**
   * @return string
   */
  public function getSubject()
  {
    return $this->subject;
  }

  /**
   * @return true
   */
  public function setHeaders($headers = array())
  {
    $this->headers = $headers;
  }

  /**
   * @return true
   */
  public function getHeaders()
  {
      return $this->headers;
  }

  /**
   * @return string
   */
  public function getReplyTo()
  {
      return $this->getFrom();
  }

  /**
  * @return null
  */
  public function setCc($cc = NULL)
  {
      $this->cc = $cc;
  }

  /**
   * @return null
   */
  public function getCc()
  {
      return $this->cc;
  }

  /**
  * @return null
  */
  public function setBcc($bcc = NULL)
  {
      $this->bcc = $bcc;
  }

  /**
   * @return null
   */
  public function getBcc()
  {
      return $this->bcc;
  }
}
