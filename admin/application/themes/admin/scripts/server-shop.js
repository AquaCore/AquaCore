(function($){
	var tbl = document.getElementById("shop-items");
	$("tbody", tbl).sortable({
		helper: function(e, ui) {
			var element = ui.clone(),
				children = ui.children();
			element.children().each(function(i) {
				$(this).width(children.eq(i).width());
			});
			return element;
		},
		containment: "parent",
		tolerance: "pointer",
		forceHelperSize: true,
		snap: false,
		axis: "y",
		cursor: "move"
	});
	$("[name=x-bulk]", tbl).bind("click", function(e) {
		var len = $("[name=\"items[]\"]:checked", tbl).length;
		if(($("select[name=action]", tbl).val() === "delete") &&
			(len === 1 && !confirm(AquaCore.l("ragnarok", "confirm-delete-item-s"))) ||
			(len > 1 && !confirm(AquaCore.l("ragnarok", "confirm-delete-item-p")))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
})(jQuery);
