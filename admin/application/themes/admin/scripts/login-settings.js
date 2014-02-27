(function($) {
	var table  = $(".ac-login-settings");
	$(".ac-noscript", table).remove();
	$(table).on("click", ".ac-delete-button", function() {
		var el = $(this).closest(".ac-group-settings");
		if($(".ac-group-settings", table).length > 1) {
			el.remove();
		} else {
			$("input", el).val("");
		}
	});
	$(".ac-add-group", table).bind("click", function() {
		$(".ac-group-settings", table).eq(0)
			.clone()
			.insertBefore($(this).closest("tr"))
			.find("input")
			.val("")
			.eq(0)
			.focus();
	});
	$(".ac-delete-server", table).bind("click", function(e) {
		if(!confirm(AquaCore.l("ragnarok-server", "confirm-delete"))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
})(jQuery);
