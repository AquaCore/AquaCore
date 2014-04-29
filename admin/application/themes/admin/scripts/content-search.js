(function($) {
	$("#content-form").on("submit", function(e) {
		var count = $("input[name=\"content[]\"]:checked", this).length;
		if($("select[name=action]", this).val() === "delete" &&
		   (count === 1 && !confirm(AquaCore.l("content", "confirm-delete-s")) ||
			count > 1 && !confirm(AquaCore.l("content", "confirm-delete-p")))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
})(jQuery);
