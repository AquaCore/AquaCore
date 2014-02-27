var AquaCore = AquaCore || {};
(function($) {
	AquaCore.ExperienceSlider = function(slider, options) {
		var self = this;
		this.option(options);
		this.element      = slider;
		this.currentLevel = this.options.level;
		this.experience   = document.createElement("div");
		this.slider       = document.createElement("div");
		this.info         = document.createElement("div");
		this.min = Math.max(0, this.options.level - 11)
		this.max = this.options.level + 31;
		$(this.experience)
			.addClass("ac-renewal-exp")
			.appendTo(this.element);
		$(this.info)
			.addClass("ac-renewal-exp-slider")
			.appendTo(this.element);
		$(this.slider)
			.addClass("ac-renewal-exp-slider")
			.appendTo(this.element)
			.slider(AquaCore.extend({}, this.options.slider, {
			min: Math.max(0, this.options.level - 11),
			max: this.options.level + 31,
			value: this.currentLevel,
			change: function(e, ui) {
				self.currentLevel = ui.value;
				self.updateUi();
			}
		}));
		this.updateUi();
	};
	AquaCore.ExperienceSlider.prototype = $.extend({}, AquaCore.prototype, {
		currentLevel: null,
		element: null,
		experience: null,
		slider: null,
		info: null,
		options: {
			format: "Level: :level (:rate%)",
			level: 1,
			experience: 1,
			slider: {}
		},
		level: function(level) {
			if(typeof level !== "undefined") {
				this.currentLevel = level;
				$(this.slider).slider("value", level);
			}
			return this.currentLevel;
		},
		updateUi: function(level) {
			var rate, diff;
			level = level || this.currentLevel;
			diff = level - this.options.level;
			if(diff < -10) {
				level = "&lt;" + (level + 1);
				rate = 40;
			} else if(diff === -10) {
				rate = 140;
			} else if(diff === -9) {
				rate = 135;
			} else if(diff === -8) {
				rate = 130;
			} else if(diff === -7) {
				rate = 125;
			} else if(diff === -6) {
				rate = 120;
			} else if(diff === -5) {
				rate = 115;
			} else if(diff === -4) {
				rate = 110;
			} else if(diff === -3) {
				rate = 105;
			} else if(diff >= -2 && diff <= 5 ) {
				rate = 100;
			} else if(diff >= 6 && diff <= 10 ) {
				rate = 95;
			} else if(diff >= 11 && diff <= 15 ) {
				rate = 90;
			} else if(diff >= 16 && diff <= 20 ) {
				rate = 85;
			} else if(diff >= 21 && diff <= 25 ) {
				rate = 60;
			} else if(diff >= 26 && diff <= 30 ) {
				rate = 35;
			} else {
				level = "&gt;" + (level - 1);
				rate = 10;
			}
			$(this.experience).text(Math.floor((this.options.experience / 100) * rate).format());
			$(this.info).html(this.options.format.replace(":level", level).replace(":rate", rate));
		}
	});
})(jQuery);
