(function($) {
	var chartData,
		colors,
		i, zIndex;
	colors = [ "#21A4EB", "#ED427E", "#31CC4D", "#E0BD31" ];
	chartData = {
		chart: {
			height: 250,
			ignoreHiddenSeries: false
		},
		title: { text: AquaCore.l("ragnarok", "online-stats") },
		xAxis: {
			title: { enabled: false },
			gridLineColor: "#000000",
			labels: {
				style: {
					fontSize: "11px",
					fontWeight: "bold",
					color: "#8ba0ba"
				}
			},
			categories: [
				AquaCore.l("week", 0),
				AquaCore.l("week", 1),
				AquaCore.l("week", 2),
				AquaCore.l("week", 3),
				AquaCore.l("week", 4),
				AquaCore.l("week", 5),
				AquaCore.l("week", 6)
			]
		},
		yAxis: {
			min: 0,
			title: { enabled: false },
			labels: {
				style: {
					fontSize: "10px",
					fontWeight: "bold",
					color: "#8ba0ba"
				}
			}
		},
		legend: {
			layout: "vertical",
			align: "right",
			verticalAlign: "top"
		},
		tooltip: {
			shared: true
		},
		series: []
	};
	zIndex = AquaCore.settings["onlineStats"].length + 1;
	for(i = 0; i < AquaCore.settings["onlineStats"].length; ++i) {
		chartData.series[i] = {
			name: AquaCore.l("application", i === 0 ? "this-week" : "weeks-ago-" + (i > 1 ? "p" : "s"), i),
			data: [],
			zIndex: --zIndex
		};
		for(var k in AquaCore.settings["onlineStats"][i]) {
			if(AquaCore.settings["onlineStats"][i].hasOwnProperty(k)) {
				chartData.series[i].data.push(AquaCore.settings["onlineStats"][i][k]);
			}
		}
		if(colors[i]) {
			chartData.series[i].color = colors[i];
		}
	}
	$("#online-stats").highcharts(chartData);
})(jQuery);
