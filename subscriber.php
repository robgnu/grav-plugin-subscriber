<?php
namespace Grav\Plugin;
use \Grav\Common\Plugin;
class SubscriberPlugin extends Plugin
{

    protected $getParams = array();
    protected $validActions = array("subscribe", "unsubscribe");
    protected $action = false;
    protected $email = false;

    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onTwigExtensions' => ['onTwigExtensions', 0]
        ];
    }
    /**
     * Initialize configuration
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            $this->active = false;
            return;
        }

        $this->enable([
            'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
        ]);
    }
    /**
     * Set needed variables to display passwords.
     */
    public function onTwigSiteVariables()
    {
        if ($this->config->get('plugins.subscriber.built_in_css')) {
            $this->grav['assets']->add('plugin://subscriber/css/subscriber.css');
        }
    }
    /**
     *
     */
    public function onTwigExtensions()
    {
        $this->getParams = $_GET; // Save GET Params
        // Validate GET-Parameters
        $this->validateSubscriberParams();
        // Check values for errors -> returns an empty string or an error message.
        $outputMessage = $this->checkSubscriberParams();
        if (!empty($this->action) && !empty($this->email) && empty($outputMessage)) {
          // no error in params -> proceed with e-mail notification
          if ($this->sendEmailNotification()) {
            // e-mail sent succesfully -> display message to the user.
            switch ($this->action) {
              case "subscribe";
                $outputMessage = $this->grav['language']->translate('PLUGIN_SUBSCRIBER.MSG_SUBSCRIBE_THANKYOU');
              break;
              case "unsubscribe";
                $outputMessage = $this->grav['language']->translate('PLUGIN_SUBSCRIBER.MSG_UNSUBSCRIBE_THANKYOU');
              break;
            } // switch
          } else {
            // error while sending e-mail. -> display error to the user.
            $outputMessage = $this->grav['language']->translate('PLUGIN_SUBSCRIBER.MSG_ERROR_NOACTION');
          }
        }
        require_once(__DIR__ . '/twig/subscriber.twig.php');
        $this->grav['twig']->twig->addExtension(new subscriberTwigExtension($outputMessage));
    }


    /**
     * Validates and saves the input params
     */
    protected function validateSubscriberParams() {
      if (isset($this->getParams['action']) && !empty($this->getParams['action'])) {
        $this->action = filter_var($this->getParams['action'], FILTER_SANITIZE_STRING);
      }
      if (isset($this->getParams['email']) && !empty($this->getParams['email'])) {
        $this->email = filter_var($this->getParams['email'], FILTER_SANITIZE_EMAIL);
        $this->email = filter_var($this->email, FILTER_VALIDATE_EMAIL);
      }
    }


    /**
     * Checks the input params
     * The return value is the error message or an empty string.
     *
     * @return string
     */
    protected function checkSubscriberParams() {
      // No parameters -> no output to the user
      if (!$this->action && !$this->email) { return ""; }
      // Check action param
      if (!$this->action || !in_array($this->action, $this->validActions) ) {
        return $this->grav['language']->translate('PLUGIN_SUBSCRIBER.MSG_ERROR_NOACTION');
      }
      // Check e-mail address
      if (!$this->email) {
        return $this->grav['language']->translate('PLUGIN_SUBSCRIBER.MSG_ERROR_NOMAIL');
      }
      return "";
    }


    /**
     * Sends email notification to the configured address.
     */
    protected function sendEmailNotification() {
      // Create the subject
      $subject  = $this->grav['language']->translate('PLUGIN_SUBSCRIBER.EMAIL_SUBJECT');
      // Create the content
      $content  = "<p>".$this->grav['language']->translate('PLUGIN_SUBSCRIBER.EMAIL_CONTENT_TEASER')."</p>";
      $content .= "<p>";
      $content .= "<b>".$this->grav['language']->translate('PLUGIN_SUBSCRIBER.EMAIL_CONTENT_SITE').":</b> ".$this->grav['config']->get('site.title');
      $content .= "<br/>";
      $content .= "<b>".$this->grav['language']->translate('PLUGIN_SUBSCRIBER.EMAIL_CONTENT_ACTION').":</b> ".$this->action;
      $content .= "<br/>";
      $content .= "<b>".$this->grav['language']->translate('PLUGIN_SUBSCRIBER.EMAIL_CONTENT_ADDRESS').":</b> ".$this->email;
      $content .= "</p>";
      // DEBUG-Code: $content .= "<p>".print_r($_GET, true)."</p>";
      $message  = $this->grav['Email']->message($subject, $content, 'text/html')
            ->setFrom($this->grav['config']->get('plugins.subscriber.email_from'))
            ->setTo($this->grav['config']->get('plugins.subscriber.email_to'));
      $sent = $this->grav['Email']->send($message);
      return $sent;
    }

}
?>
