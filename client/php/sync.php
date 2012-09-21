<?php

require_once(__DIR__ . '/PhpCloudServer.class.php');

$config = (object)require(__DIR__ . '/config.inc.php');

$server = new PhpCloudServer($config->url, $config->username, $config->password);
foreach ($server->getAllFiles() as $file) {
	echo "{$file->path}...";
	$server->downloadFile($file->path, $config->output_folder);
	echo "Ok\n"; 
}
