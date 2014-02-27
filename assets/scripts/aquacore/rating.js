(function($) {
	AquaCore.Rating = function(element, options) {
		var self = this;
		this.element = element;
		this.loading = false;
		this.option(options);
		this.element.on("mousemove", function(e) {
			$(".ac-content-rating-hover", this).css("width", self.weight(e).pct + "%");
		}).on("mouseenter", function() {
			$(".ac-content-rating-fill", this).css("display", "none");
		}).on("mouseleave", function() {
			$(".ac-content-rating-hover", this).css("width", 0);
			$(".ac-content-rating-fill", this).css("display", "block");
		}).on("click", function(e) {
			self.rate(self.weight(e).weight);
		});
	};
	AquaCore.Rating.prototype = $.extend({}, AquaCore.prototype, {
		options: {
			contentType: 0,
			contentId: 0,
			maxWeight: 0,
			averageRating: 0,
			userRating: 0
		},
		weight: function(e) {
			var pct, weight, offsetX;
			offsetX = e.pageX - this.element.offset().left;
			pct = offsetX / (this.element.width() / 100);
			weight = Math.ceil((pct / 100) * this.options.maxWeight);
			pct = weight / (this.options.maxWeight / 100);
			return { pct: pct, weight: weight };
		},
		rate: function(weight) {
			var self = this;
			if(this.loading) return;
			this.loading = true;
			$.ajax({
				dataType: "json",
				cache: false,
				url: AquaCore.buildUrl({
					baseDir: AquaCore.BASEDIR,
					script: "rate.php",
					query: {
						"ctype": this.options.contentType,
						"id": this.options.contentId,
						"weight": weight
					}
				}),
				success: function(data) {
					if(data.success) {
						self.userRating = data.weight;
						self.averageRating = data.average;
						$(".ac-content-rating-fill", self.element).css("width", self.averageRating / (self.options.maxWeight / 100) + "%");
					}
				},
				complete: function() {
					self.loading = false;
				}
			});
		}
	});
})(jQuery);
