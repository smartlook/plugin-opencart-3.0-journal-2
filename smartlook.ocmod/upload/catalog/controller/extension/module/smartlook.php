<?php

class ControllerExtensionModuleSmartlook extends Controller
{

	const SETTING_NAME = 'smartlook';

	public function index()
	{
		$this->load->language('extension/module/smartlook');

		$this->load->model('setting/setting');
		$settings = $this->model_setting_setting->getSetting(self::SETTING_NAME);

		$data = array(
			'chat' => NULL,
		);
		if (isset($settings[self::SETTING_NAME . 'chatId'])) {
			$data['chat'] = '<script type="text/javascript">
				window.smartlook||(function(d) {
				var o=smartlook=function(){ o.api.push(arguments)},h=d.getElementsByTagName(\'head\')[0];
				var c=d.createElement(\'script\');o.api=new Array();c.async=true;c.type=\'text/javascript\';
				c.charset=\'utf-8\';c.src=\'//rec.smartlook.com/recorder.js\';h.appendChild(c);
				})(document);
				smartlook(\'init\', \'' . $settings[self::SETTING_NAME . 'chatKey'] . '\');';
			if ($this->customer->isLogged()) {
				$data['chat'] .= 'smartlook(\'tag\', \'email\', ' . json_encode($this->customer->getEmail()) . ');';
				$data['chat'] .= 'smartlook(\'tag\', \'name\', ' . json_encode($this->customer->getFirstName() . ' ' . $this->customer->getLastName()) . ');';
			}

			$data['chat'] .= '</script>';
		}
                
                $code = $this->load->view('extension/module/smartlook', $data);
                
                if ($this->config->get('config_template') == 'default') {
                    return $code;
                }
                else {
                    echo $code;                    
                }
	}

}
