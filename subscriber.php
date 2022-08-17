<?php
namespace Grav\Plugin;
use Grav\Common\Filesystem\Folder;
use Grav\Common\GPM\GPM;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Plugin;
use Grav\Common\Filesystem\RecursiveFolderFilterIterator;
use Grav\Common\User\User;
use Grav\Common\Utils;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\Event\Event;
use Symfony\Component\Yaml\Yaml;
class SubscriberPlugin extends Plugin
{

	protected $getParams = array();
	protected $validActions = array("subscribe", "unsubscribe");
	protected $route = 'subscribe';
	protected $action = false;
	protected $email = false;
	protected $mailing = false;

	public static function getSubscribedEvents()
	{
		return [
			'onPluginsInitialized' => ['onPluginsInitialized', 0],
			'onTwigExtensions' => ['onTwigExtensions', 0]
		];
	}

	/**
	 * Admin side initialization
	 */
	public function initializeAdmin()
	{
		/** @var Uri $uri */
		$uri = $this->grav['uri'];

		$this->enable([
			'onTwigTemplatePaths' => ['onTwigAdminTemplatePaths', 0],
			'onAdminMenu' => ['onAdminMenu', 0],
			'onDataTypeExcludeFromDataManagerPluginHook' => ['onDataTypeExcludeFromDataManagerPluginHook', 0],
		]);

		$mailing = $this->getMailings();

		$this->grav['twig']->mailing = $mailing;
		$this->grav['twig']->twig_vars['mailing'] = $mailing;


	}
	/**
	 * Add navigation item to the admin plugin
	 */
	public function onAdminMenu()
	{
		$this->grav['twig']->plugins_hooked_nav['PLUGIN_SUBSCRIBER.LIST'] = ['route' => $this->route, 'icon' => 'fa-file-text'];
	} 
	/**
	 * Exclude subscribe from the Data Manager plugin
	 */
	public function onDataTypeExcludeFromDataManagerPluginHook()
	{
		$this->grav['admin']->dataTypesExcludedFromDataManagerPlugin[] = 'subscribe';
	}
	/**
	 * Add plugin templates path
	 */
	public function onTwigAdminTemplatePaths()
	{
		$this->grav['twig']->twig_paths[] = __DIR__ . '/admin/templates';
	}
	/**
	 * Initialize configuration
	 */
	public function onPluginsInitialized()
	{
		if ($this->isAdmin()) {
			$this->initializeAdmin();
		} else {
			$this->enable([
				'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
			]);
		}
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
			// no error in params -> proceed with save
			if ($this->saveNewSubscriberChoice()) {
				// no error in save -> proceed with e-mail notification
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
		if (isset($this->getParams['mailing']) && !empty($this->getParams['mailing'])) {
			$this->mailing = filter_var($this->getParams['mailing'], FILTER_SANITIZE_STRING);
		} else {
			$this->mailing = filter_var("default", FILTER_SANITIZE_STRING);
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

	/**
	 * Save user choice in file
	 */
	protected function saveNewSubscriberChoice() {
		$filename = DATA_DIR . '/mailing/';
		$filename .= $this->mailing . '.yaml';
		$file = File::instance($filename);

		if (file_exists($filename)){
			$data = Yaml::parse($file->content());

			if ($this->action == "subscribe") {
				$to_add = true;
				foreach( $data['subscribers'] as $sub){
					if ($sub['email'] == $this->email){
						$to_add = false;
						break;
					}
				}
				if ($to_add){
					$data['subscribers'][] = [
						'date' => date('D, d M Y H:i:s', time()),
						'email' => $this->email
					];
				}
			} else {
				$new_data = array(
					'mailing' => $this->mailing,
					'subscribers' => array()
				);
				foreach( $data['subscribers'] as $sub){
					if ($sub['email'] != $this->email){
						$new_data['subscribers'][] = $sub;
					}
				}
				$data = $new_data;
			}

		} else {
			if ($this->action == "subscribe") {
				$data = array(
					'mailing' => $this->mailing,
					'subscribers' => array([
						'date' => date('D, d M Y H:i:s', time()),
						'email' => $this->email
					])
				);
			} else {
				$data = array(
					'mailing' => $this->mailing,
					'subscribers' => array([])
				);
			}
		}

		$file->save(Yaml::dump($data));
		return $file;
	}

	/**
	 * return the files in mailing list folder
	 */
	private function getMailings($path = '') {
		$files = [];
		$data = [];

		if (!$path) {
			$path = DATA_DIR . 'mailing';
		}

		if (!file_exists($path)) {
			Folder::mkdir($path);
		}

		$dirItr     = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
		$filterItr  = new RecursiveFolderFilterIterator($dirItr);
		$itr        = new \RecursiveIteratorIterator($filterItr, \RecursiveIteratorIterator::SELF_FIRST);

		$itrItr = new \RecursiveIteratorIterator($dirItr, \RecursiveIteratorIterator::SELF_FIRST);
		$filesItr = new \RegexIterator($itrItr, '/^.+\.yaml$/i');

		foreach ($filesItr as $filepath => $file) {

			$files[] = (object)array(
				"fileName" => $file->getFilename(),
				"filePath" => $filepath,
				"data" => Yaml::parse(file_get_contents($filepath))
			);
			$data[] = Yaml::parse(file_get_contents($filepath));
		}

		return $data;
	}

}
?>
