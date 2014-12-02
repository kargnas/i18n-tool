'use strict';

angular.module('toolApp.controllers.list', ['ngRoute', 'toolApp.services', 'cfp.hotkeys'])

	.config(['$routeProvider', function($routeProvider){
		$routeProvider.when('/list/:type/:language', {
			templateUrl: '../../../view/list.html',
			controller: 'ListCtrl'
		});
	}])

	.controller('ListCtrl', ['$scope', '$routeParams', '$timeout', 'StringService', 'hotkeys', function($scope, $routeParams, $timeout, $StringService, hotkeys){
		var language = $routeParams.language;
		var type = $routeParams.type;

		$scope.isLoading = false;
		$scope.isLoadingImport = false;
		$scope.isLoadingCommit = false;
		$scope.editingKey = null;
		/** @type StringInfoList */
		$scope.stringList = null;
		$scope.baseStringList = {};

		$scope.fetch = function(){
			$scope.isLoading = 2;

			$StringService.list(language, type)
				.success(function(data, status){
					$scope.stringList = new StringInfoList(data);
				})
				.finally(function(){
					$scope.isLoading--;
				});

			$StringService.baseList(type)
				.success(function(data, status){
					$scope.baseStringList = new StringInfoList(data);
				})
				.finally(function(){
					$scope.isLoading--;
				});
		};

		$scope.importFreshData = function(){
			$scope.isLoadingImport = true;
			$StringService.import(language, type)
				.success(function(data, stats){
					if (!data.success) {
						alert('실패!');
					}
				})
				.finally(function(){
					$scope.isLoadingImport = false;
					$scope.fetch();
				});
		};

		$scope.commitData = function(){
			if (!confirm('변경된 데이터를 서버에 커밋하시겠습니까? 변경사항은 롤백 할 수 없습니다.'))
				return;

			$scope.isLoadingCommit = true;
			$StringService.commit(language, type)
				.success(function(data, stats){
					if (!data.success) {
						alert("요청에 실패했습니다.\r\n\r\n" + data);
						return;
					}

					alert(data.message);

					// 개발자 모드일 때 새창으로 bitbucket 커밋 내역 뜨도록 하기.
					if (data.location && $scope.$parent.isDeveloperMode) {
						window.open(data.location);
					}
				})
				.finally(function(){
					$scope.isLoadingCommit = false;
				});
		};

		$scope.exportDownload = function(exportType){
			window.open("./ajax/string/export.php?lang=" + language + "&type=" + type + "&export=" + exportType);
		};

		$scope.onChangeEditingKey = function(newKey, newScope){
			if (newKey == $scope.editingKey)
				return;

			//$scope.$emit('onChangeEditingKey', $scope.editingKey, newKey);
			//console.log("현재 수정중: " + $scope.editingKey + ' -> ' + newKey + ' 로 변경');

			$scope.$broadcast('onChangedEditingKey', $scope.editingKey, newKey);

			$scope.editingKey = newKey;
		};

		// 단축키용 메소드
		$scope.cancelEditing = function(){
			$scope.$broadcast('onCanceledEditing', $scope.editingKey);
			$scope.editingKey = null;
		};

		$scope.doPrevEditing = function(moveIndex){
			var currentIndex = $scope.stringList.getIndexOfKey($scope.editingKey);
			if (currentIndex != -1) {
				var newStringInfo = $scope.stringList.getInfoByIndex(currentIndex - moveIndex);
				// newStringInfo.key
				if (newStringInfo) {
					$scope.onChangeEditingKey(newStringInfo.key);
				} else {
					$scope.onChangeEditingKey(null);
				}
			}
		};

		$scope.doNextEditing = function(moveIndex){
			var currentIndex = $scope.stringList.getIndexOfKey($scope.editingKey);
			if (currentIndex != -1) {
				var newStringInfo = $scope.stringList.getInfoByIndex(currentIndex + moveIndex);
				// newStringInfo.key
				if (newStringInfo) {
					$scope.onChangeEditingKey(newStringInfo.key);
				} else {
					$scope.onChangeEditingKey(null);
				}
			}
		};

		$scope.$on('refetch', function(){
			$scope.fetch();
		});

		$scope.$on('onChangeEditingKey', function(an, newKey, newScope){
			$scope.onChangeEditingKey(newKey, newScope);
		});

		$scope.$on('addNewString', function(ag, newKey, newInfo){
			$scope.stringList.insert(newInfo);
		});

		$scope.$on('removeString', function(ag, newKey){
			$scope.stringList.deleteKey(newKey);
			// console.log('allList Updated', $scope.stringList);
		});

		$scope.$emit('onChangedLanguageAndType', language, type);

		$scope.fetch();

		hotkeys.add({
			combo: 'esc',
			description: '수정중인 작업 취소하기',
			allowIn: ['textarea'],
			callback: function() {
				event.preventDefault();

				$scope.cancelEditing();
			}
		});

		hotkeys.add({
			combo: ['shift+tab'],
			description: '저장하고 이전으로 넘어가기',
			allowIn: ['textarea'],
			callback: function(event, hotkey) {
				event.preventDefault();

				$scope.doPrevEditing(1);
			}
		});

		hotkeys.add({
			combo: ['ctrl+enter', 'tab'],
			description: '저장하고 다음으로 넘어가기',
			allowIn: ['textarea'],
			callback: function(event, hotkey) {
				event.preventDefault();

				$scope.doNextEditing(1);
			}
		});
	}])

	.controller('ListKeyStringController', ['$scope', '$filter', '$timeout', 'StringService', 'hotkeys', function($scope, $filter, $timeout, $StringService, hotkeys){
		$scope.isRequest = false;
		$scope.reloadInfo = function(){
			$scope.input = $scope.info;
			$scope.inputOriginal = angular.copy($scope.input);

			// 체크박스용
			$scope.isNull = $scope.info.string == null;
		};

		$scope.makeCurrentEditing = function(){
			$scope.$emit('onChangeEditingKey', $scope.input.key, $scope);
		};

		$scope.cancelEditing = function(){
			$scope.$emit('onChangeEditingKey', null, $scope);
		};

		$scope.isCurrentEditing = function(){
			if (!$scope.input) return false;
			return $scope.$parent.editingKey == $scope.input.key;
		};

		$scope.delete = function(){
			if (!confirm("정말로 삭제하시겠습니까?\r\n\r\n[" + $scope.input.key + "]\r\n" + $scope.input.string))
				return;

			$scope.isRequest = true;
			$StringService.delete($scope.$parent.selectedLanguage.code, $scope.$parent.selectedType, $scope.input.key)
				.success(function(data, status){
					if (data.success) {
						$scope.$emit('removeString', $scope.input.key);
						$scope.reloadInfo();
					} else {
						alert('Error!');
					}
				})
				.finally(function(){
					$scope.isRequest = false;
				});
		};

		$scope.toggleModified = function(){
			$scope.isLoading = true;
			$StringService
				.toggleModified($scope.$parent.selectedType, $scope.$parent.selectedLanguage.code, $scope.input.key)
				.success(function(data, status){
					if (data.info) {
						$scope.info = data.info;
						$scope.$emit('addNewString', $scope.info.key, $scope.info);
						$scope.reloadInfo();
					}
				})
				.finally(function(){
					$scope.isLoading = false;
				});
		};

		$scope.saveLastEditing = function() {
			// console.log('Save: ' + $scope.info.key + ', ' + $scope.input.string);
			$StringService.insert($scope.$parent.selectedLanguage.code, $scope.$parent.selectedType, $scope.input.key, $scope.input.string, $scope.$parent.isMarkingAfterEditing)
				.success(function(data, status){
					if (data.success) {
						$scope.info = data.info;
						$scope.$emit('addNewString', $scope.info.key, $scope.info);
						$scope.reloadInfo();
					} else {
						alert('Error!');
					}
				})
				.finally(function(){
					$scope.isRequest = false;
				});
		};

		$scope.$on('onChangedEditingKey', function(an, editingKey, newKey){
			if ($scope.input.key != editingKey)
				return;

			if (!$scope.input.string) {
				$scope.input.string = ($scope.isNull ? null : $scope.input.string);
			}

			var isChanged = $scope.inputOriginal.string != $scope.input.string;
			//console.log($scope.inputOriginal.string + " => " + $scope.input.string);

			if (isChanged) {
				$scope.isRequest = true;

				$timeout(function(){
					$scope.saveLastEditing();
				}, 300);
			}
		});

		$scope.$on('onCanceledEditing', function(an, editingKey){
			if ($scope.input.key != editingKey)
				return;

			var isChanged = $scope.inputOriginal.string != $scope.input.string;
			//console.log($scope.inputOriginal.string + " => " + $scope.input.string);

			// 취소기능
			// Original 로 복구
			if (isChanged) {
				$scope.input = angular.copy($scope.inputOriginal);
			}
		});

		$scope.reloadInfo();
	}])

	.controller('StringFormController', ['$scope', '$filter', 'StringService', function($scope, $filter, $StringService){
		$scope.isLoading = false;

		$scope.submit = function(){
			var key = $scope.key;
			var string = $scope.string;

			if (!key || !string)
				return;

			$scope.isLoading = true;

			$StringService.insert($scope.$parent.selectedLanguage.code, $scope.$parent.selectedType, key, string)
				.success(function(data, status){
					if (data.success) {
						$scope.$emit('addNewString', key, data.info);
						//$scope.key = '';
						$scope.string = '';
					} else {
						alert('Error!');
					}
				})
				.finally(function(){
					$scope.isLoading = false;
				});
		};
	}]);