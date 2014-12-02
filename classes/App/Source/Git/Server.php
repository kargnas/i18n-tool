<?php
	namespace App\Source\Git;

	use App\Config;
	use App\LocalizationDictionary\StringDictionary;

	class Server extends BitbucketAPI
	{
		static $languageMap = array();

		function __construct() {
			parent::__construct();

			$this->repoAccount  = Config::get('source.server.git.repo.account');
			$this->repoName     = Config::get('source.server.git.repo.name');
			$this->repoBranch   = Config::get('source.server.git.repo.branch');
			$this->resourceType = StringDictionary::getResourceTypeByString(Config::get('source.server.resourceType'));
		}
	}