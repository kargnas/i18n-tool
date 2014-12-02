'use strict';

describe('toolApp.version module', function() {
  beforeEach(module('toolApp.version'));

  describe('version service', function() {
    it('should return current version', inject(function(version) {
      expect(version).toEqual('0.1');
    }));
  });
});
