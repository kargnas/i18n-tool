<?
	/**
	 * 번역 진행중인 언어 데이터를 DB에서 불러오는 기능.
	 */

	include "../../_head.php";

	\App\Util\Login::errorIfNotLogin();

	$request = json_decode(file_get_contents("php://input"));
	$type    = $request->type;

	switch ($type) {
		case 'iphone':
			$languages = \App\Source\Git\iOS::getLanguages();

			echo json_encode(array(
				                 'languageList' => $languages
			                 ));
			break;

		case 'android':
			$languages = \App\Source\Git\Android::getLanguages();

			echo json_encode(array(
				                 'languageList' => $languages
			                 ));
			break;

		case 'server':
			$languages = \App\Source\Git\Server::getLanguages();

			echo json_encode(array(
				                 'languageList' => $languages
			                 ));
			break;

		default:
	}