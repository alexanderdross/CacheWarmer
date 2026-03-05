/* global Chart, sfChartData, jQuery */
(function ($) {
	'use strict';

	if (typeof sfChartData === 'undefined') {
		return;
	}

	// Tab navigation for page detail.
	$(document).on('click', '.sf-page-detail .nav-tab', function (e) {
		e.preventDefault();
		var tab = $(this).data('tab');
		$('.sf-page-detail .nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		$('.sf-tab-panel').removeClass('sf-tab-active');
		$('#' + tab).addClass('sf-tab-active');

		// Render charts on tab switch (Chart.js needs visible canvas).
		if (tab === 'sf-tab-overview') {
			renderTrendChart();
			renderPositionChart();
		}
		if (tab === 'sf-tab-trends') {
			renderWeeklyTrendChart();
		}
	});

	$(document).ready(function () {
		renderTrendChart();
		renderPositionChart();
	});

	var trendChartInstance = null;
	var positionChartInstance = null;
	var weeklyChartInstance = null;

	function renderTrendChart() {
		var canvas = document.getElementById('sf-trend-chart');
		if (!canvas || !sfChartData.daily_trend || !sfChartData.daily_trend.length) {
			return;
		}

		if (trendChartInstance) {
			trendChartInstance.destroy();
		}

		var labels = sfChartData.daily_trend.map(function (d) {
			return d.snapshot_date;
		});
		var clicks = sfChartData.daily_trend.map(function (d) {
			return parseInt(d.clicks, 10);
		});
		var impressions = sfChartData.daily_trend.map(function (d) {
			return parseInt(d.impressions, 10);
		});
		var positions = sfChartData.daily_trend.map(function (d) {
			return parseFloat(d.position);
		});

		trendChartInstance = new Chart(canvas.getContext('2d'), {
			type: 'line',
			data: {
				labels: labels,
				datasets: [
					{
						label: 'Clicks',
						data: clicks,
						borderColor: '#2271b1',
						backgroundColor: 'rgba(34, 113, 177, 0.1)',
						fill: true,
						tension: 0.3,
						yAxisID: 'y'
					},
					{
						label: 'Impressions',
						data: impressions,
						borderColor: '#72aee6',
						backgroundColor: 'transparent',
						borderDash: [5, 5],
						tension: 0.3,
						yAxisID: 'y'
					},
					{
						label: 'Position',
						data: positions,
						borderColor: '#d63638',
						backgroundColor: 'transparent',
						tension: 0.3,
						yAxisID: 'y1'
					}
				]
			},
			options: {
				responsive: true,
				interaction: {
					mode: 'index',
					intersect: false
				},
				plugins: {
					legend: {
						position: 'top'
					}
				},
				scales: {
					y: {
						type: 'linear',
						position: 'left',
						title: { display: true, text: 'Clicks / Impressions' },
						beginAtZero: true
					},
					y1: {
						type: 'linear',
						position: 'right',
						title: { display: true, text: 'Position' },
						reverse: true,
						grid: { drawOnChartArea: false }
					},
					x: {
						ticks: {
							maxTicksLimit: 10
						}
					}
				}
			}
		});
	}

	function renderPositionChart() {
		var canvas = document.getElementById('sf-position-chart');
		if (!canvas || !sfChartData.pos_dist) {
			return;
		}

		if (positionChartInstance) {
			positionChartInstance.destroy();
		}

		var labels = Object.keys(sfChartData.pos_dist);
		var values = Object.values(sfChartData.pos_dist);

		if (values.reduce(function (a, b) { return a + b; }, 0) === 0) {
			return;
		}

		positionChartInstance = new Chart(canvas.getContext('2d'), {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [{
					label: 'Keywords',
					data: values,
					backgroundColor: [
						'#00a32a',
						'#2271b1',
						'#dba617',
						'#d63638',
						'#646970'
					]
				}]
			},
			options: {
				responsive: true,
				plugins: {
					legend: { display: false }
				},
				scales: {
					y: {
						beginAtZero: true,
						title: { display: true, text: 'Number of Keywords' },
						ticks: { stepSize: 1 }
					},
					x: {
						title: { display: true, text: 'Position Range' }
					}
				}
			}
		});
	}

	function renderWeeklyTrendChart() {
		var canvas = document.getElementById('sf-weekly-trend-chart');
		if (!canvas || !sfChartData.weekly_trend || !sfChartData.weekly_trend.length) {
			return;
		}

		if (weeklyChartInstance) {
			weeklyChartInstance.destroy();
		}

		var labels = sfChartData.weekly_trend.map(function (d) {
			return d.date;
		});
		var clicks = sfChartData.weekly_trend.map(function (d) {
			return d.clicks;
		});
		var positions = sfChartData.weekly_trend.map(function (d) {
			return d.position;
		});

		weeklyChartInstance = new Chart(canvas.getContext('2d'), {
			type: 'line',
			data: {
				labels: labels,
				datasets: [
					{
						label: 'Weekly Clicks',
						data: clicks,
						borderColor: '#2271b1',
						backgroundColor: 'rgba(34, 113, 177, 0.15)',
						fill: true,
						tension: 0.3,
						yAxisID: 'y'
					},
					{
						label: 'Avg Position',
						data: positions,
						borderColor: '#d63638',
						backgroundColor: 'transparent',
						tension: 0.3,
						yAxisID: 'y1'
					}
				]
			},
			options: {
				responsive: true,
				interaction: {
					mode: 'index',
					intersect: false
				},
				scales: {
					y: {
						type: 'linear',
						position: 'left',
						title: { display: true, text: 'Clicks' },
						beginAtZero: true
					},
					y1: {
						type: 'linear',
						position: 'right',
						title: { display: true, text: 'Position' },
						reverse: true,
						grid: { drawOnChartArea: false }
					}
				}
			}
		});
	}

})(jQuery);
