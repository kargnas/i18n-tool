'use strict';

angular.module('toolApp.services', [])

	.factory('StringService', function($http){
		var doRequest = function(path, params){
			return $http.post(path, params)
				.error(function(data, status){
					alert("HTTP Request 도중 오류가 발행했습니다.\r\n\r\nStatus " + status + "\r\n" + data);
				});
		};

		return {
			list: function(lang, type){
				return doRequest('./ajax/string/getStrings.php', {
					type: type,
					lang: lang
				});
			},
			baseList: function(type){
				return doRequest('./ajax/string/getBaseStrings.php', {
					type: type
				});
			},
			import: function(lang, type){
				return doRequest('./ajax/string/import.php', {
					lang: lang,
					type: type
				});
			},
			commit: function(lang, type){
				return doRequest('./ajax/string/commit.php', {
					lang: lang,
					type: type
				});
			},
			delete: function(lang, type, key){
				return doRequest('./ajax/string/deleteKey.php', {
					lang: lang,
					type: type,
					key: key
				});
			},
			insert: function(lang, type, key, string, isMarkingAfterEditing){
				return doRequest('./ajax/string/insert.php', {
					lang: lang,
					type: type,
					key: key,
					string: string,
					isMarkingAfterEditing: isMarkingAfterEditing
				});
			},
			toggleModified: function(type, locale, key){
				return doRequest('./ajax/string/toggleModified.php', {
					locale: locale,
					type: type,
					key: key
				});
			}
		};
	})

	.factory('ImporterService', function($http){
		var doRequest = function(path, params){
			return $http.post(path, params)
				.error(function(data, status){
					alert("HTTP Request 도중 오류가 발행했습니다.\r\n\r\nStatus " + status + "\r\n" + data);
				});
		};

		return {
			bulkImporter: function(type, lang, content){
				return doRequest('./ajax/importer/bulkImporter.php', {
					type: type,
					lang: lang,
					content: content
				});
			}
		};
	})

	.factory('SummaryService', function($http){
		var doRequest = function(path, params){
			return $http.post(path, params)
				.error(function(data, status){
					alert("HTTP Request 도중 오류가 발행했습니다.\r\n\r\nStatus " + status + "\r\n" + data);
				});
		};

		return {
			summaryAllLanguage: function(type){
				return doRequest('./ajax/summary/getSummaryAllLanguage.php', {
					type: type
				});
			}
		};
	})

	.factory('AppService', function($http){
		var doRequest = function(path, params){
			return $http.post(path, params)
				.error(function(data, status){
					alert("HTTP Request 도중 오류가 발행했습니다.\r\n\r\nStatus " + status + "\r\n" + data);
				});
		};

		return {
			languages: function(type){
				return doRequest('./ajax/getLanguages.php', {
					type: type
				});
			},
			clear: function(type){
				return doRequest('./ajax/clear.php', {
					type: type
				});
			},
			clearMarks: function(type){
				return doRequest('./ajax/clearMarks.php', {
					type: type
				});
			},
			commit: function(type){
				return doRequest('./ajax/commit.php', {
					type: type
				});
			},
			import: function(type){
				return doRequest('./ajax/import.php', {
					type: type
				});
			},
		};
	})
;