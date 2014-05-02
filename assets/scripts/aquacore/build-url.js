var AquaCore = AquaCore || {};
(function($) {
	AquaCore.buildUrl = function(options) {
		var url = "";
		options = $.extend({
			"protocol": window.location.protocol + "//",
			"domain": window.location.host || window.location.hostname,
			"username": null,
			"password": null
		}, options);
		if(options.protocol) {
			url+= options.protocol;
		}
		if(options.username) {
			url+= options.username;
			if(options.password) {
				url+= ":" + options.password;
			}
			url+= "@";
		}
		url+= options.domain.replace(/(\/)+$/g, "");
		url+= this.buildPath(options);
		return url;
	};
	AquaCore.buildPath = function(options) {
		var path = "/";
		options = $.extend({
			"urlRewrite": AquaCore.REWRITE,
			"baseDir": AquaCore.DIR,
			"script": AquaCore.SCRIPT_NAME,
			"path": [],
			"action": "index",
			"arguments": [],
			"query": {},
			"hash": null
		}, options);
		if(options.baseDir) {
			path+= options.baseDir.replace(/(\/+)$/g, "").replace(/^(\/+)/g, "") + "/"
		}
		if(options.script && options.script !== "index.php") {
			path+= options.script.replace(/(\/+)$/g, "").replace(/^(\/+)/g, "") + "/";
		}
		if(options.urlRewrite) {
			options.path.map(encodeURIComponent);
			options.arguments.map(encodeURIComponent);
			if(options.path.length) {
				path+= options.path.join("/") + "/";
			}
			if((options.action && options.action !== "index") || options.arguments.length) {
				path+= "action/" + encodeURIComponent(options.action || "index") + "/";
				if(options.arguments.length) {
					path+= options.arguments.join("/") + "/";
				}
			}
		} else {
			var pathQuery = {};
			if(options.path.length) pathQuery.path = options.path.join(".");
			if(options.action && options.action !== "index") pathQuery.action = options.action;
			if(options.arguments.length) pathQuery.arg = options.arguments.join(".");
			options.query = $.extend(pathQuery, options.query);
		}
		path = path.replace(/\/+$/g, "");
		path+= this.buildQuery(options.query).replace(/&+$/g, "");
		if(options.hash) {
			path+= "#" + options.hash;
		}
		return path;
	};
	AquaCore.buildQuery = function(options) {
		var query = "";
		for(var key in options) {
			if(!options.hasOwnProperty(key)) {
				continue;
			}
			query+= encodeURIComponent(key);
			if($.isArray(options[key])) {
				query+= "=";
				options[key].map(encodeURIComponent);
				query = options[key].join("&");
			} else if(options[key]) {
				query+= "=" + encodeURIComponent(options[key]);
			}
			query+= "&";
		}
		if(query) {
			return "/?" + query;
		} else {
			return "";
		}
	};
})(jQuery);
