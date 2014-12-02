<?php
	namespace App\Source\Git;

	use App\Bitbucket\Web\Auth;
	use App\Bitbucket\Web\Commit;
	use App\Config;
	use App\Exception\CommitSourceNotChangedException;
	use App\LocalizationDictionary\StringDictionary;

	class iOS extends BitbucketAPI
	{
		static $languageMap = array();

		function __construct() {
			parent::__construct();

			$this->repoAccount  = Config::get('source.ios.git.repo.account');
			$this->repoName     = Config::get('source.ios.git.repo.name');
			$this->repoBranch   = Config::get('source.ios.git.repo.branch');
			$this->resourceType = StringDictionary::getResourceTypeByString(Config::get('source.ios.resourceType'));
		}
	}