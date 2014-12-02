<?php
	namespace App\Bitbucket\Web;

	use App\Bitbucket\Web\Exception\StatusException;
	use App\Source\Git\BitbucketAPI;
	use App\Source\Git\iOS;
	use Bitbucket\API\Authentication\Exception;

	class Auth
	{
		/** @var BitbucketAPI */
		protected $bitbucketAPI;

		/** @var string */
		protected $user, $pass, $csrfToken;

		public $curl;

		protected $requestTimeout = 20;
		protected $cookieFile = '';

		public $lastCurlInfo;

		function __construct(BitbucketAPI $bitBucketApi) {
			$this->bitbucketAPI = $bitBucketApi;
			$this->user         = $bitBucketApi->user;
			$this->pass         = $bitBucketApi->pass;

			$this->cookieFile = "/tmp/bitbucketAuth.php.cookie";

			$this->doLogin();
		}

		function __destruct() {
			// @unlink($this->cookieFile);
		}

		protected static function http_build_str_inner($query, $prefix, $arg_separator, &$args) {
			if (!is_array($query)) {
				return null;
			}
			foreach ($query as $key => $val) {
				$name = $prefix . "[" . $key . "]";
				if (!is_numeric($name)) {
					if (is_array($val)) {
						static::http_build_str_inner($val, $name, $arg_separator, $args);
					} else {
						$args[] = rawurlencode($name) . '=' . urlencode($val);
					}
				}
			}
		}

		protected static function getQueryString(array $query, $prefix = '', $arg_separator = '') {
			if (!is_array($query)) {
				return null;
			}
			if ($arg_separator == '') {
				$arg_separator = ini_get('arg_separator.output');
			}
			$args = array();
			foreach ($query as $key => $val) {
				$name = $prefix . $key;
				if (!is_numeric($name)) {
					if (is_array($val)) {
						static::http_build_str_inner($val, $name, $arg_separator, $args);
					} else {
						$args[] = rawurlencode($name) . '=' . urlencode($val);
					}
				}
			}
			return implode($arg_separator, $args);
		}

		public function post($url, $parameter = array(), array $options = array()) {
			if ($parameter) {
				if (is_array($parameter))
					$query = static::getQueryString($parameter);
				else if (is_string($parameter))
					$query = $parameter;
			}

			$parsedUrl = parse_url($url);

			$url = $parsedUrl['scheme'] . "://" . ($parsedUrl['host']) . $parsedUrl['path'] . (!empty($parsedUrl['query']) ? "?" . $parsedUrl['query'] : "");

			$this->curl = curl_init();
			if ($parsedUrl['scheme'] == 'https') {
				curl_setopt($this->curl, CURLOPT_URL, $url);
				curl_setopt($this->curl, CURLOPT_PORT, ($parsedUrl['port'] ?: 443));
				curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0); // ignore ssl
				curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0); // ignore ssl
			} else {
				curl_setopt($this->curl, CURLOPT_URL, $url);
				curl_setopt($this->curl, CURLOPT_PORT, ($parsedUrl['port'] ?: 80));
			}
			if ($query) {
				curl_setopt($this->curl, CURLOPT_POST, true);
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, $query);
			}
			$headers = array(
				'Host: ' . $parsedUrl['host'],
				'Origin: https://bitbucket.org'
			);
			if ($options['contentType']) $headers[] = 'Content-Type: ' . $options['contentType'];
			else $headers[] = 'Content-Type: application/x-www-form-urlencoded';
			if ($options['accept']) $headers[] = 'Accept: ' . $options['accept'];
			if ($options['cookie']) $headers[] = 'Cookie: ' . $options['cookie'];
			if ($this->csrfToken) $headers[] = 'X-CSRFToken: ' . $this->csrfToken;
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->requestTimeout);
			curl_setopt($this->curl, CURLOPT_HEADER, true);
			curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookieFile);
			curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookieFile);
			if ($options['referer']) curl_setopt($this->curl, CURLOPT_REFERER, $options['referer']);
			if ($options['useragent']) curl_setopt($this->curl, CURLOPT_USERAGENT, $options['useragent']);

			/// debug
			curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);

			$response = curl_exec($this->curl);
			$header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$body = substr($response, $header_size);
			// list($header, $body) = explode("\r\n\r\n", , 2);
			$info     = curl_getinfo($this->curl);
			$errorNo  = curl_errno($this->curl);
			$errorStr = curl_error($this->curl);
			curl_close($this->curl);

			$this->lastCurlInfo                    = $info;
			$this->lastCurlInfo['response_header'] = $header;

			if ($this->lastCurlInfo['response_header']) {
				$responseHeaders = explode("\r\n", $this->lastCurlInfo['response_header']);
				foreach ($responseHeaders as $content) {
					if (preg_match("/^set-cookie\: (.*)$/i", $content, $pregs)) {
						if (preg_match("/csrftoken=(.[^;]*)/i", $content, $pregs)) {
							$this->csrfToken = $pregs[1];
						}
					}
				}
			}

			if ($errorNo !== 0) {
				throw new Exception("CURL Request Timeout: {$errorStr} (code: {$errorNo})");
			}

			if ($body === false)
				throw new Exception("CURL Response false");

			if ($body === '')
				throw new Exception("CURL Response empty");

			if (empty($info['http_code']))
				throw new Exception("CURL Response Status Empty");

			switch ($info['http_code']) {
				case 200:
				case 302:
					break;

				case 500:
					throw new Exception("커밋용 계정이 쓰기 권한을 가지고 있지 않은 것 같습니다. 권한을 확인하시기 바랍니다.");

				default:
					throw new StatusException($info['http_code'] . " / " . $body);
			}

			return $body;
		}

		protected function doLogin() {
			$loginPage = $this->post('https://bitbucket.org/account/signin/');
			preg_match("@csrfmiddlewaretoken'.*?value='(.[^']*?)'@", $loginPage, $pregs);

			usleep(300000);

			$csrfToken = $pregs[1];
			$request   = $this->post("https://bitbucket.org/account/signin/", array(
				'next'                => '/account/team_check/?next=/',
				'username'            => $this->user,
				'password'            => $this->pass,
				'csrfmiddlewaretoken' => $csrfToken,
				'submit'              => ''
			), array(
				                         'referer'   => 'https://bitbucket.org/account/signin/?next=/',
				                         'useragent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.120 Safari/537.36'
			                         ));

			if (strpos($request, 'home &mdash; Bitbucket') === false)
				throw new Exception("로그인에 실패했습니다.");

			return true;
		}
	}