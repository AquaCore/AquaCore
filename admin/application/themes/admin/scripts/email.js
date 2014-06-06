(function($) {
	$(".email-altbody").css("display", "none");
	$("button.tab").on("click", function() {
		if($(this).hasClass("body-tab")) {
			$(this).prop("disabled", true);
			$("button.tab.altbody-tab").prop("disabled", false);
			$(".email-body").css("display", "block");
			$(".email-altbody").css("display", "none");
		} else if($(this).hasClass("altbody-tab")) {
			$(this).prop("disabled", true);
			$("button.tab.body-tab").prop("disabled", false);
			$(".email-body").css("display", "none");
			$(".email-altbody").css("display", "block");
		}
	});
	CKEDITOR.replace("ckeditor", AquaCore.settings.CKEditorOptions);
})(jQuery);
