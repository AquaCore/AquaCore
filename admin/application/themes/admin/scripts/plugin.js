(function($){
	var form = document.getElementById("plugin-form");
	$(".ac-settings").each(function() {
		$(this).dialog({
			closeText: "x",
			minHeight: 10,
			maxHeight: 700,
			width: 450,
			modal: true,
			resizable: false,
			draggable: false,
			title: AquaCore.l("settings", "plugin-settings")
		}).dialog("close");
		new AquaCore.AjaxForm($("form", this).get(0), {
			dataType: "json",
			async: false,
			beforeSend: formBeforeSend,
			success: formSuccess,
			error: function(x, y, z) {
				console.log(x, y, z);
			}
		});
	});
	$(".ac-action-plugin-settings", form).bind("click", function(e) {
		$(document.getElementById("plugin-settings" + $(this).attr("ac-plugin-id"))).dialog("open");
		e.preventDefault();
		e.stopPropagation();
		return false;
	});
	$("[name=x-bulk]", form).bind("click", function(e) {
		var len = $("[name=\"plugins[]\"]:checked", form).length;
		if(($("select[name=action]", form).val() === "delete") &&
			(len === 1 && !confirm(AquaCore.l("plugin", "confirm-delete-s"))) ||
			(len > 1 && !confirm(AquaCore.l("plugin", "confirm-delete-p")))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
	$(".ac-action-delete", form).bind("click", function(e) {
		if(!confirm(AquaCore.l("plugin", "confirm-delete-s"))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
})(jQuery);
