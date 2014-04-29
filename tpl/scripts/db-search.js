(function($) {
	var container = $(".ac-search");
	$("#search-type").on("change", function() {
		var enable;
		$(".item-class select, .item-class-title label").css("display", "none");
		switch(parseInt($(this).val())) {
			case 4: enable = "weapon"; break;
			case 5: enable = "armor"; break;
			case 10: enable = "ammo"; break;
			default: return;
		}
		$("#search-:type, .item-class-title label[for=search-:type]".replace(/:type/g, enable)).css("display", "");
	}).change();
	$(".toggle", container).on("click", function() {
		var wrapper = $(".wrapper", container);
		wrapper.stop(true, true);
		if(wrapper.is(":hidden")) {
			wrapper.show("blind", { easing: "easeInOutCirc" }, 400);
		} else {
			wrapper.hide("blind", { easing: "easeInOutCirc" }, 300);
		}
	});
	$("button[type=reset]", container).on("click", function() {
		var frm = $("form", container);
		$("input[type=text],input[type=number]", frm).val("").attr("value", "").change();
		$("checkbox:checked", frm).removeAttr("checked");
		$("select", frm).each(function() {
			$("option", this).removeAttr("selected");
			$("option:eq(0)", this).prop("selected", true);
			$(this).change();
		});
	});
	$(".ac-search-limit").on("change", function() {
		var query = {};
		query[$(this).attr("name")] = $(this).val();
		window.location = AquaCore.buildUrl($.extend(true, {}, AquaCore.URI, { query: query }));
	});
})(jQuery);
