'use strict';

angular.module('toolApp.controllers.summary', ['ngRoute', 'toolApp.services', 'cfp.hotkeys'])

	.config(['$routeProvider', function($routeProvider){
		$routeProvider.when('/list/:type/summary', {
			templateUrl: '../../../view/summary.html',
			controller: 'SummaryCtrl'
		});
	}])

	.controller('SummaryCtrl', ['$scope', '$routeParams', '$timeout', 'SummaryService', function($scope, $routeParams, $timeout, $SummaryService){
		var language = 'summary';
		var type = $routeParams.type;

		$scope.isFetch = false;
		$scope.isLoading = false;
		$scope.message = null;
		$scope.summary = null;

		$scope.$emit('onChangedLanguageAndType', language, type);

		$scope.exportDownload = function(exportType){
			window.open("./ajax/summary/export.php?type=" + type + "&export=" + exportType);
		};

		// filterMarking, filterBlank 값에 따른 보여줄지 말지 여부.
		$scope.ifFilterInfo = function(info, options) {
			var filterMarking = options.filterMarking,
				filterBlank = options.filterBlank;

			var result = true;

			if (filterMarking) {
				result = !!info.modified;
			}
			if (result && filterBlank) {
				result = false;
				angular.forEach(info.infoList, function(v, k){
					result = (v.string === null || v.string.length == 0 ? true : result);
				});
			}

			return result;
		};

		// fetch data
		$scope.fetch = function(){
			$scope.isFetch = true;
			$scope.isLoading = true;
			$scope.message = null;
			$scope.summary = null;

			$SummaryService.summaryAllLanguage(type)
				.success(function(data, status){
					if (data.items)
						$scope.summary = data;

					if ($scope.summary == null)
						$scope.message = "데이터를 가져올 수 없습니다. 다시 시도해주세요.";
				})
				.error(function(){
					$scope.message = "데이터를 가져오는데 오류가 발생했습니다. 2초 후 재시도 합니다.";
				})
				.finally(function(){
					$scope.isLoading = false;
				});
		};

		$scope.$on('refetch', function(){
			$scope.fetch();
		});

		// 데이터가 많아서 느리므로 자동으로 읽지 않고 버튼으로 대체
		// $scope.fetch();
	}]);