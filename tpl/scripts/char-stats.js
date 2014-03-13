(function($) {
	$("#char-stats").highcharts({
		chart: {
			polar: true,
			height: 230,
			width: 230,
			backgroundColor: "rgba(255, 255, 255, 0)"
		},
		credits: {
			enabled: false
		},
		title: {
			text: AquaCore.settings["charStats"]["title"]
		},
		pane: {
			startAngle: 0,
			endAngle: 360
		},
		legend: {
			enabled: false
		},
		tooltip: {
			headerFormat: "",
			valuePrefix: "",
			formatter: function() {
				return this.point.category + ": <span style=\"font-size: .85em\">" + this.y + "</span>";
			},
			backgroundColor: "rgba(255, 255, 255, 0)",
			borderColor: "rgba(255, 255, 255, 0)",
			borderRadius: 0,
			shadow: false,
			style: {
				fontWeight: "bold",
				color: "#64768C",
				padding: 0
			}
		},
		xAxis: {
			tickInterval: 60,
			min: 0,
			max: 360,
			labels: {
				style: {
					fontSize: "11px",
					fontWeight: "bold",
					color: "#8ba0ba"
				}
			},
			categories: {
				0:   AquaCore.l("ragnarok-stats", "str"),
				60:  AquaCore.l("ragnarok-stats", "vit"),
				120: AquaCore.l("ragnarok-stats", "dex"),
				180: AquaCore.l("ragnarok-stats", "int"),
				240: AquaCore.l("ragnarok-stats", "agi"),
				300: AquaCore.l("ragnarok-stats", "luk")
			}
		},
		yAxis: {
			min: 0
		},
		plotOptions: {
			series: {
				pointStart: 0,
				pointInterval: 60
			},
			column: {
				grouping: false,
				pointPlacement: "on",
				pointPadding: 0,
				groupPadding: 0
			}
		},
		series: [{
			type: "area",
			color: "#6CC94B",
			data: [
				AquaCore.settings["charStats"]["str"],
				AquaCore.settings["charStats"]["vit"],
				AquaCore.settings["charStats"]["dex"],
				AquaCore.settings["charStats"]["int"],
				AquaCore.settings["charStats"]["agi"],
				AquaCore.settings["charStats"]["luk"],
			]
		}]
	});
})(jQuery);