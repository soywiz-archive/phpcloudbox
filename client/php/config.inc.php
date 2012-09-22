<?php

$lines = file(__DIR__ . '/../login');

return array(
	'url' => trim($lines[0]),
	'username' => trim($lines[1]),
	'password' => trim($lines[2]),
	'output_folder' => 'test_folder',
);