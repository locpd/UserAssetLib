PHP library to use UserAsset

Usage:

	require 'src/UserAsset.php';
	
	$config = [
		'auth_name' => 'Nastar',
		'auth_password' => '123456',
		'api_url' => 'http://userasset.zii.com/api.php'
	];
	
	$ua = new phamloc\UserAsset\UserAsset($config);
	$result = $ua->putObject('upload', '2.jpg', "http://www.twinfinite.net/wp-content/uploads/2014/08/AC-feature.jpg", true);
	print_r($result);

	echo $ua->getUrl(['upload', '2.jpg']);

	$result = $ua->deleteObject('upload', '2.jpg');
	print_r($result);
