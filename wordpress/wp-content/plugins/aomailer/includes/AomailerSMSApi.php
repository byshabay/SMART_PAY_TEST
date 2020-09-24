<?php 
class AomailerSMSApi
{	
	/**
	 * History
	 */
	public $date_start;
	public $date_stop;
	public $state = 'deliver';
	public $originator = 'target';
	public $phone;
	public $operator;
	public $from_hour = '00';
	public $from_minute = '00';
	public $to_hour = '23';
	public $to_minute = '59';
	
	/**
	 * Send
	 */
	public $text_sms;
	public $used_translit;
	public $all_recipients;
	public $pending_recipients;
	public $processing_recipients;
	public $onhold_recipients;
	public $completed_recipients;
	public $canceled_recipients;
	public $refunded_recipients;
	public $failed_recipients;
	public $selection_recipients;
	public $date_send;

	/**
	 * config()
	 */
	public function config()
	{
		$config_path = realpath(AOMP_AOMAILER_DIR) . '/config.php';
		if (file_exists($config_path)) {
			$config = require($config_path);
			
			return $config;
		}
	}
	
	/**
	 * rules() 
	 */
	public function rules() 
	{
		return [
			['date_start, date_stop, date_send', 'Pattern', '/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}/i'],
			['state, originator, operator', 'Pattern', '/^[a-z]{1,20}$/i'],
			['from_hour, from_minute, to_hour, to_minute', 'Pattern', '/^[0-9]{2}$/i'],
			['selection_recipients', 'Pattern', '/^[0-9a-z \+\-\(\)\s\,]{1,}$/i'],
			['phone', 'function', 'cleaningNumber'],
			['used_translit, all_recipients, pending_recipients, processing_recipients, on_hold_recipients, completed_recipients, canceled_recipients, refunded_recipients, failed_recipients', 'Boolean'],
			['text_sms', 'function', 'cleaningTextSms'],
		];
	}
	
	/**
	 * attributes($array=[]) 
	 */
	public function attributes($array=[]) 
	{
		if (!empty($array)) {
			
			$rules = self::rules();
			if (empty($rules) || !is_array($rules)) {
				return false;
			}

			foreach ($array as $key=>$value) {
				
				if (empty($key) || !is_string($key) || !property_exists($this, $key)) {
					continue;
				}

				foreach ($rules as $rule) {

					if (empty($rule[0])) {
						continue;
					}

					$rule[0] = preg_replace('/[\s\r\n]/i', '', $rule[0]);
					$properties = explode(',', $rule[0]);
					
					if (
						empty($properties) || 
						!is_array($properties) || 
						empty($rule[1]) ||
						!is_string($rule[1])
					) {
						continue;
					}

					if (in_array($key, $properties)) {

						if (
							$rule[1]==='function' && 
							!empty($rule[2]) && 
							is_string($rule[2]) && 
							method_exists($this, $rule[2])
						) {
									
							$method = $rule[2];
							$this->$key = $this->$method($value);
		
						} elseif (method_exists('AomailerValidate', 'validate'.$rule[1])) {
			
							$method = 'validate'.$rule[1];
							$param = '';
							if (!empty($rule[2]) && is_string($rule[2])) {
								$param = $rule[2];
							}
							
							if (AomailerValidate::$method($value, $param)) {

								$this->$key = $value;

							} else {
									
								$this->$key = false;
									
							}					
						}	

						break;
					}
				}
			}
		}
	}
	
	/**
	 * getBalance($login='', $passwd='')
	 */
	public function getBalance($login='', $passwd='')
	{
		$data['security'] = [
			'login' => $login,
			'password' => $passwd,
		];

		$xml = self::generateXML($data);
		$answer_xml = self::request($xml, $this->config()['sms_api_url_balance']);

		if (empty($answer_xml)) {
			return ['error'=>__('No Answer', 'aomailer')];
		}
		
		$data = self::parseXML($answer_xml);
		if (empty($data['balance'][0]['value'])) {
			if (!empty($data['error'])) {
				return ['error'=>$data['error']];
			} else {
				return ['error'=>__('No Answer', 'aomailer')];
			}
		}

		return ['error'=>0, 'balance'=>$data['balance'][0]['value'], 'currency'=>$data['balance'][0]['currency']];
	}
	
	/**
	 * getFromName($login='', $passwd='')
	 */
	public function getFromName($login='', $passwd='')
	{
		$data['security'] = [
			'login' => $login,
			'password' => $passwd,
		];
		
		$xml = self::generateXML($data);
		$answer_xml = self::request($xml, $this->config()['sms_api_url_fromname']);

		if (empty($answer_xml)) {
			return ['error'=>__('No Answer', 'aomailer')];
		}
		
		$data = self::parseXML($answer_xml);
		if (empty($data['from_name'])) {
			if (!empty($data['error'])) {
				return ['error'=>$data['error']];
			} else {
				return ['error'=>__('No Answer', 'aomailer')];
			}
		}

		return ['error'=>0, 'from_name'=>$data['from_name']];
	}
	
	/**
	 * getTemplates($login='', $passwd='')
	 */
	public function getTemplates($login='', $passwd='')
	{
		$data['security'] = [
			'login' => $login,
			'password' => $passwd,
		];
		
		$xml = self::generateXML($data);
		$answer_xml = self::request($xml, $this->config()['sms_api_url_templates']);

		if (empty($answer_xml)) {
			return ['error'=>__('No Answer', 'aomailer')];
		}

		$data = self::parseXML($answer_xml);
		if (empty($data['templates'])) {
			if (!empty($data['error'])) {
				return ['error'=>$data['error']];
			} else {
				return ['error'=>__('No Answer', 'aomailer')];
			}
		}

		return ['error'=>0, 'templates'=>$data['templates']];
	}
	
	/**
	 * getHistory($data=[])
	 *
	 * stats date_start
	 * stats date_stop
	 * stats state: not_deliver, expired, deliver, partly_deliver
	 * stats originator
	 * stats phone
	 * stats operator
	 * stats from_hour
	 * stats from_minute
	 * stats to_hour
	 * stats to_minute
	 */
	public function getHistory()
	{
		$settings = AomailerDB::aomp()->loadSettings('history');
		if (empty($settings['login']) || empty($settings['passwd'])) {
			return false;
		}	

		$stat = [
			'security' => [
				'login' => $settings['login'],
				'password' => $settings['passwd'],
			],
			'stats' => [
				'date_start'   => !empty($this->date_start) ? $this->date_start : date('Y-m-d'),
				'date_stop'    => !empty($this->date_stop) ? $this->date_stop : date('Y-m-d'),
				'state'        => $this->state,
				'originator'   => $this->originator,
				'phone'        => !empty($this->phone) ? $this->phone : '',
				'operator'     => !empty($this->operator) ? $this->operator : '',
				'from_hour'    => $this->from_hour,
				'from_minute'  => $this->from_minute,
				'to_hour'      => $this->to_hour,
				'to_minute'    => $this->to_minute,
			],
		];

		$xml = self::generateXML($stat);
		$answer_xml = self::request($xml, $this->config()['sms_api_url_history']);
		
		if (empty($answer_xml)) {
			return ['error'=>__('No Answer', 'aomailer')];
		}

		$data = self::parseXML($answer_xml);
		if (empty($data['stat'])) {
			if (!empty($data['error'])) {
				return ['error'=>$data['error']];
			} else {
				return ['error'=>__('No Answer', 'aomailer')];
			}
		}

		return ['error'=>0, 'stat'=>$data['stat']];
	}

	/**
	 * send($data=[])
	 */
	public function send($data=[])
	{
		$settings = AomailerDB::aomp()->loadSettings('history');
		if (empty($settings['login']) || empty($settings['passwd'])) {
			return false;
		}	

		if (empty($data['message']) || !is_array($data['message'])) {
			return false;
		}	

		$xml = '<?xml version="1.0" encoding="utf-8" ?><request>';
		$inc = 1;
		foreach ($data['message'] as $key=>$value) {
			if (
				empty($value['from_name']) || 
				empty($value['sms_text']) || 
				empty($value['abonents']) || 
				!is_array($value['abonents'])
			) {
				continue;
			}
			
			$xml .= '<message type="sms">';
				$xml .= '<sender>'.$value['from_name'].'</sender>';
				$xml .= '<text>'.$value['sms_text'].'</text>';
				$xml .= '<name_delivery>'.$value['name_delivery'].'</name_delivery>';
				
				foreach ($value['abonents'] as $k=>$val) {
					$validity_period = '';
					if (!empty($val['validity_period'])) {
						$validity_period = 'validity_period="'.$val['validity_period'].'"';
					}
					
					$num_phone = $k + 1;
					$xml .= '<abonent phone="'.$val['number'].'" number_sms="'.$num_phone.'" client_id_sms="'.time().$num_phone.'" time_send="'.$val['time_send'].'" '.$validity_period.' />';					
				}
			$xml .= '</message>';
			$inc++;
		}
		
		$xml .= '<security><login value="'.$data['login'].'" /><password value="'.$data['passwd'].'" /></security></request>';
		$answer_xml = self::request($xml, $this->config()['sms_api_url_send']);

		if (empty($answer_xml)) {
			return ['error'=>__('No Answer', 'aomailer')];
		}

		$results = self::parseXML($answer_xml);
		if (empty($results['send'])) {
			if (!empty($results['error'])) {
				return ['error'=>$results['error']];
			} else {
				return ['error'=>__('No Answer', 'aomailer')];
			}
		}

		return ['error'=>0, 'send'=>$results['send']];
	}

	/**
	 * replaceTag($str='', $data=[])
	 */
	public function replaceTag($str='', $data=[])
	{
		if (empty($data) || empty($this->config()['tag'])) {
			return $str;
		}

		foreach ($this->config()['tag'] as $key=>$value) {
			$search[] = '#'.$value.'#';
			$replace[] = !empty($data[$key]) ? $data[$key] : '';
		}

		return str_replace($search, $replace, $str);
	}
	
	/**
	 * transliterate($str)
	 */
	public function transliterate($str) 
	{
		$for_replacement = [
            'А' => 'A',   'а' => 'a',
            'Б' => 'B',   'б' => 'b',
            'В' => 'V',   'в' => 'v',
            'Г' => 'G',   'г' => 'g',
            'Д' => 'D',   'д' => 'd',
            'Е' => 'E',   'е' => 'e', 
            'Ё' => 'YO',  'ё' => 'yo',
            'Ж' => 'ZH',  'ж' => 'zh',
            'З' => 'Z',   'з' => 'z',
            'И' => 'I',   'и' => 'i', 
            'Й' => 'J',   'й' => 'j', 
            'К' => 'K',   'к' => 'k',
            'Л' => 'L',   'л' => 'l',
            'М' => 'M',   'м' => 'm',
            'Н' => 'N',   'н' => 'n', 
            'О' => 'O',   'о' => 'o', 
            'П' => 'P',   'п' => 'p', 
            'Р' => 'R',   'р' => 'r',
            'С' => 'S',   'с' => 's',
            'Т' => 'T',   'т' => 't', 
            'У' => 'U',   'у' => 'u', 
            'Ф' => 'F',   'ф' => 'f', 
            'Х' => 'H',   'х' => 'h', 
            'Ц' => 'C',   'ц' => 'c', 
            'Ч' => 'CH',  'ч' => 'ch', 
            'Ш' => 'SH',  'ш' => 'sh', 
            'Щ' => 'SCH', 'щ' => 'sch', 
            'Ь' => '',    'ь' => '', 
            'Ы' => 'Y',   'ы' => 'y', 
            'Ъ' => '',    'ъ' => '', 
            'Э' => 'E',   'э' => 'e', 
            'Ю' => 'YU',  'ю' => 'yu', 
            'Я' => 'YA',  'я' => 'ya',            
		]; 
 
		$output = str_replace( 
			array_keys($for_replacement), 
			array_values($for_replacement), $str 
		); 
 
		return $output; 
	}
	
	/**
	 * getFormat($float)
	 */
	public static function getFormat($int=0, $format=0)
	{
		if (empty($int)) {
			return 0;
		}
		
		if (empty($format)) {
			return $int;
		} 
		
		if ($format=='money') {
			
			return @number_format($int, 2, '.', ' ');
			
		} elseif ($format=='number') {
			
			return @number_format($int, 0, ',', ' ');
			
		} elseif ($format=='float') {
			
			return @number_format($int, 2, ',', ' ');
			
		} elseif ($format=='phone') {
			
			$int = preg_replace('/[^0-9]/i', '', $int);
			return preg_replace('/^[^7]/i', '7', $int);

		} else {
			return $int;
		}
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
						<login value="{{login}}" />
						<password value="{{password}}" />
					</security>
				',
				'stats' => '
					<stats 
						date_start="{{date_start}}" 
						date_stop="{{date_stop}}" 
						state="{{state}}" 
						originator="{{originator}}" 
						phone="{{phone}}" 
						operator="{{operator}}" 
						from_hour="{{from_hour}}" 
						from_minute="{{from_minute}}" 
						to_hour="{{to_hour}}" 
						to_minute="{{to_minute}}" 
					/>
				',
				'message' => '
					<message type="{{type}}">
						<sender>{{sender}}</sender>
						<text>{{text}}</text>
						<abonent 
							phone="{{phone}}" 
							number_sms="{{number_sms}}" 
							client_id_sms="{{client_id_sms}}" 
							time_send="{{time_send}}" 
							validity_period="{{validity_period}} 
						/>
					</message>
				',
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
			
			if ($value['tag']=='SMS') {
				
				$data['sms'][] = [
					'area' => $value['attributes']['AREA'],
					'value' => $value['value'],
				];

			}
			
			if ($value['tag']=='MONEY') {
				
				$data['balance'][] = [
					'currency' => $value['attributes']['CURRENCY'],
					'value' => $value['value'],
				];

			}
			
			if ($value['tag']=='ORIGINATOR') {
				
				if ($value['attributes']['STATE']!=='completed') {
					continue;
				}

				$data['from_name'][] = [
					'value' => $value['value'],
				];

			}
			
			if ($value['tag']=='PATTERN') {

				$data['templates'][$value['attributes']['ID_PATTERN']] = [
					'value' => $value['value'],
					'name' => $value['attributes']['NAME'],
				];

			}
			
			if ($value['tag']=='INFORMATION') {
				$data['send'][] = [
					'attributes' => [
						'num' => $value['attributes']['NUMBER_SMS'],
						'id' => $value['attributes']['ID_SMS'],
						'turn' => $value['attributes']['ID_TURN'],
						'parts' => $value['attributes']['PARTS'],
					],
					'value' => $value['value'],
				];
			}
			
			if ($value['tag']=='ERROR') {
				
				$data['error'] = $value['value'];
				
			}
			
			if ($value['tag']=='STATS') {
				
				if (!empty($value['attributes']) && $value['attributes']['NUM_STATS']==0) {
					return $data;
				}

			} 

			if ($value['tag']=='STAT') {
				$data['stat'][] = [
					'id' => $value['attributes']['ID_SMS'],
                    'state' => $value['attributes']['ID_STATE'],
                    'operator' => $value['attributes']['OPERATOR'],
                    'delivery' => $value['attributes']['NAME_DELIVERY'],
                    'phone' => $value['attributes']['PHONE'],
                    'originator' => $value['attributes']['ORIGINATOR'],
                    'time_state' => $value['attributes']['TIME_CHANGE_STATE'],
                    'time' => $value['attributes']['TIME'],
                    'status'=> $value['attributes']['STATUS'],
                    'status_title' => $value['attributes']['STATUS_TRANSLATE'],
                    'text' => $value['attributes']['TEXT'],
                    'price' => $value['attributes']['PRICE'],
                    'parts' => $value['attributes']['NUM_PARTS'],
                    'partno' => $value['attributes']['PART_NO'],
				];
			} 
		}
		
		return $data;
	}
	
	/**
	 * cleaningTextSms()
	 */
	private function cleaningTextSms($str='')
	{
		if (empty($str) || !is_string($str)) {
			return '';
		}

		$str = strip_tags($str);
		$str = htmlspecialchars($str);
		$str = htmlentities($str);
		
		return $str;
	}
	
	/**
	 * cleaningNumberArray($array=[])
	 */
	private function cleaningNumber($number='')
	{
		if (preg_match('/^[0-9 \+\-\(\)]{1,}$/i', $number)) {
				
			$number = preg_replace('/[\+\-\(\)\s]/i', '', $number);
			if (strlen($number)>15 || strlen($number)<9) {
				return false;
			}

		} elseif (preg_match('/^[0-9a-z \+\-\(\)]{1,}$/i', $number)) {	
				
			$number = preg_replace('/[\+\-\(\)\s]/i', '', $number);
			if (strlen($number)>11 || strlen($number)<5) {
				return false;
			}
				
		} else {
			return false;
		}

		return $number;
	}

	/**
	 * request($xml, $url)
	 */
	private function request($xml, $url)
	{
		$response = wp_remote_post($url, [
			'method'      => 'POST',
			'headers'     => 'Content-type: text/xml; charset=utf-8',
			'body'        => $xml
		]);

		if (is_wp_error($response)) {
			return ['error'=>$response->get_error_message()];
		} else {
			$result = $response['body'];
		}
		
		return $result;
	}

	/**
	 * aomp($className=__CLASS__)
	 */ 
	public static function aomp($className=__CLASS__)
	{
		return new $className();
	}
}
