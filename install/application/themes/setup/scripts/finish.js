(function($) {
	var steps, progress, currentStep;

	function nextStep() {
		var step = steps[currentStep];
		$(".installation-step." + step)
			.removeClass("incomplete")
			.addClass("running");
		$.ajax({
			dataType: "json",
			cache: false,
			type: "GET",
			url: "?action=install&args=" + step + "." + progress,
			success: function(data) {
				if(data.progress[0] === data.progress[1]) {
					progress = 1;
					currentStep++;
					$(".installation-step." + step)
						.removeClass("running")
						.addClass("complete")
						.find(".progress")
						.text("");
					if(currentStep === steps.length) {
						window.location.search = "?action=finish";
						return;
					}
				} else {
					progress++;
					$(".installation-step." + step)
						.find(".progress")
						.text("(" + progress + "/" + data.progress[1] + ")");
				}
				nextStep();
			},
			error: function(jqXHR) {
				var error = $("div").addClass("ac_form_warning").text("An unexpected error occurred.");
				try {
					var response = $.parseJSON(jqXHR.responseText);
					if(typeof response === "object" && response.hasOwnProperty("error")) {
						error.text(response.error);
					}
				} catch(e) {}
				$(".content").prepend(error);
			}
		});
	}

	$("button.start-installation").bind("click", function() {
		$(".start-installation, button.next").remove();
		$("button.prev").attr("disabled", "disabled").parent().attr("href", "#");
		$.ajax({
			dataType: "json",
			cache: false,
			type: "GET",
			url: "?action=install&args=start",
			success: function(data) {
				var list = $("ul").addClass("installation-steps");
				steps = [];
				for(var step in data.steps) {
					if(!data.steps.hasOwnProperty(step)) {
						continue;
					}
					steps.push(step);
					$("li").addClass("installation-step incomplete " + step)
						.html("<div class=\"title\">" + data.steps[step] +
							  "<span class=\"dots\">.</span>" +
							  "</div><div class=\"progress\"></div>")
						.appendTo(list);
				}
				currentStep = 0;
				progress = 1;
				nextStep();
				$(".content").append(list);
			},
			error: function(jqXHR) {
				var error = $("div").addClass("ac_form_warning").text("An unexpected error occurred.");
				try {
					var response = $.parseJSON(jqXHR.responseText);
					if(typeof response === "object" && response.hasOwnProperty("error")) {
						error.text(response.error);
					}
				} catch(e) {}
				$(".content").prepend(error);
			}
		});
	})
})(jQuery);
