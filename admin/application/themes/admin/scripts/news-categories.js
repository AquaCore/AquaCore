(function($){
	var success = function(data) {
		var id = $(this.form).closest(".ac-settings").attr("id").replace("category-settings-", "");
		var context = document.getElementById("category-info-" + id);
		if(data["data"].hasOwnProperty("image")) {
			if(data["data"]["image"]) {
				$(".category-image-" + id)
					.css("display", "")
					.attr("src", data["data"]["image"]);
				$(".ac-delete-button", this.form).css("display", "");
			} else {
				$(".category-image-" + id).css("display", "none");
				$(".ac-delete-button", this.form).css("display", "none");
			}
			delete(data["data"]["image"]);
		}
		if(data["data"].hasOwnProperty("name")) {
			$("[ac-field=name]", context).html(data["data"]["name"]
				.replace(/&/g, "&amp;")
				.replace(/</g, "&lt;")
				.replace(/>/g, "&gt;")
				.replace(/"/g, "&quot;")
				.replace(/'/g, "&#39;")
				.replace(/\//g, "&#x2F;"));
		}
		if(data["data"].hasOwnProperty("description")) {
			$("[ac-field=description]", context).html(data["data"]["description"]);
		}
		if(data["data"].hasOwnProperty("slug")) {
			$("[ac-field=slug]", context)
				.html(data["data"]["slug"])
				.attr("href", AquaCore.buildUrl({
					"baseDir": AquaCore.BASEDIR,
					"path": [ "news", "category", data["data"]["slug"] ]
				}));
		}
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
		title: AquaCore.l("content", "edit-category")
	}).dialog("close").each(function() {
		new AquaCore.AjaxForm($("form", this).get(0), {
			dataType: "json",
			async: false,
			beforeSend: formBeforeSend,
			success: success
		});
	});
	$(".ac-action-edit").bind("click", function() {
		$("#category-settings-" + $(this).val()).dialog("open");
	});
	$("button[name=x-delete]").bind("click", function(e) {
		if(!confirm(AquaCore.l("content", "confirm-delete-category"))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
		return true;
	});
	$("input[name=x-bulk-action]").bind("click", function(e) {
		var len = $("[name=\"categories[]\"]:checked").length;
		if((len > 1 && !confirm(AquaCore.l("content", "confirm-delete-categories"))) ||
		   (len === 1 && !confirm(AquaCore.l("content", "confirm-delete-category")))) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
		return true;
	});
})(jQuery);
