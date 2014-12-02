<?php
	$request  = json_decode(file_get_contents("php://input"));
	$password = $request->password;

	setcookie("password", $password, strtotime('+7 days'), '/', $_SERVER['SERVER_NAME']);
	$_COOKIE['password'] = $password;

	$json = array();
	$json['success'] = true;
	echo json_encode($json);