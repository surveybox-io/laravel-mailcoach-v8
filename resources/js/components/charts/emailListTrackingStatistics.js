document.addEventListener('alpine:init', () => {
    Alpine.data('emailListTrackingStatisticsChart', () => ({
        chartData: {},
        chart: null,
        zoomed: false,
        resetZoom() {
            if (!this.chart) {
                return;
            }

            this.chart.resetZoom();
            this.zoomed = false;
        },
        renderChart: function (chartData) {
            const chart = document.getElementById('chart-tracking');

            this.chartData = chartData;

            let c = false;

            Chart.helpers.each(Chart.instances, function (instance) {
                if (instance.canvas.id === 'chart-tracking') {
                    c = instance;
                }
            });

            if (c) {
                c.destroy();
            }

            this.chart = new Chart(chart.getContext('2d'), {
                type: 'line',
                data: {
                    labels: this.chartData.labels,
                    datasets: [
                        {
                            label: 'Open rate',
                            borderColor: '#3461D6',
                            backgroundColor: '#3461D6',
                            pointBackgroundColor: '#3461D6',
                            pointBorderColor: '#3461D6',
                            borderRadius: 5,
                            data: this.chartData.openRate,
                        },
                        {
                            label: 'Click rate',
                            borderColor: '#0FBA9E',
                            backgroundColor: '#0FBA9E',
                            pointBackgroundColor: '#0FBA9E',
                            pointBorderColor: '#0FBA9E',
                            borderRadius: 5,
                            data: this.chartData.clickRate,
                        },
                        {
                            label: 'Unsubscribe rate',
                            borderColor: '#ED5E58',
                            backgroundColor: '#ED5E58',
                            pointBackgroundColor: '#ED5E58',
                            pointBorderColor: '#ED5E58',
                            borderRadius: 5,
                            data: this.chartData.unsubscribeRate,
                            hidden: true,
                        },
                        {
                            label: 'Bounce rate',
                            borderColor: '#EDA758',
                            backgroundColor: '#EDA758',
                            pointBackgroundColor: '#EDA758',
                            pointBorderColor: '#EDA758',
                            borderRadius: 5,
                            data: this.chartData.bounceRate,
                            hidden: true,
                        },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                            },
                        },
                        tooltip: {
                            backgroundColor: 'rgba(37, 42, 63, 1)',
                            titleSpacing: 4,
                            bodySpacing: 8,
                            padding: 20,
                            displayColors: false,
                            callbacks: {
                                label: function (context) {
                                    let label = context.dataset.label || '';
                                    let value = context.raw;

                                    if (typeof value === 'number') {
                                        value = Math.abs(value);
                                    }

                                    return `${label}: ${value}%`;
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            ticks: {
                                color: 'rgba(100, 116, 139, 1)',
                                precision: 2,
                            },
                            grid: {
                                display: false,
                            },
                        },
                        x: {
                            ticks: {
                                autoSkip: false,
                                //maxRotation: 0,
                                display: false,
                                color: 'rgba(100, 116, 139, 1)',
                            },
                            grid: {
                                borderColor: 'rgba(100, 116, 139, .2)',
                                borderDash: [5, 5],
                                zeroLineColor: 'rgba(100, 116, 139, .2)',
                                zeroLineBorderDash: [5, 5],
                            },
                        },
                    },
                },
            });
        },
    }));
});
