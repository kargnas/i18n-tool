<?php
	namespace App;

	use App\Exception\DataStoreException;
	use App\Model\StringInfoList;
	use App\Util\Logger;
	use FluentPDO;

	class DataStore
	{
		protected $sqliteFilePath = "";

		/** @var \SQLite3 */
		protected $sqlite;

		function __construct() {
			$this->sqliteFilePath = __DIR__ . "/../../file/db.sqlite";

			if (!file_exists($this->sqliteFilePath)) {
				$this->makeStructure();
			}

			$this->sqlite = new \SQLite3($this->sqliteFilePath);
			$this->sqlite->busyTimeout(5);
		}

		private function makeStructure() {
			$baseSqlite = __DIR__ . "/../../file/db.base.sqlite";
			$copy = @copy($baseSqlite, $this->sqliteFilePath);

			if ($copy === false) {
				ErrorPage::printError("쓰기 오류 발생", "<b>{$this->sqliteFilePath}</b> 파일을 쓸 수 없습니다. 폴더에 퍼미션이 조정되었는지 확인해주세요.");
			}

			if (!file_exists($this->sqliteFilePath)) {
				ErrorPage::printError("쓰기 오류 발생", "<b>{$this->sqliteFilePath}</b> 파일을 쓰는 것은 성공했으나, 생성된 파일을 찾을 수 없습니다.");
			}
		}

		/**
		 * @param callable $callback
		 */
		public function transaction($callback) {
			$this->sqlite->query('BEGIN;');
			$callback();
			$this->sqlite->query('COMMIT;');
		}

		function deleteAllKey($type, $key) {
			$query = "delete from `keyList` where `type` = \"" . addslashes($type) . "\" and `key` = \"" . addslashes($key) . "\"";
			$this->sqlite->exec($query);

			$query = "delete from `stringList` where `type` = \"" . addslashes($type) . "\" and `key` = \"" . addslashes($key) . "\"";
			$this->sqlite->exec($query);
		}

		function clear($type) {
			$query = "delete from `keyList` where `type` = \"" . addslashes($type) . "\"";
			$this->sqlite->exec($query);

			$query = "delete from `stringList` where `type` = \"" . addslashes($type) . "\"";
			$this->sqlite->exec($query);
		}

		function clearModified($type) {
			$query = "update `keyList` set `modified` = 0 where `type` = \"" . addslashes($type) . "\"";
			$this->sqlite->exec($query);
		}

		function deleteString($locale, $type, $key) {
			$query = "delete from `stringList` where `locale` = \"" . addslashes($locale) . "\" and `type` = \"" . addslashes($type) . "\" and `key` = \"" . addslashes($key) . "\"";
			$this->sqlite->exec($query);
		}

		function setString($locale, $type, $key, $string) {
			$string = stripcslashes($string);
			$string = addslashes($string);
			$string = str_replace("\\'", "'", $string);
			$string = str_replace("\\\"", "\"\"", $string);
			$query  = "insert into `stringList` (`locale`, `type`, `key`, `string`) values (\"" . addslashes($locale) . "\", \"" . addslashes($type) . "\", \"" . addslashes($key) . "\", \"" . $string . "\")";

			$res = $this->sqlite->exec($query);
			if (!$res) {
				throw new DataStoreException($this->sqlite->lastErrorMsg() . "\r\n" . $query);
			}
		}

		function setDictionary($locale, $type, StringInfoList $stringInfoList) {
			$self = $this;
			$this->transaction(function () use ($stringInfoList, $locale, $type, $self) {
				foreach ($stringInfoList->items as $stringInfo) {
					$self->deleteString($locale, $type, $stringInfo->key->toString());
					$self->setString($locale, $type, $stringInfo->key->toString(), $stringInfo->string);
				}
			});
		}

		public function getKey($type, $key) {
			$query = $this->sqlite->query("select * from `keyList` where `type` = \"" . addslashes($type) . "\" and `key` = \"" . addslashes($key) . "\"");
			$row   = $query->fetchArray();
			return $row;
		}

		public function setKey($type, $key, $modified = null) {
			if ($modified === null) $modified = 1;

			$query = "delete from `keyList` where `type` = \"" . addslashes($type) . "\" and `key` = \"" . addslashes($key) . "\"";
			@$this->sqlite->exec($query);

			$query = "insert into `keyList` (`type`, `key`, `modified`) values (\"" . addslashes($type) . "\", \"" . addslashes($key) . "\", \"" . ($modified ? 1 : 0) . "\")";
			@$this->sqlite->exec($query);
		}

		public function setKeys($type, StringInfoList $stringInfoList, $modified = null) {
			$self = $this;
			$this->transaction(function () use ($self, $stringInfoList, $type, $modified) {
				foreach ($stringInfoList->getKeys() as $key) {
					$self->setKey($type, $key, $modified);
				}
			});
		}

		/**
		 * keys 의 modified 를 한방에 변경
		 *
		 * @param       $type
		 * @param array $keys
		 * @param       $modified
		 */
		public function setKeysArray($type, array $keys, $modified) {
			$self = $this;
			$this->transaction(function () use ($self, $keys, $type, $modified) {
				foreach ($keys as $key) {
					$self->setKey($type, $key, $modified);
				}
			});
		}

		/**
		 * “ => " 등으로 변경해줌.
		 *
		 * @param $string
		 */
		static protected function replaceStandardSpecialChars($string) {
			if ($string === null)
				return $string;

			$replaceMap = array(
				"“" => "\"",
				"”" => "\""
			);

			return str_replace(array_keys($replaceMap), array_values($replaceMap), $string);
		}

		function getStrings($locale, $type) {
			/**
			 * group by keyList.key : angularjs 에서 track by `key` 옵션을 쓰고 있으므로, key 가 같은 값이 생기면 안됨. 중복 제거를 위해 group by
			 */
			$query = $this->sqlite->query("
				select
					keyList.key,
					keyList.modified,
					stringList.string

				from keyList

				left join stringList
				on stringList.key = keyList.key
				and stringList.`type` = keyList.type
				and stringList.`locale` = '" . addslashes($locale) . "'

				where
					keyList.`type` = '" . addslashes($type) . "'

				group by keyList.key

				order by keyList.key asc
			");

			$dic = array();
			while($row = $query->fetchArray()) {
				$dic[] = array(
					'key'      => $row['key'],
					'string'   => static::replaceStandardSpecialChars($row['string']),
					'modified' => $row['modified']
				);
			}

			return $dic;
		}

		public function getBaseStrings($type) {
			return static::getStrings('ko_KR', $type);
		}

		function getString($locale, $type, $key) {
			$query = $this->sqlite->query("
				select
					keyList.key,
					keyList.modified,
					stringList.string

				from keyList

				left join stringList
				on stringList.key = keyList.key
				and stringList.`type` = keyList.type
				and stringList.`locale` = '" . addslashes($locale) . "'

				where
					keyList.`type` = '" . addslashes($type) . "'
				and keyList.`key` = '" . addslashes($key) . "'

				order by keyList.key asc
			");

			$dic = array();
			while($row = $query->fetchArray()) {
				$dic[$row['key']] = array(
					'key'      => $row['key'],
					'string'   => static::replaceStandardSpecialChars($row['string']),
					'modified' => $row['modified']
				);
			}

			if (!$dic) {
				$dic[$key] = array(
					'key' => $key,
				    'string' => null,
				    'modified' => false
				);
			}

			return $dic[$key];
		}
	}