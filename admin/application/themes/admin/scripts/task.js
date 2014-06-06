(function($) {
	$(".run-task:enabled").on("click", function(e) {
		var button = $(this),
			token = button.closest("form").find("input[name=runtaskid]"),
			data = {
			"x-run": button.val(),
			"runtaskid": token.val()
		};
		e.preventDefault();
		e.stopPropagation();
		button.attr("disabled", "disabled").css({ opacity: 0.7 });
		$.ajax({
			url: AquaCore.buildUrl({ path: [ "task" ] }),
			data: data,
			type: "POST",
			dataType: "json",
			success: function(data) {
				AquaCore._flash.enqueue(data.type, null, data.message);
				token.val(data.key);
			},
			complete: function() {
				button.removeAttr("disabled").css({ opacity: 1 });
			}
		});
	});
})(jQuery);
