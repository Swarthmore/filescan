/* eslint-disable */
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import Drawer from 'core/drawer';
import ChartJS from 'core/chartjs';

export const init = (results, a11yresults) => {



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

      // Here eventually I have my compiled template, and any javascript that it generated.
      // The templates object has append, prepend and replace functions.
      //Templates.appendNodeContents('#page-content', html, js);
      // alert('Done');
  })
  // Deal with this exception (Using core/notify exception function is recommended).
  .catch((error) => displayException(error));
};
