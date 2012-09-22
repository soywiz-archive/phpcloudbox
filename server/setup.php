<?php

require_once(__DIR__ . '/code/Core.php');

$fin = fopen('php://stdin', 'rb');

echo "IP: ";
$ip = trim(fgets($fin));

echo "Host: ";
$host = trim(fgets($fin));

echo "Certificate base path: ";
$certPath = trim(fgets($fin));

$serverRootDir = __DIR__;

for ($n = 0; $n <= 9; $n++) @mkdir("/tmp/{$n}", 0777, true);

$tpl = <<<EOF
upstream backend {
	ip_hash;
	server 127.0.0.1:9000;
}

server {
	#listen       {$ip}:80;
	listen       {$ip}:443 ssl;
	server_name  {$host};
	
	ssl                  on;
	ssl_certificate      {$certPath}.pem;
	ssl_certificate_key  {$certPath}.key;
    ssl_protocols        SSLv3 TLSv1 TLSv1.1 TLSv1.2;
    ssl_ciphers          HIGH:!aNULL:!MD5;

	keepalive_timeout    70;
	
	gzip on;
	gzip_http_version 1.0;
	gzip_comp_level 2;
	gzip_proxied any;
	gzip_types text/plain text/html text/css application/x-javascript text/xml application/xml application/xml+rss text/javascript;

	fastcgi_read_timeout 600;
	fastcgi_send_timeout 600;
	
	location ~ /.(svn|git)/ {
		deny all;
	}
	
	location /?action=file.upload {
		# 10 GB
		client_max_body_size 10000M;

		# Pass altered request body to this location
		upload_pass   /?php&action=file.upload;

		# Store files to this directory
		# The directory is hashed, subdirectories 0 1 2 3 4 5 6 7 8 9 should exist
		upload_store /tmp 1;

		# Allow uploaded files to be read only by user
		upload_store_access user:rw group:rw all:rw;

		# Set specified fields in request body
		upload_set_form_field "files[\${upload_field_name}][name]" \$upload_file_name;
		upload_set_form_field "files[\${upload_field_name}][content_type]" \$upload_content_type;
		upload_set_form_field "files[\${upload_field_name}][path]" \$upload_tmp_path;

		# Inform backend about hash and size of a file
		#upload_aggregate_form_field "files[\${upload_field_name}][md5]" \$upload_file_md5;
		upload_aggregate_form_field "files[\${upload_field_name}][size]" \$upload_file_size;

		upload_pass_form_field "^.*\$";

		upload_cleanup 404 500-505;
	}
	
	location / {
		root           {$serverRootDir}/www;
		
		fastcgi_param  SCRIPT_FILENAME    {$serverRootDir}/www/index.php;
		fastcgi_param  QUERY_STRING       \$query_string;
		fastcgi_param  REQUEST_METHOD     \$request_method;
		fastcgi_param  CONTENT_TYPE       \$content_type;
		fastcgi_param  CONTENT_LENGTH     \$content_length;

		fastcgi_param  SCRIPT_NAME        \$fastcgi_script_name;
		fastcgi_param  REQUEST_URI        \$request_uri;
		fastcgi_param  DOCUMENT_URI       \$document_uri;
		fastcgi_param  DOCUMENT_ROOT      \$document_root;
		fastcgi_param  SERVER_PROTOCOL    \$server_protocol;
		fastcgi_param  HTTPS              on;

		fastcgi_param  GATEWAY_INTERFACE  CGI/1.1;
		fastcgi_param  SERVER_SOFTWARE    nginx/\$nginx_version;

		fastcgi_param  REMOTE_ADDR        \$remote_addr;
		fastcgi_param  REMOTE_PORT        \$remote_port;
		fastcgi_param  SERVER_ADDR        \$server_addr;
		fastcgi_param  SERVER_PORT        \$server_port;
		fastcgi_param  SERVER_NAME        \$server_name;

		# PHP only, required if PHP was built with --enable-force-cgi-redirect
		fastcgi_param  REDIRECT_STATUS    200;

		fastcgi_pass   backend;
	}
	
	# http://wiki.nginx.org/X-accel
	location {$serverRootDir}/files {
		internal;
		root /;
	}
}
EOF;

file_put_contents(__DIR__ . '/nginx.conf', $tpl);