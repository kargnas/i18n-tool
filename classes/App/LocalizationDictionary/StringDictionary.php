<?php
	namespace App\LocalizationDictionary;

	use App\Exception\DictionaryException;
	use App\Model\StringInfoList;
	use App\Source\Git\Android;
	use App\Source\Git\iOS;
	use App\Source\Git\Server;
	use App\Util\Logger;
	use Sabre\XML;

	class StringDictionary
	{
		const RESOURCE_TYPE_ANDROID = 1;
		const RESOURCE_TYPE_IPHONE = 2;
		const RESOURCE_TYPE_SERVER = 3;
		const RESOURCE_TYPE_EXCEL = 4;

		/** @var StringInfoList */
		public $stringInfoList;

		public $lang, $type;

		function __construct(StringInfoList $stringInfoList) {
			$this->stringInfoList = $stringInfoList;
		}

		/**
		 * @param $stringType
		 *
		 * @return int|null
		 */
		static function getResourceTypeByString($stringType) {
			switch($stringType) {
				case 'android':
					return static::RESOURCE_TYPE_ANDROID;
				case 'iphone':
					return static::RESOURCE_TYPE_IPHONE;
				case 'server':
					return static::RESOURCE_TYPE_SERVER;
				default:
					return null;
			}
		}

		static protected function stripHTMLStyleSpaces($string) {
			$string = preg_replace("/\s{2,}/i", "", $string);
			$string = trim($string);
			return $string;
		}

		static protected function parseProperties($txtProperties) {
			$result             = array();
			$lines              = explode("\n", $txtProperties);
			$key                = "";
			$isWaitingOtherLine = false;
			foreach ($lines as $i => $line) {
				if (empty($line) || (!$isWaitingOtherLine && strpos($line, "#") === 0))
					continue;

				if (!$isWaitingOtherLine) {
					$key   = substr($line, 0, strpos($line, '='));
					$value = substr($line, strpos($line, '=') + 1, strlen($line));
				} else {
					$value .= $line;
				}

				/* Check if ends with single '\' */
				if (strrpos($value, "\\") === strlen($value) - strlen("\\")) {
					$value              = substr($value, 0, strlen($value) - 1) . "\n";
					$isWaitingOtherLine = true;
				} else {
					$isWaitingOtherLine = false;
				}

				if ($key) {
					$value              = trim(static::decodeUnicodeEscape($value));
					$value              = str_replace("''", "'", $value);
					$result[trim($key)] = $value;
				}

				unset($lines[$i]);
			}

			return $result;
		}

		static protected function decodeUnicodeEscape($str) {
			$str = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
				return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
			}, $str);
			return $str;
		}

		// PHP 5.3 기준으로 마땅한 XML 파서가 없어 코드 쪼가리를 주워서 만듬.
		static function XML2assoc(\XMLReader $xml) {
			$assoc = null;
			$n     = 0;
			while($xml->read()) {
				if ($xml->nodeType == \XMLReader::END_ELEMENT) break;
				if ($xml->nodeType == \XMLReader::ELEMENT and !$xml->isEmptyElement) {
					$assoc[$n]['name'] = '{}' . $xml->name;
					if ($xml->hasAttributes) while($xml->moveToNextAttribute()) $assoc[$n]['attributes'][$xml->name] = $xml->value;
					$assoc[$n]['value'] = static::XML2assoc($xml);
					$n++;
				} else if ($xml->isEmptyElement) {
					$assoc[$n]['name'] = '{}' . $xml->name;
					if ($xml->hasAttributes) while($xml->moveToNextAttribute()) $assoc[$n]['attributes'][$xml->name] = $xml->value;
					$assoc[$n]['value'] = "";
					$n++;
				} else if ($xml->nodeType == \XMLReader::TEXT) $assoc = $xml->value;
			}
			return $assoc;
		}

		/**
		 * @param $type
		 * @param $content
		 *
		 * @return static
		 *
		 * @throws DictionaryException
		 */
		static function getByParsingStringsFile($type, $content) {
			Logger::Log("파싱 중... {$type}");
			switch ($type) {
				case static::RESOURCE_TYPE_IPHONE:
					$data = array();
//			$lines = file($this->content, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
					$lines = explode("\n", $content);
					foreach ($lines as $line) {
						while(preg_match('/^\\s*("(?:[^"]|\\\\")*"|[^"]\\w*)\\s*=\\s*("(?:[^"]|\\\\")*"|[^"]\\w*)\\s*;/', $line, $groups) == 1) {
							// $group[1] contains the name, $group[2] contains the value
							array_push($data, array($groups[1], $groups[2]));
							$line = trim(substr($line, strlen($groups[0])));
						}
					}

					$json = array(
						'items' => array()
					);
					foreach ($data as $info) {
						$key = $info[0];
						$val = $info[1];

						$key = substr($key, 1, -1);
						$val = substr($val, 1, -1);

						$json['items'][] = array(
							'key'    => $key,
							'string' => $val
						);
					}

					$stringInfoList = new StringInfoList($json);
					return new static($stringInfoList);
					break;

				case static::RESOURCE_TYPE_ANDROID:
					$reader = new \XMLReader();
					$reader->xml($content);

					$parsed = static::XML2assoc($reader);
					$json   = array(
						'items' => array()
					);

					foreach ($parsed as $dom1) {
						switch($dom1['name']) {
							case '{}resources':
								foreach ($dom1['value'] as $dom) {
									switch ($dom['name']) {
										case '{}string':
											$json['items'][] = array(
												'key'    => $dom['attributes']['name'],
												'string' => static::stripHTMLStyleSpaces($dom['value'])
											);
											break;

										case '{}string-array':
											foreach ($dom['value'] as $idx => $item) {
												switch ($item['name']) {
													case '{}item':
														$json['items'][] = array(
															'key'    => $dom['attributes']['name'] . '##' . sprintf("%05d", $idx) . '',
															'string' => static::stripHTMLStyleSpaces($item['value'])
														);
														break;
												}
											}
											break;
									}
								}
								break;
						}
					}

					$stringInfoList = new StringInfoList($json);
					return new static($stringInfoList);
					break;

				case static::RESOURCE_TYPE_SERVER:
					$ini = static::parseProperties($content);

					$json = array(
						'items' => array()
					);

					foreach ($ini as $key => $val) {
						$json['items'][] = array(
							'key'    => $key,
							'string' => $val
						);
					}

					$stringInfoList = new StringInfoList($json);
					return new static($stringInfoList);
					break;

				default:
					throw new DictionaryException("알 수 없는 타입입니다.");
			}
		}

		public function getExportString($type) {
			return $this->stringInfoList->getExportString($type);
		}

		/**
		 * '언어 파일 직접 다운로드' 기능을 사용 할 때 호출하는 메소드
		 *
		 * @param $type
		 *
		 * @throws DictionaryException
		 */
		public function exportString($type) {
			switch ($type) {
				// 안드로이드, 아이폰은 파일네임에 언어 정보가 없고, 디렉토리 명에 언어 코드가 지정되어 있는 방식이므로 FileName 에 language 가 뭔지 표현해줌. (편의상)
				// 예) ko.lproj/Localizable.strings 또는 /res/values-ko/strings.xml
				case static::RESOURCE_TYPE_IPHONE:
					$fileName = "[" . $this->lang . "] " . iOS::getFileNameByLanguage($this->lang);
					break;

				case static::RESOURCE_TYPE_ANDROID:
					$fileName = "[" . $this->lang . "] " . Android::getFileNameByLanguage($this->lang);
					break;

				// 서버는 파일명에 무슨 언어인지 적혀있음. 그래서 그대로 다운로드 기능에 사용함 (Messages_[언어].properties)
				case static::RESOURCE_TYPE_SERVER:
					$fileName = Server::getFileNameByLanguage($this->lang);
					break;

				default:
					throw new DictionaryException("알 수 없는 타입입니다.");
			}

			header('Content-Type: application/octet-stream');
			header("Content-Transfer-Encoding: Binary");
			header("Content-disposition: attachment; filename=\"" . addslashes($fileName) . "\"");
			echo $this->getExportString($type);
		}

		public function exportExcel() {
			$this->stringInfoList->printExportString(static::RESOURCE_TYPE_EXCEL);
		}

		public function setBaseStringsByDictionary(StringDictionary $dic) {
			foreach ($dic->stringInfoList->items as $item) {
				if ($this->stringInfoList->hasKey($item->key)) continue;

				$this->stringInfoList->addStringInfo($item);
			}
		}
	}