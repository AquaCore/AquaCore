(function($) {
	$(".ac-settings").dialog({
		closeText: "x",
		minHeight: 10,
		maxHeight: 700,
		width: 450,
		modal: true,
		resizable: false,
		draggable: false
	}).dialog("close");
	$(".ac-edit-user").dialog("option", "title", AquaCore.l("profile", "edit-account-admin", AquaCore.settings["accountInfo"]["displayName"]));
	$(".ac-ban-user").dialog("option", "title", AquaCore.l("profile", (AquaCore.settings["accountInfo"]["banned"] ? "unban-account" : "ban-account"), AquaCore.settings["accountInfo"]["displayName"]));
	new AquaCore.AjaxForm(document.getElementById("edit-user"), {
		dataType: "json",
		async: false,
		beforeSend: formBeforeSend,
		success: function(data) {
			$("input[type=file]", this.form).val("");
			if(data["data"].hasOwnProperty("avatar")) {
				$(".ac-delete-button", this.form).css("display", (Boolean(data["data"]["avatar"]) ? "" : "none"));
			}
			for(var key in data["data"]) {
				if(!data["data"].hasOwnProperty(key)) {
					continue;
				}
				var element = $("[ac-field=\"" + key + "\"]"),
					value   = data["data"][key];
				if(!element.length) {
					continue;
				}
				switch(key) {
					case "avatar":
						if(data["data"][key]) {
							element.attr("src", data["data"][key]);
							$("img[ac-field=\"avatar\"]", this.form).removeClass("disabled");
						} else {
							element.attr("src", AquaCore.settings["defaultAvatar"]);
							$("img[ac-field=\"avatar\"]", this.form).addClass("disabled");
						}
						break;
					case "credits":
						element.html(parseInt(value).format());
						break;
					default:
						element.html(value);
						break;
				}
			}
			formSuccess.call(this, data);
		}
	});
	new AquaCore.AjaxForm(document.getElementById("ban-user"), {
		dataType: "json",
		async: false,
		beforeSend: formBeforeSend,
		success: function(data) {
			console.log(data, $.extend({}, data.data));
			if(data["data"]["banned"]) {
				$(".ac-ban-field").css("display", "none");
				if(data["data"]["unban_date"]) {
					$(".ac-unban-date, .ac-unban-date-label").css("display", "");
					$(".ac-unban-date").html(data["data"]["unban_date_formatted"]);
				} else {
					$(".ac-unban-date, .ac-unban-date-label").css("display", "none");
				}
				$(".ac-ban-user-button").html(AquaCore.l("profile", "unban"));
				$(".ac-ban-user").dialog("option", "title", AquaCore.l("profile", "unban-account", AquaCore.settings["accountInfo"]["displayName"]));
				$("[name=reason]", this.form).attr("placeholder", AquaCore.l("profile", "unban-reason"));
				$(".ac-ban-ro-accounts .ac-form-label", this.form).html(AquaCore.l("profile", "unban-accounts"));
			} else {
				$(".ac-ban-field").css("display", "");
				$(".ac-unban-date, .ac-unban-date-label").css("display", "none");
				$(".ac-ban-user-button").html(AquaCore.l("profile", "ban"));
				$(".ac-ban-user").dialog("option", "title", AquaCore.l("profile", "ban-account", AquaCore.settings["accountInfo"]["displayName"]));
				$("[name=reason]", this.form).attr("placeholder", AquaCore.l("profile", "ban-reason"));
				$(".ac-ban-ro-accounts .ac-form-label", this.form).html(AquaCore.l("profile", "ban-accounts"));
			}
			$(".ac-account-status").html(data["data"]["status"]);
			$("input[name=\"unban_time\"], input[name=\"ban_accounts\"], textarea", this.form).val("");
			AquaCore.settings["accountInfo"]["banned"] = data["data"]["banned"];
			delete(data["data"]);
			formSuccess.call(this, data)
		}
	});
	$("button.ac-edit-user-button").bind("click", function(e) {
		$(".ac-edit-user").dialog("open");
		e.preventDefault();
		e.stopPropagation();
		return false;
	});
	$("button.ac-ban-user-button").bind("click", function(e) {
		$(".ac-ban-user").dialog("open");
		e.preventDefault();
		e.stopPropagation();
		return false;
	});
}(jQuery));
