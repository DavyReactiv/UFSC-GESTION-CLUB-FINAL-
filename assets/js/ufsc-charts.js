/**
 * UFSC Simple Charts - Basic chart functionality
 * A lightweight replacement for Chart.js for license statistics
 */

(function() {
    'use strict';

    window.UFSCCharts = {
        /**
         * Create a simple pie chart using CSS and HTML
         */
        createPieChart: function(canvasId, data, options = {}) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            const container = canvas.parentNode;
            canvas.style.display = 'none';

            // Create chart container
            const chartDiv = document.createElement('div');
            chartDiv.className = 'ufsc-pie-chart';
            chartDiv.style.cssText = `
                position: relative;
                width: 200px;
                height: 200px;
                border-radius: 50%;
                margin: 0 auto;
                overflow: hidden;
            `;

            const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
            let currentAngle = 0;
            const colors = options.colors || ['#2e2d54', '#d40000', '#4CAF50', '#FF9800', '#9C27B0'];

            // Create legend
            const legend = document.createElement('div');
            legend.className = 'ufsc-chart-legend';
            legend.style.cssText = `
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                margin-top: 10px;
                gap: 10px;
            `;

            data.labels.forEach((label, index) => {
                const value = data.datasets[0].data[index];
                const percentage = ((value / total) * 100).toFixed(1);
                const angle = (value / total) * 360;
                
                // Create pie slice
                const slice = document.createElement('div');
                slice.style.cssText = `
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    clip-path: polygon(50% 50%, 50% 0%, ${50 + 50 * Math.cos((currentAngle - 90) * Math.PI / 180)}% ${50 + 50 * Math.sin((currentAngle - 90) * Math.PI / 180)}%, ${50 + 50 * Math.cos((currentAngle + angle - 90) * Math.PI / 180)}% ${50 + 50 * Math.sin((currentAngle + angle - 90) * Math.PI / 180)}%);
                    background-color: ${colors[index % colors.length]};
                `;
                
                chartDiv.appendChild(slice);
                currentAngle += angle;

                // Create legend item
                const legendItem = document.createElement('div');
                legendItem.style.cssText = `
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    font-size: 12px;
                `;
                
                const colorBox = document.createElement('div');
                colorBox.style.cssText = `
                    width: 12px;
                    height: 12px;
                    background-color: ${colors[index % colors.length]};
                    border-radius: 2px;
                `;
                
                const labelText = document.createElement('span');
                labelText.textContent = `${label}: ${value} (${percentage}%)`;
                
                legendItem.appendChild(colorBox);
                legendItem.appendChild(labelText);
                legend.appendChild(legendItem);
            });

            container.appendChild(chartDiv);
            container.appendChild(legend);
        },

        /**
         * Create a simple bar chart using CSS and HTML
         */
        createBarChart: function(canvasId, data, options = {}) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            const container = canvas.parentNode;
            canvas.style.display = 'none';

            const chartDiv = document.createElement('div');
            chartDiv.className = 'ufsc-bar-chart';
            chartDiv.style.cssText = `
                display: flex;
                align-items: end;
                height: 200px;
                gap: 10px;
                padding: 20px;
                border-bottom: 2px solid #ccc;
                border-left: 2px solid #ccc;
            `;

            const maxValue = Math.max(...data.datasets[0].data);
            const colors = options.colors || ['#2e2d54', '#d40000', '#4CAF50', '#FF9800', '#9C27B0'];

            data.labels.forEach((label, index) => {
                const value = data.datasets[0].data[index];
                const height = (value / maxValue) * 160;
                
                const barContainer = document.createElement('div');
                barContainer.style.cssText = `
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    min-width: 40px;
                `;
                
                const bar = document.createElement('div');
                bar.style.cssText = `
                    width: 30px;
                    height: ${height}px;
                    background-color: ${colors[index % colors.length]};
                    border-radius: 4px 4px 0 0;
                    position: relative;
                    transition: all 0.3s ease;
                `;
                
                // Add value label on top
                const valueLabel = document.createElement('div');
                valueLabel.style.cssText = `
                    position: absolute;
                    top: -20px;
                    left: 50%;
                    transform: translateX(-50%);
                    font-size: 11px;
                    font-weight: bold;
                    color: #333;
                `;
                valueLabel.textContent = value;
                bar.appendChild(valueLabel);
                
                const labelDiv = document.createElement('div');
                labelDiv.style.cssText = `
                    font-size: 11px;
                    text-align: center;
                    margin-top: 5px;
                    max-width: 60px;
                    word-wrap: break-word;
                `;
                labelDiv.textContent = label;
                
                barContainer.appendChild(bar);
                barContainer.appendChild(labelDiv);
                chartDiv.appendChild(barContainer);

                // Add hover effect
                bar.addEventListener('mouseenter', function() {
                    this.style.opacity = '0.8';
                });
                bar.addEventListener('mouseleave', function() {
                    this.style.opacity = '1';
                });
            });

            container.appendChild(chartDiv);
        }
    };

    // Auto-initialize charts on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Look for any charts that need to be rendered
        if (window.ufscChartsData) {
            window.ufscChartsData.forEach(function(chartConfig) {
                if (chartConfig.type === 'pie') {
                    UFSCCharts.createPieChart(chartConfig.canvasId, chartConfig.data, chartConfig.options);
                } else if (chartConfig.type === 'bar') {
                    UFSCCharts.createBarChart(chartConfig.canvasId, chartConfig.data, chartConfig.options);
                }
            });
        }
    });
})();