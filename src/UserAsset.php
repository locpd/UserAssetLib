<?php

namespace phamloc\UserAsset;

class UserAsset
{
	private $auth, $apiUrl;
	
	public function __construct(array $config)
	{
		if ( ! isset($config['auth_name']) || ! isset($config['auth_password']) || ! isset($config['api_url']) ) {
			throw new \InvalidArgumentException('Insufficient config params');
		}
		
		$this->auth = [
			"name"		=> $config['auth_name'],
			"password"	=> $config['auth_password']
		];
		$this->apiUrl = $config['api_url'];
	}
	
	public function putObject(array $params)
	{
		if ( empty($params['namespace']) || empty($params['object_name']) ) {
			throw new \InvalidArgumentException('namespace, object_name are required params');
		}
		
		if ( empty($params['file']) && empty($params['url']) && empty($params['body']) ) {
			throw new \InvalidArgumentException('file, url, or body param must be set');
		}
		
		$data = [
			'auth' => json_encode($this->auth),
			'method' => 'post'
		];
		
		if ( ! empty($params['file']) ) {
			$data['file'] = new \CURLFile($params['file']);
			unset($params['file']);
		}
		
		$data['params'] = json_encode($params);
		
		return $this->request($data);
	}

    private function request($data)
	{
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $this->apiUrl);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_FAILONERROR, 1);

		$response = curl_exec($curl);
		$result = json_decode($response, true);
		
		$error_code = curl_errno($curl);
		curl_close($curl);
		
		if ( $error_code !== CURLE_OK ) {
			throw new \RuntimeException(sprintf('cURL returned with the following error code: "%s"', $error_code));
		}
		
		if (empty($result)) {
			throw new \Exception('UserAsset api return invalid response: '.$response);
		}

		return $result;
	}

}