'use strict';

angular.module('toolApp.controllers.importer', ['ngRoute', 'toolApp.services', 'cfp.hotkeys'])

	.config(['$routeProvider', function($routeProvider){
		$routeProvider.when('/list/:type/:lang/importer', {
			templateUrl: '../../../view/importer.html',
			controller: 'ImporterCtrl'
		});
	}])

	.controller('ImporterCtrl', ['$scope', '$routeParams', 'ImporterService', function($scope, $routeParams, $ImporterService){
		var language = $routeParams.lang;
		var type = $routeParams.type;
		var menu = 'importer';

		// 언어가 아닌 요약 화면일 때는 이 기능을 지원하지 않는다.
		//if (language == 'summary') {
		//	location.href = '#/list/' + type + '/' + language;
		//	return;
		//}

		$scope.$emit('onChangedLanguageAndType', language, type, menu);

		$scope.isRequesting = false;

		$scope.submit = function(){
			$scope.isRequesting = true;
			var content = $scope.bulkData;

			$ImporterService
				.bulkImporter(type, language, content)
				.success(function(data, stats){
					if (!data.success) {
						alert('실패!\r\n\r\n' + data);
					} else {
						alert('작업이 완료되었습니다.');
					}
				})
				.finally(function(){
					$scope.isRequesting = false;
					// location.href = '#/list/' + type + '/' + language;
				});
		};
	}]);