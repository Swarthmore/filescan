/* eslint-disable */
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import Drawer from 'core/drawer';
import ChartJS from 'core/chartjs';

export const init = (results, a11yresults) => {
    const chartData = {
      labels: ['Pass', 'Warn', 'Fail'],
      datasets: [{
        label: 'A11y Check',
        data: [a11yresults.pass.total, a11yresults.warn.total, a11yresults.fail.total],
        backgroundColor: ['green', 'yellow', 'red']
      }]
    }

  function renderA11yScanChart() {
    const canvas = document.querySelector('#a11yScanChart')

    if (!canvas) {
      throw new Error('Could not find canvas {#a11yRadarChart}')
    }

    const ctx = canvas.getContext('2d')

    new ChartJS(ctx, {
      type: 'pie',
      data: {
        labels: ['Scanned', 'In queue', 'Not in queue'],
        datasets: [{
          label: 'Scan Queue',
          data: [results.scanned.total, results.inqueue.total, results.notinqueue.total],
          backgroundColor: ['blue', 'green', 'orange']
        }],
      }
    })
  }

  function renderA11yDetailsChart() {
    const canvas = document.querySelector('#a11yRadarChart')

    if (!canvas) {
      throw new Error('Could not find canvas {#a11yRadarChart}')
    }

    const ctx = canvas.getContext('2d')

    const chartData = {
      labels: [
        'Has Text',
        'Has Title',
        'Has Language',
        'Is Tagged'
      ],
      datasets: [{
        label: 'A11y Breakdown',
        data: [80, 70, 55, 99],
        fill: true,
        backgroundColor: 'rgba(255, 99, 132, 0.2)',
        borderColor: 'rgb(255, 99, 132)',
        pointBackgroundColor: 'rgb(255, 99, 132)',
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: 'rgb(255, 99, 132)'
      }]
    }

    new ChartJS(ctx, {
      type: 'radar',
      data: chartData
    })

  }

  function renderA11yPieChart() {
    const canvas = document.querySelector('#a11yPieChart')

    if (!canvas) {
      throw new Error('Could not find canvas {#a11yPieChart}')
    }

    const ctx = canvas.getContext('2d')

    const chartData = {
      labels: ['Pass', 'Warn', 'Fail'],
      datasets: [{
        label: 'A11y Check',
        data: [a11yresults.pass.total, a11yresults.warn.total, a11yresults.fail.total],
        backgroundColor: ['green', 'yellow', 'red']
      }]
    }
    new ChartJS(ctx, {
      type: 'pie',
      data: chartData
    })
  }

  const context = {
    name: 'A11y Check',
    results: results,
    a11yresults: a11yresults
  };

  console.log({ context })

  // This will call the function to load and render our template.
  Templates.renderForPromise('block_a11y_check/summary', context)
    .then(({ html, js }) => {

      Templates.appendNodeContents(".block_a11y_check .card-body", html, js);

      renderA11yPieChart(a11yresults)
      renderA11yDetailsChart(a11yresults)
      renderA11yScanChart(results)

      // Here eventually I have my compiled template, and any javascript that it generated.
      // The templates object has append, prepend and replace functions.
      //Templates.appendNodeContents('#page-content', html, js);
      // alert('Done');
  })
  // Deal with this exception (Using core/notify exception function is recommended).
  .catch((error) => displayException(error));
};
