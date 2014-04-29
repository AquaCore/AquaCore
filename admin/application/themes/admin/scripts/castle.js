(function($) {
	var castlesTable = $(".ac-castles").eq(0);
	var empty        = $("tr.empty", castlesTable);
	for(var i = 1; i < empty.length; ++i) {
		empty.eq(i).remove();
	}
	delete(empty);
	castlesTable.on("click", ".ac-delete-button", function() {
		$(this).closest("tr").remove();
	});
	$(".ac-add-castle", castlesTable).on("click", function() {
		var row = $("<tr></tr>");
		row.append($("<td></td>")
				.addClass("ac-castle-id")
				.append($("<input>")
						.attr("type", "number")
						.attr("min", "0")
						.attr("name", "casid[]")
						.attr("placeholder", AquaCore.l("ragnarok-charmap", "castle-id"))
				))
			.append($("<td></td>")
				.addClass("ac-castle-name")
				.append($("<input>")
						.attr("type", "text")
						.attr("name", "casname[]")
						.attr("placeholder", AquaCore.l("ragnarok-charmap", "castle-name"))
				))
			.append($("<td></td>")
				.addClass("ac-castle-options")
				.append($("<button></button>")
						.addClass("ac-delete-button")
						.attr("tabindex", "-1")
						.attr("type", "button")
				))
			.insertBefore($(this).closest("tr"))
			;
		$(".ac-castle-id input", row).focus();
	});
})(jQuery);
