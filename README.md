# UserAssetLib
PHP library to use UserAsset

Usage:

	require 'src/UserAsset.php';
	
	$config = [
		'auth_name' => 'Nastar',
		'auth_password' => '123456',
		'api_url' => 'http://userasset.zii.com/api.php'
	];
	
	$ua = new phamloc\UserAsset\UserAsset($config);
	$result = $ua->putObject([
		'namespace'		=> 'upload',
		'object_name'	=> '2.jpg',
	//	'file'			=> '40.jpg',
		'url'			=> "https://taigame.org/ss",
		'optimize'		=> true
	]);
	
	print_r($result);
