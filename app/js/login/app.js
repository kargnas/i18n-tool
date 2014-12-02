'use strict';

// Declare app level module which depends on views, and components
angular.module('loginApp', [
	'ngRoute',
	'loginApp.controllers.login',
	'cfp.hotkeys'
]).
	config(['$routeProvider', function($routeProvider){

	}])
	.controller('LoginCtrl', ['$scope', function($scope){

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
	});