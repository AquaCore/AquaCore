(function($) {
	var tbl = document.getElementById("cache-settings");

	$(".optional", tbl).hide();
	$(".adapter-field").on("change", function() {
		$(".optional", tbl).hide();
		$(".optional." + $(this).val()).show();
	}).change();
})(jQuery);
