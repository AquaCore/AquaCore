<?php
 $page->theme->footer->enqueueScript('jquery')
	 ->type('text/javascript')
	 ->src('//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js');
 $page->theme->footer->enqueueScript('theme.install')
	 ->type('text/javascript')
	 ->append('
(function($) {
	var steps, progress, currentStep, length;
	function nextStep() {
		var step = steps[currentStep];
		$(".installation-step." + step)
			.removeClass("incomplete")
			.addClass("active");
		$.ajax({
			dataType: "json",
			cache: false,
			type: "GET",
			url: "?action=install&arg=" + step + "." + progress,
			success: function(data) {
				console.log(data, step);
				if(data.progress[0] === data.progress[1]) {
					progress = 1;
					currentStep++;
					$(".installation-step." + step)
						.removeClass("active")
						.addClass("complete")
						.find(".progress")
						.text("");
					if(currentStep === length) {
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
				console.log(jqXHR);
				var error = $("<div></div>").addClass("ac_form_warning").text("An unexpected error occurred.");
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

	$("button.next").bind("click", function(e) {
		e.preventDefault();
		e.stopPropagation();
		$(".content").text("");
		$(this).remove();
		$("button.prev").attr("disabled", "disabled").parent().attr("href", "#");
		$("#menu li a").attr("href", "#");
		$.ajax({
			dataType: "json",
			cache: false,
			type: "GET",
			url: "?action=install&arg=start",
			success: function(data) {
				var list = $("<ul></ul>").addClass("installation-steps");
				steps = [];
				length = 0;
				console.log(data);
				for(var step in data) {
					if(!data.hasOwnProperty(step)) {
						continue;
					}
					++length;
					steps.push(step);
					$("<li></li>").addClass("installation-step incomplete " + step)
						.html("<div class=\"title\">" + data[step] +
							  "<span class=\"dots\">.</span>" +
							  "<div class=\"progress\"></div></div>")
						.appendTo(list);
				}
				currentStep = 0;
				progress = 1;
				$(".content").append(list);
				nextStep();
			},
			error: function(jqXHR) {
				var error = $("<div></div>").addClass("ac_form_warning").text("An unexpected error occurred.");
				try {
					var response = $.parseJSON(jqXHR.responseText);
					if(typeof response === "object" && response.hasOwnProperty("error")) {
						error.text(response.error);
					}
				} catch(e) {}
				$(".content").prepend(error);
			}
		});
	}).attr("type", "button");
})(jQuery);
');
echo __setup('installation-ready');