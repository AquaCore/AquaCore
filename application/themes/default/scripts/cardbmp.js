(function($){
	$(document).tooltip({
		items: "[ac-ro-card]",
		tooltipClass: "ac-ro-cardbmp",
		position: {
			my: "center bottom",
			at: "center top",
			using: function( position, feedback ) {
				$(this)
					.css(position)
					.addClass("y-" + feedback.vertical)
					.addClass("x-" + feedback.horizontal);
			}
		},
		hide: {
			duration: 200,
			delay: 100,
			easing: "easeInOutCirc"
		},
		show: {
			duration: 200,
			easing: "easeInOutCirc"
		},
		content: function() {
			return "<img src=\"" + $(this).attr("ac-ro-card") + "\">";
		}
	});
})(jQuery);