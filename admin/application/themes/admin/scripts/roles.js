(function($) {
	var sidebar = $("#sidebar"),
		permissions = $("[name=permission]", sidebar),
		success = function(data) {
			var id = $(this.form).closest(".ac-settings").attr("id").replace("edit-role", ""),
				context = $(".ac-permission-list", this.form),
				row = $("tr[ac-role-id=\"" + id + "\"]");
			$("input", context).prop("checked", false).prop("disabled", false);
			for(var key in data.data["permission"]) {
				if(!data.data["permission"].hasOwnProperty(key)) {
					continue;
				}
				$("input[value=\"" + key + "\"]", context)
					.prop("checked", true)
					.prop("disabled", data.data["permission"][key] === 2);
			}
			if(data.data["color"]) {
				var color = data.data["color"];
				$(".ac-role-color", row).html("<span style=\"font-weight: bold; color: " + color + "\">" + color + "</span>");
			} else {
				$(".ac-role-color", row).text(AquaCore.l("application", "none"));
			}
			if(data.data["background"]) {
				var bg = data.data["background"];
				$(".ac-role-background", row).html("<span style=\"font-weight: bold; color: " + bg + "\">" + bg + "</span>");
			} else {
				$(".ac-role-background", row).text(AquaCore.l("application", "none"));
			}
			$(".ac-role-name", row).html(data.data["name_formatted"]);
			$(".ac-role-description", row).html(data.data["description"]
					.replace(/&/g, "&amp;")
					.replace(/</g, "&lt;")
					.replace(/>/g, "&gt;")
					.replace(/"/g, "&quot;")
					.replace(/'/g, "&#39;")
					.replace(/\//g, "&#x2F;"));
			delete data.data["permission"];
			delete data.data["name_formatted"];
			formSuccess.call(this, data);
		};
	$(".ac-settings").dialog({
		closeText: "x",
		minHeight: 10,
		maxHeight: 700,
		width: 450,
		modal: true,
		resizable: false,
		draggable: false,
		title: AquaCore.l("role", "edit-role")
	}).dialog("close").each(function() {
		new AquaCore.AjaxForm($("form", this).get(0), {
			dataType: "json",
			async: false,
			cache: false,
			beforeSend: formBeforeSend,
			success: success
		});
	});
	$("option", permissions).bind("click", function() {
		var desc = $(this).attr("title");
		if(desc) {
			desc = "<div class=\"ac-permission-name\">" + $(this).html() + "</div>" + desc;
		}
		$(".ac-permission-description", sidebar).html(desc);
	});
	$(".ac-delete-role").bind("click", function(e) {
		if(!confirm(AquaCore.l("role", "confirm-delete-s"))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
	$(".ac-edit-role").bind("click", function(e) {
		$("#edit-role" + $(this).attr("ac-role-id")).dialog("open");
		e.preventDefault();
		e.stopPropagation();
		return false;
	});
})(jQuery);
