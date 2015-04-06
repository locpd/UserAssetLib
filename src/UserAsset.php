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
	
	/**
	 * Create an object in UserAsset service
	 * 
	 * @param string $namespace
	 * @param string $object_name
	 * @param string $file_location Location of file to be used as object
	 * @param boolean $optimize whether to optimize image
	 * @return array response from UserAsset service
	 */
	public function putObject($namespace, $object_name, $file_location, $optimize = true)
	{
		$data = [
			'auth' => json_encode($this->auth),
			'method' => 'post'
		];
		$params = [
			'namespace'		=> (string) $namespace,
			'object_name'	=> (string) $object_name,
			'optimize'		=> (boolean) $optimize
		];
		
		if ( strpos($file_location, 'http://') === 0 || strpos($file_location, 'https://') === 0 ) {
			$params['url'] = $file_location;
		} else {
			$data['file'] = new \CURLFile($file_location);
		}
		
		$data['params'] = json_encode($params);
		
		return $this->request($data);
	}
	
	/**
	 * @param mixed $uri Uri can be an array ['namespace value', 'object value'] or a string 'http://example.com/image.jpg'
	 * @param array $transforms with these options:
	 *	- width: integer indicating max width of image object
	 *	- height: integer indicating max height of image object
	 *	- left: 'left', 'center', 'right'
	 *	- top: 'top', 'middle', 'bottom'
	 * @return string url of object
	 */
	public function getUrl($uri, array $transforms = [])
	{
		if (is_array($uri)) {
			if (count($uri) !== 2) {
				throw new \InvalidArgumentException('uri must have 2 elements for namespace and object name');
			}
			$uri = "{$uri[0]}/{$uri[1]}";
		}
		
		$secret = $this->getHashKey($this->auth['name'], $this->auth['password'], $uri, $transforms);
		$params = [
			'k'		=> $secret,
			'uri'	=> $uri,
			'w'		=> empty($transforms['width']) ? null : $transforms['width'],
			'h'		=> empty($transforms['height']) ? null : $transforms['height'],
			'l'		=> empty($transforms['left']) ? null : $transforms['left'],
			't'		=> empty($transforms['top']) ? null : $transforms['top']
		];
		return $this->apiUrl.'/view.php?'.http_build_query($params);
	}
	
	public function deleteObject($namespace, $object)
	{
		$data = [
			'auth'		=> json_encode($this->auth),
			'method'	=> 'delete',
			'params'	=> json_encode(['namespace' => $namespace, 'object_name' => $object])
		];
		
		return $this->request($data);
	}

	/**
	 * Hash input params of an object with its transforms
	 * 
	 * @param string $auth_name
	 * @param string $auth_password
	 * @param string $uri 'namespace/object' or 'http://example.com/image.jpg'
	 * @param array $transforms ['width' => 100, 'top' => 'middle'] - order of transform keys does not matter
	 * @return string Hash result
	 */
	private function getHashKey($auth_name, $auth_password, $uri, array $transforms)
	{
		$all_transform_props = ['width', 'height', 'left', 'top'];
		$ordered_transform_options = [];
		foreach ($all_transform_props as $prop) {
			if ( ! empty($transforms[$prop]) ) {
				$ordered_transform_options[$prop] = $transforms[$prop];
			}
		}

		$key = "$auth_name|$auth_password|$uri|".json_encode($ordered_transform_options);
		$hash = substr(md5($key), -4);

		return $hash;
	}

	private function request($data)
	{
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $this->apiUrl.'/api.php');
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