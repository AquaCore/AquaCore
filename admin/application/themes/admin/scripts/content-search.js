(function($) {
	$("#content-form").on("submit", function(e) {
		var checkboxes = $("input[name=\"content[]\"]:checked", this),
			count = checkboxes.length;
		if($("select[name=action]", this).val() === "delete") {
			if(count > 1 && !confirm(AquaCore.l("content", "confirm-delete-p"))) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			} else if(count === 1) {
				var title = checkboxes.eq(0).closest("tr").find(".content-title a").text();
				if(!confirm(AquaCore.l("content", "confirm-delete-s", title))) {
					e.preventDefault();
					e.stopPropagation();
					return false;
				}
			}
		}
	});
})(jQuery);
