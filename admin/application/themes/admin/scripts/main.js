(function($) {
	$("body").removeClass("noscript");

	$("[ac-checkbox-toggle]").bind("change", function () {
		var checkboxes = document.getElementsByName(this.getAttribute("ac-checkbox-toggle"));
		for(var i = 0, n = checkboxes.length; i < n; i++) {
			checkboxes[i].checked = this.checked;
		}
	});

	$(".menu-option-submenu").each(function() {
		var menu = $(this);
		menu.data('__aquacore.menuWidth', menu.outerWidth()).css({"width" : 0, "display": "none"});
	});
	$("li.has-submenu").bind("mouseenter", function() {
		var menu = $(this).find(".menu-option-submenu").first();
		menu.stop(true, false)
			.css("display", "block")
			.animate({"width" : menu.data('__aquacore.menuWidth')}, {duration: 400, easing: "easeInOutCirc"});
	}).bind("mouseleave", function() {
		var menu = $(this).find(".menu-option-submenu");
		menu.stop(true, false)
			.animate({"width" : 0}, {
				duration: 400,
				easing: "easeInOutCirc",
				done: function() {
					menu.css("display", "none")
				}
			});
	});
	$(".ac-tooltip").tooltip({
		tooltipClass: "ac-tooltip-wrapper",
		show: null,
		hide: null,
		position: {
			my: "center bottom-10",
			at: "center top"
		},
		content: function() {
			var content = $(this).attr("title") || $(this).attr("alt");
			if(!content) {
				return null;
			}
			return "<div class=\"ac-tooltip-top\"></div><div class=\"ac-tooltip-content\">" + content + "</div><div class=\"ac-tooltip-bottom\"></div>";
		}
	});

	$("[ac-default-submit]").each(function() {
		var form = $(this).closest("form");
		$("input, button", form).keypress(function(e) {
			if((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
				$("[ac-default-submit]", form).click();
				e.preventDefault();
				e.stopPropagation();
				return false;
			} else {
				return true;
			}
		});
	});
})(jQuery);
