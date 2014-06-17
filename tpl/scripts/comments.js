(function($) {
	var loading, reply, report, uid = 0;

	function _uid () {
		++uid;
		return "comment-uid-" + uid;
	}

	function deleteReplyElements() {
		if(reply) {
			if(CKEDITOR.instances.hasOwnProperty(reply.attr("id"))) {
				CKEDITOR.instances[reply.attr("id")].destroy();
			}
			reply.remove();
			reply = undefined;
		}
		if(report) {
			report.remove();
			report = undefined;
		}
	};
	$(".ac-comment-upvote, .ac-comment-downvote").on("click", function() {
		var wrapper, id, ctype, weight, self = this;
		wrapper = $(this).closest(".ac-comment");
		if(loading || !wrapper.length) return;
		id = wrapper.attr("ac-comment-id");
		ctype = wrapper.attr("ac-comment-ctype");
		weight = $(this).is(".ac-comment-upvote") ? 1 : -1;
		if(AquaCore.settings["commentRatings"][id] === weight) weight = 0;
		loading = true;
		$.ajax({
			dataType: "json",
			cache: false,
			url: AquaCore.buildUrl({
				script: "ratecomment.php",
				query: {
					"ctype": ctype,
					"comment" : id,
					"weight": weight
				}
			}),
			success: function(data) {
				if(data.success) {
					var parent = $(self).parent();
					$(".ac-comment-upvote, .ac-comment-downvote", parent).removeClass("active");
					$(".ac-comment-points", parent).text(data.rating.format());
					AquaCore.settings["commentRatings"][id] = data.rating;
					if(data.rating !== 0) $(self).addClass("active");
				}
			},
			complete: function(x, y, z) {
				loading = false;
			}
		});
	});
	$(".ac-hide-children").on("click", function(e) {
		var wrapper = $(this).closest(".ac-comment");
		if($(this).hasClass("hidden")) {
			wrapper.find(".ac-comment-children").eq(0).stop(true, true).show({
				effect: "blind",
				easing: "easeInOutCirc",
				duration: 300
			});
			$(this).removeClass("hidden");
		} else {
			wrapper.find(".ac-comment-children").eq(0).stop(true, true).hide({
				effect: "blind",
				easing: "easeInOutCirc",
				duration: 300
			});
			$(this).addClass("hidden");
		}
	});
	$(".ac-comment-reply").on("click", function(e) {
		deleteReplyElements();
		e.preventDefault();
		e.stopPropagation();
		var parent = $(this).closest(".ac-comment"),
			cke = $("<textarea></textarea>")
				.attr("name", "content")
				.attr("id", _uid()),
			frm = $("<form></form>");
		frm.attr("method", "POST")
			.attr("action", $(this).attr("href"))
			.append(cke);
		if(AquaCore.settings.contentData.allowAnonymous) {
			frm.append(
				$("<input>")
					.attr("type", "checkbox")
					.attr("value", "1")
					.attr("name", "anonymous")
					.attr("id", "anon-reply")
				)
				.append(
					$("<label></label>")
						.attr("for", "anon-reply")
						.append(AquaCore.l("comment", "comment-anonymously"))
				)
		}
		frm.append(
				$("<button>")
					.attr("type", "type")
					.attr("class", "ac-button")
					.on("click", deleteReplyElements)
					.append(AquaCore.l("comment", "cancel"))
			)
			.append(
				$("<button>")
					.attr("type", "submit")
					.attr("class", "ac-button")
					.append(AquaCore.l("comment", "reply"))
			);
		reply = $("<div></div>")
			.addClass("ac-comment-reply-wrapper")
			.append(frm)
			.insertAfter($(this).closest(".ac-comment-actions"));
		CKEDITOR.replace(cke.attr("id"), $.extend({
			toolbarCanCollapse: true,
			toolbarStartupExpanded : false
		}, AquaCore.settings.ckeComments)).on("instanceReady", function(e) {
			e.editor.setData("");
		});
		return false;
	});
	$(".ac-comment-edit").on("click", function(e) {
		var parent = $(this).closest(".ac-comment");
		if(!AquaCore.settings.commentSource.hasOwnProperty(parent.attr("ac-comment-id"))) {
			return;
		}
		deleteReplyElements();
		e.preventDefault();
		e.stopPropagation();
		var cke = $("<textarea></textarea>")
				.attr("name", "content")
				.attr("id", _uid()),
			frm = $("<form></form>");
		frm.attr("method", "POST")
			.attr("action", $(this).attr("href"))
			.append(cke);
		frm.append(
				$("<button>")
					.attr("type", "type")
					.attr("class", "ac-button")
					.on("click", deleteReplyElements)
					.append(AquaCore.l("comment", "cancel"))
			)
			.append(
				$("<button>")
					.attr("type", "submit")
					.attr("class", "ac-button")
					.append(AquaCore.l("comment", "edit"))
			);
		reply = $("<div></div>")
			.addClass("ac-comment-reply-wrapper")
			.append(frm)
			.insertAfter($(this).closest(".ac-comment-actions"));
		ckeInstance = CKEDITOR.replace(cke.attr("id"), $.extend({
			toolbarCanCollapse: true,
			toolbarStartupExpanded : false
		}, AquaCore.settings.ckeComments)).on("instanceReady", function(e) {
			e.editor.setData(AquaCore.settings.commentSource[parent.attr("ac-comment-id")]);
		});
		return false;
	});
	$(".ac-comment-report").on("click", function(e) {
		deleteReplyElements();
		e.preventDefault();
		e.stopPropagation();
		var frm = $("<form></form>");
		frm.attr("method", "POST")
			.attr("action", $(this).attr("href"))
			.append(
				$("<textarea></textarea>")
					.addClass("ac-report-field")
					.attr("name", "report")
					.attr("placeholder", AquaCore.l("comment", "report-placeholder"))
			)
			.append(
				$("<button>")
					.attr("type", "type")
					.attr("class", "ac-button")
					.on("click", deleteReplyElements)
					.append(AquaCore.l("comment", "cancel"))
			)
			.append(
				$("<button>")
					.attr("type", "submit")
					.attr("class", "ac-button")
					.append(AquaCore.l("comment", "report"))
			)
			.append($("<div></div>").css("clear", "both"));
		report = $("<div></div>")
			.addClass("ac-comment-report-wrapper")
			.append(frm)
			.insertAfter($(this).closest(".ac-comment-actions"));
		return false;
	});
	CKEDITOR.replace("cke-comment", AquaCore.settings.ckeComments);
})(jQuery);
