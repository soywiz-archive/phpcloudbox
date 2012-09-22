<?php

require_once(__DIR__ . '/PhpCloudServer.class.php');

$config = (object)require(__DIR__ . '/config.inc.php');

$server = new PhpCloudServer($config->url, $config->username, $config->password);
$server->downloadAllFilesTo($config->output_folder);
$server->uploadAllNonUploadedFiles($config->output_folder);