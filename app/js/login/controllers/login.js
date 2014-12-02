'use strict';

angular.module('loginApp.controllers.login', ['ngRoute', 'loginApp.services', 'cfp.hotkeys'])

	.config(['$routeProvider', function($routeProvider){

	}])

	.controller('LoginFormCtrl', ['$scope', 'LoginService', function($scope, $LoginService){
		$scope.noIE = false;

		if (bowser.msie) {
			$scope.noIE = true;
		}

		$scope.submit = function(){
			var password = $scope.password;

			$LoginService.login(password)
				.success(function(json){
					if (json.success) {
						location.href = '/';
					}
				});
		};
	}]);