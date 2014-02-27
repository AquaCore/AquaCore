var	AquaCore = AquaCore || {};
(function ($) {
	AquaCore = $.extend({
		id: 0,
		TIME_OFFSET: 0,
		URL: "",
		REWRITE: false,
		DIR: "",
		BASE_DIR: "",
		SCRIPT_NAME: "",
		settings: {},
		language: {
			words: {},
			direction: "LTR"
		}
	}, AquaCore, true);
	AquaCore.language.extend = function(namespace, strings) {
		this.words[namespace] = this.words[namespace] || {};
		$.extend(this.words[namespace], strings);
	};
	AquaCore.language.namespace = function(namespace) {
		return (this.words.hasOwnProperty(namespace) ? this.words[namespace] : {});
	};
	AquaCore.l = function(namespace, key) {
		var args;
		if(this.language.namespace(namespace).hasOwnProperty(key)) {
			key = this.language.words[namespace][key];
		} else {
			return key;
		}
		args = Array.prototype.splice.call(arguments, 2);
		if(args.length) {
			args.unshift(key);
			key = sprintf.apply(null, args);
		}
		return key;
	};
	AquaCore.getDate = function(date) {
		if(!(date instanceof Date)) {
			date = new Date(date);
		}
		return new Date(date.getTime() + (date.getTimezoneOffset() * 60000) + (AquaCore.TIMEOFFSET * 60000));
	};
	AquaCore.prototype = {
		id: null,
		events: {},
		options: {},
		uniqueId: function() {
			this.id = this.id || ++AquaCore.id;
			return ("aquacore-n-" + this.id);
		},
		option: function(name, value) {
			if(typeof name === "object") {
				this.options = $.extend({}, this.options, name);
				return this;
			} else if(typeof value !== "undefined") {
				this.options[name] = value;
				return this;
			} else {
				return this.options[name];
			}
		},
		bind: function(event, callback) {
			this.events[event] = this.events[event] || [];
			this.events[event].push(callback);
			return this;
		},
		trigger: function(event, args) {
			if(!this.events.hasOwnProperty(event) || this.events[event].length < 1) {
				return null;
			}
			var i, response = null;
			args = args || [];
			for(i = 0; i < this.events[event].length; ++i) {
				response = this.events[event][i].apply(this, args)
				if(response === false) {
					return false;
				}
			}
			return response;
		}
	};
})(jQuery);
