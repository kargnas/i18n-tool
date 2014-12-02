<?php
	namespace App\Source\Git;

	use App\Bitbucket\Web\Auth;
	use App\Bitbucket\Web\Commit;
	use App\Config;
	use App\Exception\CommitSourceNotChangedException;
	use App\Exception\ConfigException;
	use App\LocalizationDictionary\StringDictionary;
	use App\Util\Logger;

	abstract class BitbucketAPI
	{
		// 각 언어별로 설정파일에서 읽어옴. (실제 할당은 App\Config 클래스에서 되도록 함)
		static $languageMap = array();

		/** @var Auth */
		protected $auth;

		/** @var string */
		public $user, $pass;

		/** @var int */
		public $resourceType;

		/** @var string */
		public $repoAccount, $repoName, $repoBranch;

		function __construct() {
			$this->user = Config::get('git.id');
			$this->pass = Config::get('git.pass');

			if (!$this->user || !$this->pass)
				throw new ConfigException("아이디나 비밀번호가 설정되지 않았습니다.");
		}

		static function getLanguages() {
			$list = array();
			foreach (static::$languageMap as $code => $info) {
				$list[] = array(
					'code'  => $code,
					'lang'  => $info['name'],
					'isRtl' => $info['isRtl']
				);
			}
			return $list;
		}

		static function getLanguageCodes() {
			$list = array();
			foreach (static::$languageMap as $code => $info) {
				$list[] = $code;
			}
			return $list;
		}

		/**
		 * $languageMap 변수를 참고해서, 언어코드에 해당하는 파일 Path 에서 Directory를 제외한 File Name 만 가져옴.
		 * 파일 그대로 다운받기 기능을 위함임.
		 *
		 * @param $code
		 */
		static function getFileNameByLanguage($lang) {
			$path = static::$languageMap[$lang]['path'];
			if (preg_match('@/(.[^/]*)$@', $path, $pregs)) {
				return $pregs[1];
			}
			return null;
		}

		public function getRecentCommitHash() {
			$commits = new \Bitbucket\API\Repositories\Commits();
			$commits->setCredentials(new \Bitbucket\API\Authentication\Basic($this->user, $this->pass));
			$all = $commits->all($this->repoAccount, $this->repoName, array(
				'branch' => $this->repoBranch
			));

			$data = json_decode($all->getContent());
			return $data->values[0]->hash;
		}

		protected function getFile($revision, $path) {
			$src = new \Bitbucket\API\Repositories\Src();
			$src->setCredentials(new \Bitbucket\API\Authentication\Basic($this->user, $this->pass));

			return $src->raw($this->repoAccount, $this->repoName, $revision, $path)->getContent();
		}

		/**
		 * @return Auth
		 */
		protected function getAuth() {
			if (!isset($this->auth)) {
				$this->auth = new Auth($this);
			}
			return $this->auth;
		}

		/**
		 * GIT 소스에서 제일 최근의 언어 파일 내용 가져오기
		 *
		 * @param $lang string ko_KR, en_US, ja_JP, ...
		 *
		 * @return mixed
		 */
		public function getLastStrings($lang) {
			Logger::Log("최근 Commit Hash 를 가져오는 중..");
			$hash = $this->getRecentCommitHash();

			Logger::Log("언어 소스 파일 가져오는 중.. ({$hash})");
			$file = $this->getFile($hash, static::$languageMap[$lang]['path']);
			return $file;
		}

		/**
		 * @param                  $lang
		 * @param StringDictionary $dictionary
		 * @param string           $message
		 *
		 * @return array
		 * @throws CommitSourceNotChangedException
		 * @throws \Bitbucket\API\Authentication\Exception
		 */
		public function commitDictionary($lang, StringDictionary $dictionary, $message = '[Auto commit]') {
			$auth = $this->getAuth();

			$latestGitString = $this->getLastStrings($lang);
			$newString       = $dictionary->getExportString($this->resourceType);

			if ($latestGitString == $newString)
				throw new CommitSourceNotChangedException("최근 데이터와 변동사항이 없어 실제 커밋하지는 않습니다.");

			$commit = new Commit($auth, $this, static::$languageMap[$lang]['path'], $newString);
			return $commit->commit($message);
		}
	}