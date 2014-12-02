<?php
	namespace App\Model;

	use App\Exception\StringException;

	class StringInfo
	{
		const STRING_ESCAPE_XCODE = 1;
		const STRING_ESCAPE_ANDROID = 2;
		const STRING_ESCAPE_PROPERTIES = 3;

		/** @var StringInfoList */
		public $list;

		/** @var Key */
		public $key;

		/** @var string */
		public $string;

		/** @var bool */
		public $modified;

		function __construct(array $json, StringInfoList $list) {
			$this->key      = new Key($json['key']);
			$this->string   = $json['string'];
			$this->modified = $json['modified'];

			$this->list = $list;
		}

		public function getJson() {
			return array(
				'key'      => (string) $this->key->toString(),
				'string'   => $this->string,
				'modified' => (bool) $this->modified
			);
		}

		/**
		 * 이 데이터를 DB에 저장.
		 * 삭제 후 재생성하는 방식을 취하는 특별한 이유는 없음 (그냥 만들다가 편해서 그렇게 만듬)
		 *
		 * @throws StringException
		 */
		public function save() {
			if (!$this->list)
				throw new StringException("List 값이 존재하지 않습니다.");

			$type   = $this->list->type;
			$locale = $this->list->locale;

			$store = new \App\DataStore();
			$store->setKey($type, $this->key->toString(), 1);
			$store->deleteString($locale, $type, $this->key->toString());
			$store->setString($locale, $type, $this->key->toString(), $this->string);
		}

		/**
		 * 현재와 동일한 Key, Type, Locale 의 객체를 다시 디비에서 로딩하여 리턴
		 *
		 * @return static
		 * @throws StringException
		 */
		public function getReloadedObject() {
			if (!$this->list)
				throw new StringException("List 값이 존재하지 않습니다.");

			$type   = $this->list->type;
			$locale = $this->list->locale;

			$store  = new \App\DataStore();
			$string = $store->getString($locale, $type, $this->key->toString());

			$self = new static($string, $this->list);
			return $self;
		}

		public function isArrayString() {
			return preg_match("/##[0-9]*$/", $this->key);
		}

		public function getArrayStringKey() {
			preg_match("/^(.*)##[0-9]*$/i", $this->key, $pregs);
			return $pregs[1];
		}

		public function getArrayStringIndex() {
			preg_match("/^.*##([0-9]*)$/i", $this->key, $pregs);
			return $pregs[1];
		}

		public function isNull() {
			return $this->string === null;
		}

		public function getEscapeString($type) {
			switch ($type) {
				case static::STRING_ESCAPE_XCODE:
					return addcslashes($this->string, "\n\r\"");
					break;

				case static::STRING_ESCAPE_ANDROID:
					$str = addcslashes(htmlspecialchars($this->string), "\n\r\"'");
					$str = str_replace(array("&quot;"), array("\""), $str);
					return $str;
					break;

				case static::STRING_ESCAPE_PROPERTIES:
					$str = $this->string;
					// $str = str_replace("'", "''", $str);
					$str = substr(json_encode(array($str)), 2, -2);
					$str = str_replace("\\\"", "\"", $str);
					$str = str_replace("\\\\", "\\", $str);
					$str = str_replace("\\/", "/", $str);
					return $str;
					break;

				default:
					return $this->string;
			}
		}
	}