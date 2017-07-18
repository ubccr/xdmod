module.exports = {
	tab: "#main_tab_panel__metric_explorer",
	metricCatalogEntryByName: function(name) {
		return "//div[@id=\"metric_explorer\"]//ul[@class=\"x-tree-root-ct x-tree-arrows\"]//a[@class=\"x-tree-node-anchor\"]//span[contains(text(), \"" + name + "\")]";
	},
	metricCatalogEntryExpanderByName: function(name) {
		return "//div[@id=\"metric_explorer\"]//ul[@class=\"x-tree-root-ct x-tree-arrows\"]//span[contains(text(), \"" + name + "\")]/ancestor::li//img[@class=\"x-tree-ec-icon x-tree-elbow-end-plus\"]";
	},
	metricContextEntryByName: function(name) {
		return "//div[@class=\"x-menu x-menu-floating x-layer x-menu-nosep\"]//span[contains(text(), \"" + name + "\")]";
	},
	title: "//*[local-name()=\'svg\']/*[local-name()=\'text\' and @class=\'highcharts-title\']/*[local-name()=\'tspan\']",
	contextMenuItemByText: function(text) {
		return "//div[@class=\"x-menu x-menu-floating x-layer x-menu-nosep\"]//span[contains(text(), \"" + text + "\")]";
	},
	resizableWindowByTitle: function(title) {
		return "//div[contains(@class, \"x-window\") and contains(@class, \"x-resizable-pinned\") and contains(@style, \"visibility: visible\")]//span[@class=\"x-window-header-text\" and text()[contains(.,\"" + title + "\")]]";
	},
	rawDataEntryByIndex: function(index) {
		index = index !== undefined ? index : 1;
		return "(//div[contains(@class, 'x-window') and contains(@style, 'visibility: visible')]//div[contains(@class, 'x-grid3-body')]//div[contains(@class, 'x-grid3-cell-inner') and contains(@class, 'x-grid3-col-local_job_id')])[" + index + "]";
	},
	startDate: "#metric_explorer input[id^=start_field_ext]",
	endDate: "#metric_explorer input[id^=end_field_ext]",
	container: "#metric_explorer",
	load: {
		button: function meLoadButtonId() {
			return "button=Load Chart";
		},
		firstSaved: ".x-menu-floating:not(.x-hide-offsets) .x-grid3-body .x-grid3-row-first",
		// This will return the id for the chart number specified in a zero based manner.
		// This does not take into account the selected item
		chartNum: function meChartByIndex(number) {
			number = number + 1;
			return ".x-menu-floating:not(.x-hide-offsets) .x-grid3-body > div:nth-child(" + number + ")";
		}
	},
	addData: {
		button: ".x-btn-text.add_data",
		secondLevel: ".x-menu-floating:not(.x-hide-offsets):not(.x-menu-nosep)"
	},
	data: {
		button: "button=Data",
		container: "",
		modal: {
			updateButton: "button=Update",
			groupBy: {
				input: "input[name=dimension]"
			}
		}
	},
	deleteChart: "",
	undo: function meUndoButtonId() {
		return "#" + $container("button.x-btn-text-icon")
			.closest("table")
			.attr("id");
	},
	options: {
		aggregate: "#aggregate_cb",
		button: "#metric_explorer button.chartoptions",
		trendLine: "#me_trend_line",
		swap: "#me_chart_swap_xy",
		title: "div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*=\"visibility: visible\"] #me_chart_title"
	},
	chart: {
		svg: "#metric_explorer > div > .x-panel-body-noborder > .x-panel-noborder svg",
		title: "#hc-panelmetric_explorer svg .undefinedtitle",
		titleInput: "div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*=\"visibility: visible\"] input[type=text]",
		titleOkButton: "div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*=\"visibility: visible\"] table.x-btn.x-btn-noicon.x-box-item:first-child button",
		titleCancelButton: "div.x-menu.x-menu-floating.x-layer.x-menu-nosep[style*=\"visibility: visible\"] table.x-btn.x-btn-noicon.x-box-item:last-child button",
		contextMenu: {
			container: "#metric-explorer-chartoptions-context-menu",
			legend: "#metric-explorer-chartoptions-legend",
			addData: "#metric-explorer-chartoptions-add-data",
			addFilter: "#metric-explorer-chartoptions-add-filter"

		},
		axis: "#metric_explorer .highcharts-xaxis-labels"
	},
	catalog: {
		container: "#metric_explorer > div > .x-panel-body-noborder > .x-border-panel:not(.x-panel-noborder)",
		tree: "#metric_explorer > div > .x-panel-body-noborder > .x-border-panel:not(.x-panel-noborder) .x-tree-root-ct"
	},
	buttonMenu: {
		firstLevel: ".x-menu-floating:not(.x-hide-offsets)"
	}
};
