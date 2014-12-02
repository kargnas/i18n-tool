<?php
	namespace App\Bitbucket\Web;

	use App\Bitbucket\Web\Exception\StatusException;
	use App\Source\Git\BitbucketAPI;
	use App\Source\Git\iOS;
	use Bitbucket\API\Authentication\Exception;

	class Commit
	{
		/** @var Auth */
		protected $auth;

		/** @var BitbucketAPI */
		protected $bitbucketAPI;

		protected $filePath, $content;

		function __construct(Auth $auth, BitbucketAPI $bitbucketAPI, $filePath, $content) {
			$this->auth         = $auth;
			$this->bitbucketAPI = $bitbucketAPI;

			$this->filePath = $filePath;
			$this->content  = $content;
		}

		public function commit($message = '') {
			$lastCommitHash = $this->bitbucketAPI->getRecentCommitHash();

			$referer = 'https://bitbucket.org/' . $this->bitbucketAPI->repoAccount . '/' . $this->bitbucketAPI->repoName . '/src/' . $lastCommitHash . $this->filePath . '?at=' . $this->bitbucketAPI->repoBranch;

//			$this->auth->post($referer);
//			sleep(1);

//			$this->auth->post('https://bitbucket.org/api/1.0/repositories/1km/app-ios/src/89daef4ddc3e5f61f225df9d4704080757960d2e/ko.lproj/Localizable.strings');
//			sleep(1);

			$param = array(
				'branch'     => $this->bitbucketAPI->repoBranch,
				'files'      => array(
					array(
						'path'    => $this->filePath,
						'content' => $this->content
					)
				),
				'message'    => $message,
				'parents'    => array($lastCommitHash),
				'repository' => array(
					'full_name' => $this->bitbucketAPI->repoAccount . "/" . $this->bitbucketAPI->repoName
				),
				'timestamp'  => @date('c', time()),
				'transient'  => false
			);

			if (!$param['files'][0]['path'])
				throw new Exception("path 가 존재하지 않습니다.");

			if (!$param['files'][0]['content'])
				throw new Exception("content 가 존재하지 않습니다.");

			$data = $this->auth->post('https://bitbucket.org/!api/internal/repositories/' . $this->bitbucketAPI->repoAccount . '/' . $this->bitbucketAPI->repoName . '/oecommits/', json_encode($param), array(
				'referer'     => $referer,
				'contentType' => 'application/json',
				'accept'      => 'application/json, text/javascript, */*; q=0.01'
			));

			return json_decode($data);
		}
	}