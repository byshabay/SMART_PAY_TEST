<?php 
class AomailerEmailApi
{	
	public $config = [];
	
	public function __construct($is_plugin_page=false)
	{
		$config_path = realpath(AOMP_AOMAILER_DIR) . '/config.php';
		if (file_exists($config_path)) {
			$this->config = require($config_path);
		}
	}
	
	/**
	 * send($data=[])
	 */
	public function send($data=[], $security=[])
	{
		if(
			empty($security['login']) || 
			empty($security['passwd']) || 
			empty($data['letter']['from']) || 
			empty($data['letter']['subject']) || 
			empty($data['letter']['to'])
		) {
			return false;
		}
		
		$stat = [
			'security' => [
				'login' => $security['login'],
				'password' => $security['passwd'],
			],
			'from' => [
				'from' => $data['letter']['from'],
			],
			'email' => [
				'type' => 2,
				'name_delivery' => 'WordPress_order',
				'subject' => $data['letter']['subject'],
				'to' => $data['letter']['to'],
				'id_message' => '',
				'data' => self::encode($data),
			],
		];	

		$xml = self::generateXML($stat);
		
		$result = self::request($xml);
		if (!empty($result) && is_array($result)) {
			if (!empty($result['error'])) {
				return false;
			} elseif (!empty($result['success'])) {
				return true;
			}
		}
	}
	
	/**
	 * encode($str)
	 */
	private function encode($str)
	{
		if (is_array($str)) {
			$str = serialize($str);
		}
		
		return base64_encode($str);
	}
	
	/**
	 * request($xml, $url)
	 */
	private function request($xml)
	{
		$response = wp_remote_post($this->config['email_api_url'], [
			'method'      => 'POST',
			'headers'     => 'Content-type: text/xml; charset=utf-8',
			'body'        => $xml
		]);
		
		if (is_wp_error($response)) {
			return ['error'=>$response->get_error_message(), 'success'=>0];
		} else {
			$answer = $response['body'];
		}

		if (!empty($answer)) {
			
			$res = self::parseXML($answer);

			if (!empty($res['error'])) {
				foreach ($res['error'] as $val) {
					if (!empty($val['value'])) {
						$err .= $val['value'].' ';
					}
				}
			}

			if (!empty($err)) {
				$err = __('Price', 'aomailer').$err;
				return ['error'=>$err, 'success'=>0];
			} else {
				foreach ($res['success'] as $val) {
					if (!empty($val['value'])) {
						$succ .= $val['value'].' ';
					}
				}
				if (!empty($succ)) {
					$succ = __('Failed to send email notification', 'aomailer').$succ;
				}

				return ['error'=>0, 'success'=>$succ];
			}
			
			return ['error'=>1, 'success'=>0];
		}
	}
	/** 
	 * viewNotice($message, $type)
	 */
	private function viewNotice($message, $type)
	{
		$class = '';
		if ($type=='error') {
			$class = 'error';
		} elseif ($type=='success') {
			$class = 'success';
		}

		return '
			<div class="notice notice-'.$class.' is-dismissible">
				<p>'.$message.'</p>
			</div>
		';
	}
	
	/** 
	 * generateXML($xml)
	 */
	private function generateXML($data)
	{
		$block_xml = [
			'title' => '<?xml version="1.0" encoding="utf-8" ?>',
			'request' => [
				'security' => '
					<security>
						<login>{{login}}</login>
						<password>{{password}}</password>
					</security>
				',
				'from' => '
					<from>{{from}}</from>
				',
				'email' => '
					<email type="{{type}}" name_delivery="{{name_delivery}}" to="{{to}}" subject="{{subject}}" id_message="{{id_message}}">
						<letter>{{data}}</letter>
					</email>
				'
			],
		];
					
		foreach ($block_xml as $key=>$value) {
			
			if ($key=='title') {
				$xml = $value;	
			} elseif ($key=='request') {
				$xml .= '<'.$key.'>';
				foreach ($value as $name=>$option) {
					if (!empty($data[$name])) {
						foreach ($data[$name] as $tag=>$v) {
							$option = str_replace('{{'.$tag.'}}', $v, $option);
						}
						
						$xml .= $option;
					}
				}
				$xml .= '</'.$key.'>';
			}	
		}	
		
		$xml = preg_replace('/(>[\s]{1,}\<)/', '><', $xml);
		$xml = preg_replace('/[\s]{2,}/', ' ', $xml);

		return $xml;
	}
	
	/** 
	 * parseXML($xml)
	 */
	private function parseXML($xml)
	{
		$p = xml_parser_create();
		xml_parse_into_struct($p, $xml, $vals, $index);

		$data = [];
		foreach ($vals as $value) {
			
			if ($value['tag']=='RESPONSE') {
				continue;
			}
			
			if ($value['tag']=='ERROR') {

				$data['error'][] = [
					'value' => $value['value'],
				];

			}
			
			if ($value['tag']=='SUCCESS') {
				
				$data['success'][] = [
					'value' => $value['value'],
				];

			}
		}
		
		return $data;
	}

	/**
	 * aomp($is_plugin_page=false, $className=__CLASS__)
	 */ 
	public static function aomp($is_plugin_page=false, $className=__CLASS__)
	{
		return new $className($is_plugin_page);
	}
}
