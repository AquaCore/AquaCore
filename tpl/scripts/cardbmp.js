(function($) {
	$("[ac-ro-card]").tooltip({
		tooltipClass: "ac-card-bmp",
		position: {
			my: "center+5 bottom-7",
			at: "center-5 top"
		},
		hide: null,
		show: null,
		content: function() {
			return $("<span/>")
				.append($("<div/>").addClass("ac-tooltip-top"))
				.append($("<div/>").width(150).addClass("ac-tooltip-content").append($("<img/>").attr("src", $(this).attr("ac-ro-card"))))
				.append($("<div/>").addClass("ac-tooltip-bottom"));
		}
	});
})(jQuery);
