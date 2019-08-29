<?php

use Smartlook\Webapi\Client;


require __DIR__ . '/../../../../system/library/smartlook/vendor/autoload.php';

class ControllerExtensionModuleSmartlook extends Controller
{

	const SETTING_NAME = 'smartlook';
	const AUTH_KEY = 'a750a570cb92490d0928bb71b460fdb99c059e2d';

	public function index()
	{
		$this->load->language('extension/module/smartlook');
		$this->load->model('setting/setting');

		$settings = $this->model_setting_setting->getSetting(self::SETTING_NAME);
		$message = NULL;
		$formAction = NULL;

		if (isset($_GET['action'])) {
			switch ($_GET['action']) {
				case 'disable':
					$this->model_setting_setting->deleteSetting(self::SETTING_NAME);
					break;
				case 'login':
				case 'register':
					$api = new Client;
					$result = $_GET['action'] === 'register' ?
						$api->signUp(array('authKey' => self::AUTH_KEY, 'email' => $_POST['email'], 'password' => $_POST['password'], 'lang' => $this->language->get('code'),)) :
						$api->signIn(array('authKey' => self::AUTH_KEY, 'email' => $_POST['email'], 'password' => $_POST['password'],));

					if ($result['ok']) {
						$projectId = NULL;
						$chatKey = NULL;
						if ($_GET['action'] === 'register') {
							$api->authenticate($result['account']['apiKey']);
							$project = $api->projectsCreate(array(
								'name' => $this->config->get('config_store'),
							));
							$projectId = $project['project']['id'];
							$chatKey = $project['project']['key'];
						}
						$this->model_setting_setting->editSetting(self::SETTING_NAME, array(
							self::SETTING_NAME . 'firstRun' => TRUE,
							self::SETTING_NAME . 'email' => $result['user']['email'],
							self::SETTING_NAME . 'chatId' => $result['account']['apiKey'],
							self::SETTING_NAME . 'chatKey' => $chatKey,
							self::SETTING_NAME . 'customCode' => '',
							self::SETTING_NAME . 'projectId' => $projectId,
						));
					} else {
						$message = $result['error'];
						$formAction = $_GET['action'] === 'register' ? NULL : 'login';
						$data['email'] = $_POST['email'];
					}

					break;
				case 'update':
					$api = new Client;
					$settings = $this->model_setting_setting->getSetting(self::SETTING_NAME);
					$api->authenticate($settings[self::SETTING_NAME . 'chatId']);
					$project = $_POST['project'];
					if (substr($project, 0, 1) === '_') {
						$project = $api->projectsCreate(array(
							'name' => substr($project, 1),
						));
					} else {
						$project = $api->projectsGet(array(
							'id' => $project,
						));
					}
					$settings[self::SETTING_NAME . 'projectId'] = $project['project']['id'];
					$settings[self::SETTING_NAME . 'chatKey'] = $project['project']['key'];
					$this->model_setting_setting->editSetting(self::SETTING_NAME, $settings);
					break;
			}
		}

		$that = $this;
		$data = array();
		$data['translator'] = new SmartlookModuleExtensionTranslator($_ = function ($text) use ($that) {
			return $that->language->get($text);
		});

		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$base = HTTPS_SERVER;
		} else {
			$base = HTTP_SERVER;
		}

		$base = rtrim($base, '/\\');

		$this->document->addScript($base . '/view/javascript/smartlook.js');
		$this->document->addStyle($base . '/view/stylesheet/smartlook.css');
		$this->document->setTitle($title = $_('headingTitle'));

		$data['project'] = NULL;
		$data['projects'] = NULL;
		$settings = $this->model_setting_setting->getSetting(self::SETTING_NAME);

		if (isset($settings[self::SETTING_NAME . 'chatId'])) {
			$api = new Client;
			$api->authenticate($settings[self::SETTING_NAME . 'chatId']);
			$result = $api->projectsList();
			$data['projects'] = $result['projects'];
			if ($settings[self::SETTING_NAME . 'projectId']) {
				$data['project'] = $settings[self::SETTING_NAME . 'projectId']; //$api->projectsGet(array('id' => $settings[self::SETTING_NAME . 'projectId']));
			}
		}

		if (isset($settings[self::SETTING_NAME . 'email'])) {
			$data['email'] = $settings[self::SETTING_NAME . 'email'];
			$data['enabled'] = TRUE;
		} else {
			$data['enabled'] = FALSE;
		}
		$data['base'] = $base;
		$data['headingTitle'] = $title;

		if (isset($_GET['action'])) {
			$data['header'] = '';
			$data['leftMenu'] = '';
			$data['footer'] = '';
		} else {
			$data['header'] = $this->load->controller('common/header');
			$data['leftMenu'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');
		}

		$data['message'] = $message;
		$data['formAction'] = $formAction;

		$this->response->setOutput($this->load->view('extension/module/smartlook', $data));
	}

    public function install() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_smartlook', ['module_smartlook_status'=>1]);
    }

    public function uninstall() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('module_smartlook_status');
    }
}

class SmartlookModuleExtensionTranslator
{
	private $translateFunc;

	public function __construct($translateFunc)
	{
		$this->translateFunc = $translateFunc;
	}
	
	public function translate($text)
	{
		$tr = $this->translateFunc;
		return $tr($text);
		
	}
}
