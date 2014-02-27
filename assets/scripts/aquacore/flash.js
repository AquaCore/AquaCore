var AquaCore = AquaCore || {};
(function($) {
	AquaCore.Flash = function(options) {
		var self = this;
		this.option(options);
		this.options.queue++;
		this.element = document.createElement("div");
		$(this.element)
			.addClass("ac-flash-messages")
			.appendTo(this.options.appendTo || document.body)
			.bind("mouseenter", function() { if(self.messages.length) self.stop(); })
			.bind("mouseleave", function() { if(self.messages.length) self.start(); });
	};
	AquaCore.Flash.prototype = $.extend({}, AquaCore.prototype, {
		element: null,
		timeout: null,
		interval: null,
		messages: [],
		options: {
			timeout: 2000,
			interval: 4000,
			queue: 5,
			appendTo: null,
			messageOptions: {},
			hide: {},
			show: {}
		},
		enqueue: function(type, title, message) {
			if(!type) {
				return this;
			} else if($.isArray(type)) {
				for(var i = 0; i < type.length; ++i) {
					this.enqueue(type[i]["type"], type[i]["title"], type[i]["message"]);
				}
				return this;
			}
			var flash = new AquaCore.FlashMessage(type, title, message, this.options.messageOptions);
			if(this.trigger("enqueue", [ flash ]) !== false) {
				var self = this;
				flash.bind("afterClose", function() { self.dequeue(this); });
				this.messages.push(flash);
				$(this.element).append(flash.element);
				if(!this.options.queue || this.messages.length < this.options.queue) {
					flash.show();
				} else {
					$(flash.element).css("display", "none");
				}
				this.show();
				this.startTimeout();
			} else {
				delete(flash);
			}
			return this;
		},
		dequeue: function(flash) {
			var pos = this.pos(flash);
			if(pos < 0) {
				return this;
			}
			if(this.trigger("dequeue", [ this.messages[pos] ]) !== false) {
				this.element.removeChild(this.messages[pos].element);
				if(this.messages.length === 0) {
					this.stop();
					this.hide();
				} else if(this.messages.length > this.options.queue) {
					var self = this;
					this.messages[this.options.queue - 1].show();
					$(this.messages[this.options.queue - 1].element)
						.promise()
						.done(function() { self.start(); });
				} else {
					this.start();
				}
				delete(this.messages[pos]);
				this.messages.splice(pos, 1);
			}
			return this;
		},
		clear: function() {
			for(var i = 0; i < this.messages.length; ++i) {
				this.dequeue(this.messages[i]);
			}
			return this;
		},
		pos: function(flash) {
			for(var i = 0; i < this.messages.length; ++i) {
				if(flash.uniqueId() === this.messages[i].uniqueId()) {
					return i;
				}
			}
			return -1;
		},
		next: function() {
			if(this.messages.length === 0) {
				return this;
			}
			var current, next = null;
			current = this.messages[0];
			this.stop();
			if(this.trigger("next", [ current ]) !== false) {
				var self = this;
				current.hide(function() {
					self.dequeue(current);
				});
			}
			return this;
		},
		show: function(callback) {
			if($(this.element).is(":hidden") && this.trigger("show") !== false) {
				$(this.element).show($.extend({
					complete: function() {
						self.trigger("afterShow");
						if(callback) {
							callback.apply(this, arguments);
						}
					}
				}, this.options.show));
			}
			return this;
		},
		hide: function() {
			if($(this.element).is(":visible") && this.trigger("hide") !== false) {
				$(this.element).hide($.extend({
					complete: function() {
						self.trigger("afterHide");
						if(callback) {
							callback.apply(this, arguments);
						}
					}
				}, this.options.show));
			}
			return this;
		},
		stop: function() {
			if(this.trigger("stop") !== false) {
				if(this.timeout) {
					clearTimeout(this.timeout);
					this.timeout = null;
				}
				if(this.interval) {
					clearInterval(this.interval);
					this.interval = null;
				}
			}
			return this;
		},
		start: function() {
			if(!this.interval && !this.trigger("start") !== false) {
				var self = this;
				this.interval = setInterval(function() { self.next(); }, self.options.interval);
			}
			return this;
		},
		startTimeout: function() {
			if(!this.timeout && !this.interval) {
				var self = this;
				this.timeout = setTimeout(function() { self.start(); }, this.options.timeout);
			}
			return this;
		}
	});
	AquaCore.FlashMessage = function(type, title, message, options) {
		var self = this;
		this.option(options);
		this.type = type || "notification";
		this.title = title || "";
		this.message = message || "";
		this.element = document.createElement("div");
		this.button = document.createElement("div");
		this.messageContainer = document.createElement("div");
		this.titleContainer = document.createElement("div");
		$(this.element)
			.addClass("ac-flash")
			.addClass("ac-flash-type-" + this.type)
			.attr("id", this.uniqueId());
		$(this.titleContainer)
			.addClass("ac-flash-title")
			.appendTo(this.element);
		$(this.messageContainer)
			.addClass("ac-flash-message")
			.html(message)
			.appendTo(this.element);
		$(this.button)
			.addClass("ac-flash-close")
			.appendTo(this.element);
		$(this.button).bind("click", function() { self.close(); });
		if(!title) {
			$(this.titleContainer).css("display", "none");
		}
	};
	AquaCore.FlashMessage.prototype = $.extend({}, AquaCore.prototype, {
		type: "notification",
		title: null,
		message: null,
		element: null,
		button: null,
		titleContainer: null,
		messageContainer: null,
		options: {
			show: {
				effect: "blind",
				easing: "easeInOutCirc",
				duration: 400
			},
			hide: {
				effect: "blind",
				easing: "easeInOutCirc",
				duration: 400
			}
		},
		events: {},
		setTitle: function(title) {
			this.title = title;
			this.titleContainer.innerHTML = title;
			if(title) {
				$(this.titleContainer).css("display", "");
			} else {
				$(this.titleContainer).css("display", "none");
			}
			return this;
		},
		setMessage: function(message) {
			this.message = message;
			$(this.messageContainer).html(message);
			return this;
		},
		hide: function(callback) {
			if(this.trigger("hide") !== false) {
				var self = this;
				$(this.element).hide($.extend({
					complete: function() {
						self.trigger("afterHide");
						if(callback) {
							callback.apply(this, arguments);
						}
					}
				}, this.options.hide));
			}
			return this;
		},
		show: function(callback) {
			if(this.trigger("show") !== false) {
				var self = this;
				$(this.element).show($.extend({
					complete: function() {
						self.trigger("afterShow");
						if(callback) {
							callback.apply(this, arguments);
						}
					}
				}, this.options.show));
			}
			return this;
		},
		close: function(callback) {
			if(this.trigger("close") !== false) {
				var self = this;
				$(this.element).hide($.extend({
					complete: function() {
						self.trigger("afterClose");
						if(callback) {
							callback.apply(this, arguments);
						}
					}
				}, this.options.hide));
			}
			return this;
		}
	});
})(jQuery);
