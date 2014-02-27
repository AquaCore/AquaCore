var formBeforeSend, formSuccess;
(function($) {
	formBeforeSend = function() {
		$(".ac-form-warning div.active, .ac-form-response", this.form)
			.stop(true, true)
			.hide("blind", {easing: "easeInOutCirc"}, 180, function() {
				$(this)
					.html("")
					.removeClass("active")
					.show("blind", {easing: "easeInOutCirc"}, 10);
			});
		if(this.responseTimeout) {
			clearTimeout(this.responseTimeout);
			$(".ac-form-response", this.form)
				.hide("fade", {easing: "easeInOutCirc"}, 180, function() {
					$(this).html("").removeClass("ac-form-response-error").show(0);
				});
		}
	};
	formSuccess = function(data) {
		var key;
		for(key in data["data"]) {
			if(!data["data"].hasOwnProperty(key)) {
				continue;
			}
			var field = $("[name=\"" + key + "\"]", this.form),
				value = data["data"][key];
			if(!field.length) {
				continue;
			}
			if(field.is("select")) {
				$("option", field).removeAttr("selected");
				if($.isArray(value)) {
					$.each(value, function() {
						$("option[value=\"" + this + "\"]", field).attr("selected", "selected");
					});
				} else {
					$("option[value=\"" + value + "\"]", field).attr("selected", "selected");
				}
			} else {
				value = $("<div></div>").html(value).text();
				field.val(value);
			}
		}
		for(key in data["warning"]) {
			if(!data["warning"].hasOwnProperty(key)) {
				continue;
			}
			$("[name=\"" + key + "\"]", this.form)
				.closest("tr")
				.prev()
				.find("div")
				.promise()
				.done(function() {
					$(this)
						.css("display", "none")
						.addClass("active")
						.html(data["warning"][key])
						.show("blind", {easing: "easeInOutCirc"}, 200);
				});
		}
		if(data["message"]) {
			$(".ac-form-response", this.form)
				.promise()
				.done(function () {
					if(data["error"]) {
						$(this).addClass("ac-form-response-error");
					}
					$(this)
						.css("display", "none")
						.prepend(data["message"])
						.show("fade", {easing: "easeInOutCirc"}, 200);
				});
			this.responseTimeout = setTimeout(function() {
				$(".ac-form-response", this.form)
					.hide("fade", {easing: "easeInOutCirc"}, 180, function() {
						$(this).html("").removeClass("ac-form-response-error").show(0);
					});
			}, 6000);
		}
	};
})(jQuery);