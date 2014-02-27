(function($) {
	$("#news-form").on("submit", function(e) {
		var count = $("input[name=\"posts[]\"]:checked", this).length;
		if($("select[name=action]", this).val() === "delete" &&
		   (count === 1 && !confirm(AquaCore.l("news", "confirm-delete-s")) ||
			count > 1 && !confirm(AquaCore.l("news", "confirm-delete-p")))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
})(jQuery);
