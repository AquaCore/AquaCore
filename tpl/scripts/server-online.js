(function($){
	$(".ac-whos-online-marker").tooltip({
		position: {
			my: "center+5 bottom-20",
			at: "center top"
		},
		hide: null,
		show: null,
		content: function() {
			return $("<span/>")
				.append($("<div/>").addClass("ac-tooltip-top"))
				.append($("<div/>").addClass("ac-tooltip-content").append($(this).parent().find(".ac-map").clone()))
				.append($("<div/>").addClass("ac-tooltip-bottom"));
		}
	});
})(jQuery);
