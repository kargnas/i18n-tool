<?php
	date_default_timezone_set('Asia/Seoul');
	include __DIR__ . "/vendor/autoload.php";

	// HTTP_X_FORWARDED_FOR 가 있으면 REMOTE_ADDR 치환
	if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' && $_SERVER['HTTP_X_FORWARDED_FOR']) {
		$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}

	$configPath = __DIR__ . '/file/config.yml';
	if (!file_exists($configPath)) {
		\App\ErrorPage::printError("<h1>Config 파일이 존재하지 않습니다.</h1>",
		                           "<b>" . $configPath . "</b> 에 설정파일을 생성해주세요.");
	}

	$globalConfig = \Heartsentwined\Yaml\Yaml::parse($configPath);
	\App\Config::setConfig($globalConfig);