StringInfoList = function(stringList){
	var self = this;
	this.stringList = stringList;
	var _stringListIndexMap = {};

	var _makeStringListIndexMap = function(){
		_stringListIndexMap = {};
		for (i in self.stringList) {
			if (!self.stringList.hasOwnProperty(i)) continue;

			var info = self.stringList[i];
			_stringListIndexMap[info.key] = i;
		}
	};

	var _getIndexOfKey = function(key) {
		return _stringListIndexMap[key];
	};

	this.getStringList = function(){
		return self.stringList;
	};

	this.getIndexOfKey = function(key){
		var idx = _getIndexOfKey(key);
		if (idx == undefined) {
			if (key == undefined) {
				var err = new Error();
				console.log(err.stack);
				return err.stack;
			}
		}
		if (idx == undefined) return -1;
		else return parseInt(idx);
		//for (i in self.stringList) {
		//	if (!self.stringList.hasOwnProperty(i)) continue;
		//
		//	var info = self.stringList[i];
		//	if (info.key == key)
		//		return i;
		//}
		//
		//return -1;
	};

	this.getInfo = function(key) {
		var idx = this.getIndexOfKey(key);
		if (idx == -1) return null;

		return self.stringList[idx];
	};

	this.getInfoByIndex = function(index) {
		if (self.stringList.length <= index) {
			return undefined;
		}
		return self.stringList[index];
	};

	this.hasKey = function(key) {
		return self.getIndexOfKey(key) != -1;
	};

	this.deleteKey = function(key) {
		var idx = this.getIndexOfKey(key);
		if (idx == -1) return;

//		delete self.stringList[idx];
		this.stringList.splice(idx, 1);

		_makeStringListIndexMap();
	};

	this.insert = function(info) {
		var key = info.key;
		var idx = this.getIndexOfKey(key);
		if (idx == -1) {
			this.stringList.push(info);
		} else {
			this.stringList[idx] = info;
		}

		_makeStringListIndexMap();
	};

	// filterMarking, filterBlank 값에 따른 보여줄지 말지 여부.
	this.ifFilterInfo = function(info, options) {
		var filterMarking = options.filterMarking,
			filterBlank = options.filterBlank;

		var result = true;

		if (filterMarking) {
			result = !!info.modified;
		}
		if (result && filterBlank) {
			result = (info.string === null || info.string.length == 0);
		}

		return result;
	};

	_makeStringListIndexMap();
};