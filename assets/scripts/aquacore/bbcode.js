var AquaCore = AquaCore || {};
(function($) {
	AquaCore.bbCode = {};
	AquaCore.bbCode.spoiler = function(button, content) {
		var self = this;
		this.button = button;
		this.content = content;
		console.log(button, content);
		$(this.button).bind("click", function(e) {
			self.toggle();
			e.preventDefault();
		});
	};
	AquaCore.bbCode.spoiler.prototype = $.extend({}, AquaCore.prototype, {
		button: null,
		content: null,
		options: {
			hide: {
				effect: "blind",
				easing: "easeInOutCirc",
				duration: 200
			},
			show: {
				effect: "blind",
				easing: "easeInOutCirc",
				duration: 200
			}
		},
		toggle: function() {
			if($(this.content).is(":hidden")) {
				this.show();
			} else {
				this.hide();
			}
		},
		hide: function(callback) {
			if(this.trigger("hide") !== false) {
				var self = this;
				$(this.button).removeClass("active");
				$(this.content)
					.stop(true, false)
					.hide($.extend({
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
				$(this.button).addClass("active");
				$(this.content)
					.stop(true, false)
					.show($.extend({
						complete: function() {
							self.trigger("afterShow");
							if(callback) {
								callback.apply(this, arguments);
							}
						}
					}, this.options.show));
			}
			return this;
		}
	}, true);
})(jQuery);
