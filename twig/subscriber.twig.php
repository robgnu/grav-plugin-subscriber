<?php
namespace Grav\Plugin;
class subscriberTwigExtension extends \Twig_Extension
{
    protected $message;
    
    function __construct($message) {
      $this->message = $message;
    }
    
    public function getName()
    {
        return 'subscriberTwigExtension';
    }
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('subscriber', [$this, 'subscriberFunction'])
        ];
    }
    public function subscriberFunction()
    {
      if (empty($this->message)) { return ""; }
      return (string) "<p class=\"grav-plugin-subscriber message\">".$this->message."</p>";
    }

}
?>
