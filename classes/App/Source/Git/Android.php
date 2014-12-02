<?php
	namespace App\Source\Git;

	use App\Bitbucket\Web\Auth;
	use App\Bitbucket\Web\Commit;
	use App\Config;
	use App\DataStore;
	use App\Exception\CommitSourceNotChangedException;
	use App\LocalizationDictionary\StringDictionary;

	class Android extends BitbucketAPI
	{
		static $languageMap = array();

		function __construct() {
			parent::__construct();

			$this->repoAccount  = Config::get('source.android.git.repo.account');
			$this->repoName     = Config::get('source.android.git.repo.name');
			$this->repoBranch   = Config::get('source.android.git.repo.branch');
			$this->resourceType = StringDictionary::getResourceTypeByString(Config::get('source.android.resourceType'));
		}
	}