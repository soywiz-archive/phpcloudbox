<?php

$result = NULL;
$error = NULL;

try {
	require_once(__DIR__ . '/../code/Core.php');

	$user = Auth::httpAuth();
	$action = isset($_GET['action']) ? $_GET['action'] : '';
	
	$dbFiles = new DbFiles();
	$dbUser = new DbUser($user);
	
	$dbFiles->setup();
	$dbUser->setup();
	
	switch ($action) {
		case 'test':
			$time = time();
			
			die('
				<iframe name="result" style="width:100%;height:120px;"></iframe>
					
				<h1>file.upload</h1>
				<form action="?action=file.upload" method="post" target="result" enctype="multipart/form-data">
				<div>File: <input type="file" name="file" /></div>
				<input type="submit" value="submit" />
				</form>
				
				<h1>file.has</h1>
				<form action="." method="get" target="result" enctype="multipart/form-data">
				<input type="hidden" name="action" value="file.has">
				<div>Sha1: <input type="text" name="sha1" value="" /></div>
				<input type="submit" value="submit" />
				</form>
				
				<h1>tree.remove</h1>
				<form action="." method="get" target="result" enctype="multipart/form-data">
				<input type="hidden" name="action" value="tree.remove">
				<div>Path: <input type="text" name="path" value="" /></div>
				<input type="submit" value="submit" />
				</form>
				
				<h1>tree.add</h1>
				<form action="." method="get" target="result" enctype="multipart/form-data">
				<input type="hidden" name="action" value="tree.add">
				<div>Path: <input type="text" name="path" value="" /></div>
				<div>Sha1: <input type="text" name="sha1" value="" /></div>
				<div>CTime: <input type="text" name="ctime" value="' . htmlspecialchars($time) . '" /></div>
				<div>MTime: <input type="text" name="mtime" value="' . htmlspecialchars($time) . '" /></div>
				<div>Perms: <input type="text" name="perms" value="0777" /></div>
				<input type="submit" value="submit" />
				</form>
				
				<h1>tree.get</h1>
				<form action="." method="get" target="result" enctype="multipart/form-data">
				<input type="hidden" name="action" value="tree.get">
				<input type="submit" value="submit" />
				</form>
					
				<h1>log.getSince</h1>
				<form action="." method="get" target="result" enctype="multipart/form-data">
				<input type="hidden" name="action" value="log.getSince">
				<div>RowId: <input type="text" name="rowid" value="" /></div>
				<input type="submit" value="submit" />
				</form>
			');
		case 'file.upload':
			if (!isset($_FILES['file'])) throw(new Exception("Not specified a file"));
			$tmp_file = $_FILES['file']['tmp_name'];
			$sha1 = sha1_file($tmp_file);
			$sha1File = new Sha1File($sha1);
			$sha1File->moveUploadedFile($tmp_file);
			$dbFiles->addFile($sha1);
			$result = true;
		break;
		case 'file.has':
			if (!isset($_GET['sha1'])) throw(new Exception("Must specify 'sha1'"));
			$sha1File = new Sha1File($_GET['sha1']);
			$result = $sha1File->exists();
		break;
		case 'tree.get':
			$result = $dbUser->getAllFiles();
		break;
		case 'tree.add':
			if (!isset($_GET['path'])) throw(new Exception("Must specify 'path'"));
			if (!isset($_GET['sha1'])) throw(new Exception("Must specify 'sha1'"));
			if (!isset($_GET['ctime'])) throw(new Exception("Must specify 'ctime'"));
			if (!isset($_GET['mtime'])) throw(new Exception("Must specify 'mtime'"));
			if (!isset($_GET['perms'])) throw(new Exception("Must specify 'perms'"));
			$sha1File = new Sha1File($_GET['sha1']);
			$dbUser->addFile($_GET['path'], $_GET['sha1'], $_GET['ctime'], $_GET['mtime'], $_GET['perms']);
			
			$result = $sha1File->exists() ? 'file_already_uploaded' : 'must_upload_file';
			break;
		case 'tree.remove':
			if (!isset($_GET['path'])) throw(new Exception("Must specify 'path'"));
			$dbUser->deleteFile($_GET['path']);
		break;
		case 'log.getSince':
			if (!isset($_GET['rowid'])) throw(new Exception("Must specify 'rowid'"));
			$result = $dbUser->getLogSince($_GET['rowid']);
		break;
		case '':
			die('
				<ul>
					<li><a href="?action=test">test</a></li>
				</ul>
			');
		default:
			throw(new Exception("Unknown action '{$action}'"));
	}
} catch (Exception $e) {
	$error = $e->getMessage();
}

header('Content-Type: application/json');
if ($error !== NULL) {
	die(json_encode(array('result' => 'error', 'data' => $error)));
} else {
	die(json_encode(array('result' => 'ok', 'data' => $result)));
}
