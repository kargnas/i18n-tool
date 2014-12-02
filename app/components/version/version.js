'use strict';

angular.module('toolApp.version', [
  'toolApp.version.interpolate-filter',
  'toolApp.version.version-directive'
])

.value('version', '0.1');
