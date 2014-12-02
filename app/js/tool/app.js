'use strict';

// Declare app level module which depends on views, and components
angular.module('toolApp', [
	'ngRoute',
	'toolApp.controllers.summary',
	'toolApp.controllers.list',
	'toolApp.controllers.importer',
	'toolApp.services',
	'toolApp.version',
	'monospaced.elastic',
	'cfp.hotkeys'
]).
	config(['$routeProvider', function($routeProvider){
		$routeProvider
			.otherwise({redirectTo: '/list/iphone/summary'});
	}])
	.controller('BodyCtrl', ['$scope', 'AppService', function($scope, $AppService){
		var defaultLanguage = 'summary';

		$scope.languageList = [];
		$scope.typeList = [
			{code: 'iphone', type: '아이폰'},
			{code: 'android', type: '안드로이드'},
			{code: 'server', type: '서버'}
		];

		$scope.selectedLanguage = {code: defaultLanguage, lang: '한국어'};
		$scope.selectedType = $scope.typeList[0]['code'];
		$scope.isDeveloperMode = false;
		$scope.isMarkingAfterEditing = false;

		$scope.fetchLanguages = function(cb){
			$AppService.languages($scope.selectedType)
				.success(function(data){
					$scope.languageList = data.languageList;
					cb && cb();
				});
		};

		$scope.getLanguageByCode = function(code){
			var idx = -1;
			angular.forEach($scope.languageList, function(v, i){
				if (v.code == code) {
					idx = i;
				}
			});

			if (idx == -1) return {
				code: code
			};

			return $scope.languageList[idx];
		};

		$scope.getCurrentTypeDesc = function(){
			for (var i in $scope.typeList) {
				var data = $scope.typeList[i];
				if (data['code'] == $scope.selectedType) {
					return data['type'];
				}
			}
			return $scope.selectedType;
		};

		$scope.importAll = function(){
			if (!confirm('서버에서 데이터를 가져오시겠습니까? 취소는 불가능합니다.'))
				return;

			$scope.isLoadingImportAll = true;

			$AppService.import($scope.selectedType)
				.success(function(data, stats){
					if (!data.success) {
						alert("실패!\r\n\r\n" + data);
					}
				})
				.finally(function(){
					$scope.isLoadingImportAll = false;
					$scope.$broadcast('refetch');
				});
		};

		$scope.commitAll = function(){
			if (!confirm('수정한 내역을 업로드 하시겠습니까?'))
				return;

			$scope.isLoadingCommitAll = true;

			$AppService.commit($scope.selectedType)
				.success(function(data, stats){
					if (!data.success) {
						alert("요청에 실패했습니다.\r\n\r\n" + data);
						return;
					}

					alert(data.message);
				})
				.finally(function(){
					$scope.isLoadingCommitAll = false;
				});
		};

		$scope.clearCurrentData = function(){
			if (!confirm('현재 임시 DB에 저장된 "' + $scope.selectedType + '"의 모든 언어 데이터가 삭제 하시겠습니까?'))
				return;

			if (!confirm('복구는 불가능합니다. 진짜 삭제하시겠습니까?'))
				return;

			$AppService.clear($scope.selectedType)
				.success(function(data, stats){
					if (!data.success) {
						alert('실패!');
					}
				})
				.finally(function(){
					$scope.$broadcast('refetch');
				});
		};

		$scope.clearCurrentMarks = function(){
			if (!confirm('"' + $scope.selectedType + '"의 모든 언어의 마킹 표시를 해제하시겠습니까?'))
				return;

			if (!confirm('복구가 불가능합니다. 정말로 하시겠습니까?'))
				return;

			$AppService.clearMarks($scope.selectedType)
				.success(function(data, stats){
					if (!data.success) {
						alert('실패!');
					}
				})
				.finally(function(){
					$scope.$broadcast('refetch');
				});
		};

		$scope.$on('onChangedLanguageAndType', function(ng, language, type, menu){
			var doSomethingDefaultLanguages = function(){
				var changedLanguage = $scope.getLanguageByCode(language);
				if (!changedLanguage) changedLanguage = $scope.getLanguageByCode(defaultLanguage);

				$scope.selectedLanguage = changedLanguage;
			};

			$scope.selectedMenu = menu;
			if ($scope.selectedType != type) {
				$scope.selectedType = type;
				$scope.fetchLanguages(function(){
					doSomethingDefaultLanguages();
				});
			} else {
				doSomethingDefaultLanguages();
			}
		});

		$scope.getUrlChangedLanguage = function(val){
			return "#/list/" + $scope.selectedType + "/" + val;
		};

		$scope.getUrlChangedType = function(val){
			var code = ($scope.selectedLanguage ? $scope.selectedLanguage.code : defaultLanguage);
			return "#/list/" + val + "/" + code;
		};

		$scope.fetchLanguages();
	}])
	.directive('focusMe', function($timeout, $parse){
		return {
			//scope: true,   // optionally create a child scope
			link: function(scope, element, attrs){
				var model = $parse(attrs.focusMe);
				scope.$watch(model, function(value){
					if (value === true) {
						$timeout(function(){
							element[0].select();
						});
					}
				});
				// to address @blesh's comment, set attribute value to 'false'
				// on blur event:
				element.bind('blur', function(){
					try {
						scope.$apply(model.assign(scope, false));
					} catch (e) {
					}
				});
			}
		};
	})
	.directive('ngAllowTab', function () {
		return function (scope, element, attrs) {
			element.bind('keydown', function (event) {
				if (event.which == 9) {
					event.preventDefault();
					var start = this.selectionStart;
					var end = this.selectionEnd;
					element.val(element.val().substring(0, start)
					+ '\t' + element.val().substring(end));
					this.selectionStart = this.selectionEnd = start + 1;
					element.triggerHandler('change');
				}
			});
		};
	})
	.filter('unsafe', function($sce) { return $sce.trustAsHtml; });