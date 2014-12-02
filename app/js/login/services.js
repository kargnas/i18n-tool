'use strict';

angular.module('loginApp.services', [])

	.factory('LoginService', function($http){
		var doRequest = function(path, params){
			return $http.post(path, params)
				.error(function(data, status){
					alert("HTTP Request 도중 오류가 발행했습니다.\r\n\r\nStatus " + status + "\r\n" + data);
				});
		};

		return {
			login: function(password){
				return doRequest('./ajax/login/login.php', {
					password: password
				});
			}
		};
	});