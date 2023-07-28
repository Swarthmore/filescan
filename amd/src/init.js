/* eslint-disable */
import $ from 'jquery';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import ChartJS from 'core/chartjs';
import ModalFactory from 'core/modal_factory';
import { downloadAsCSV, renderFailIcon, renderWarningIcon, renderSuccessIcon } from './util'

/**
 * This function acts as the main entry point and renderer for the plugin. It will attach to DOM elements created in
 * the templates/**.mustache, and populate them with data.
 *
 * @param {object} data Information about the scan queue and scan results
 * @param {object} data.queue Information about the scan queue
 * @param {object} data.queue.totals
 * @param {number} data.queue.totals.scanned How many document have been scanned
 * @param {number} data.queue.totals.inqueue How many documents are waiting to be scanned
 * @param {number} data.queue.totals.notinqueue How many documents are not yet in the queue
 * @param {object} data.results Information about the scan results
 * @param {number} data.results.totals.pass How many documents meet 100% of a11y checks performed by scanner
 * @param {number} data.results.totals.warn How many documents meet between 100% and 0% of a11y checks performed by scanner
 * @param {number} data.results.totals.fail How many documents meet 0% of a11y checks performed by scanner
 * @param {object[]} data.results.pass An array of PDFs that meet 100% of a11y checks performed by scanner
 * @param {object[]} data.results.warn An array of PDFs that meet between 100% and 0% of a11y checks performed by scanner
 * @param {object[]} data.results.fail An array of PDFs that meet 0% of a11y checks performed by scanner
 **/
export const init = (data) => {}

export const init = (results, a11yresults) => {

  async function renderQueueStats() {
    // Create the data for the chart.
    // [0] = text, [1] = language, [3] = layout
    const data = [
      results.scanned.total,
      results.inqueue.total,
      results.notinqueue.total
    ];

    // console.log({ results, a11yresults })

    $('#a11yDetailsProgress').append(`
      <h6>Scan Queue Details</h6>
          <ul>
              <li>Scanned ${results.scanned.total}</li>
              <li>Queued ${results.inqueue.total}</li>
              <li>Not in queue ${results.notinqueue.total}</li>
          </ul>
      </div>`)

    // Create the results table.
    let tablecontent = '<table id="a11y-check-details-table" class="table table-bordered table-hover table-sm">';
    // TODO: Add explanations for what each header item actually represents
    tablecontent += `<thead>
      <tr>
        <th scope="col">Filename</th>
        <th scope="col">Bookmarks</th>
        <th scope="col">Lang</th>
        <th scope="col">Text</th>
        <th scope="col">Title</th>
        <th scope="col">Tagged</th>
        <th scope="col">Pages</th>
      </tr>
    </thead>`

    // Get all pdfs in a11yresults.fail, a11yresults.pass, and a11yresults.warn
    const failingpdfs = a11yresults.fail.pdfs
    const passingpdfs = a11yresults.pass.pdfs
    const warningpdfs = a11yresults.warn.pdfs

    const allpdfs = [].concat(failingpdfs, passingpdfs, warningpdfs)

    tablecontent += '<tbody>';

    // console.log({allpdfs})

    for (let i=0; i<allpdfs.length; i++) {
      // TODO: Clean this up with a template literal
      // TODO: Split up the table by context, OR add contextual color representing if a PDF is passing or not.
      tablecontent += '<tr>' +
          `<td class="text-truncate">${allpdfs[i].filename}</td>` +
          `<td>${allpdfs[i].hasbookmarks === "1" ? renderSuccessIcon() : renderFailIcon()}</td>` +
          `<td>${allpdfs[i].haslanguage === "1" ? renderSuccessIcon() : renderFailIcon()}</td>` +
          `<td>${allpdfs[i].hastext === "1" ? renderSuccessIcon() : renderFailIcon()}</td>` +
          `<td>${allpdfs[i].hastitle === "1" ? renderSuccessIcon() : renderFailIcon()}</td>` +
          `<td>${allpdfs[i].istagged === "1" ? renderSuccessIcon() : renderFailIcon()}</td>` +
          `<td>${+allpdfs[i].pagecount >= 1 ? allpdfs[i].pagecount : renderFailIcon()}</td>` +
    '</tr>'
    }

    tablecontent += '</tbody>';
    tablecontent += '</table>';

    // Create the download to csv button
    const $downloadToCsvButton = $('<button class="btn btn-secondary mb-2">Download to CSV</button>')

    // Create the modal body
    const $modalbody = $('<div></div>')

    $modalbody
        .append($downloadToCsvButton)
        .append(tablecontent)

    // Create the modal
    const modal = await ModalFactory.create({
      title: 'A11y Check Details',
      body: $modalbody,
      // TODO: Add last updated timestamp
      footer: '<p></p>',
      large: true
    })

    modal.getRoot()[0].childNodes[1].style.maxWidth = '75vw' || undefined

    // I think this needs to come after the modal is created...
    $downloadToCsvButton.click(() => downloadAsCSV(document.getElementById('a11y-check-details-table')))

    // Create the button triggering the modal
    const $modalbtn = $('<button class="btn btn-secondary">Details</button>')
    $modalbtn.click(() => modal.show())

    // Append the button trigger to the DOM
    $("#more-details-container").append($modalbtn)

  }

  function renderA11yPieChart() {

    // Create the canvas element.
    const canvas = $('<canvas/>').attr({
      id: '#a11yPieChart',
      'aria-label': 'A11y check pie chart describing the a11y status of the course PDFs.'
    })

    const ctx = canvas[0].getContext('2d')

    // Append the canvas to the DOM
    $('#a11y-chart-container')
        .append(`<h6 class="mb-2">PDF A11y Stats</h6>`)
        .append(canvas)

    const chartData = {
      labels: [`Pass (${a11yresults.pass.total})`, `Warn (${a11yresults.warn.total})`, `Fail (${a11yresults.fail.total})`],
      datasets: [{
        label: 'A11y Check',
        data: [a11yresults.pass.total, a11yresults.warn.total, a11yresults.fail.total],
        backgroundColor: ['#5cb85c', '#f0ad4e', '#d9534f']
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

  // This will call the function to load and render our template.
  Templates.renderForPromise('block_a11y_check/summary', context)
    .then(({ html, js }) => {

      // Render the template.
      Templates.appendNodeContents("#block_a11y_check_root", html, js);

      // Render the queue stats.
      renderQueueStats()
      // Only render the chart if there's data.
      if (results.scanned.total > 0) {
        renderA11yPieChart()
      }
  })
  // Deal with this exception (Using core/notify exception function is recommended).
  .catch((error) => displayException(error));
};
