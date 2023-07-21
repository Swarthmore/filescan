/* eslint-disable */
import $ from 'jquery';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import ChartJS from 'core/chartjs';
import ModalFactory from 'core/modal_factory';

// Helper function to convert bytes to megabytes
const sizeInMb = sizeInBytes => (sizeInBytes / (1024*1024)).toFixed(2);

// Helper function to convert an HTML table into a CSV
function downloadAsCSV(tableEle, separator = ','){
  let csvRows = []
  //only get direct children of the table in question (thead, tbody)
  Array.from(tableEle.children).forEach(function(node){
    //using scope to only get direct tr of node
    node.querySelectorAll(':scope > tr').forEach(function(tr){
      let csvLine = []
      //again scope to only get direct children
      tr.querySelectorAll(':scope > td').forEach(function(td){
        //clone as to not remove anything from original
        let copytd = td.cloneNode(true)
        let data
        if(copytd.dataset.val) data = copytd.dataset.val.replace(/(\r\n|\n|\r)/gm, '')
        else {
          Array.from(copytd.children).forEach(function(remove){
            //remove nested elements before getting text
            remove.parentNode.removeChild(remove)
          })
          data = copytd.textContent.replace(/(\r\n|\n|\r)/gm, '')
        }
        data = data.replace(/(\s\s)/gm, ' ').replace(/"/g, '""')
        csvLine.push('"'+data+'"')
      })
      csvRows.push(csvLine.join(separator))
    })
  })
  var a = document.createElement("a")
  a.style = "display: none; visibility: hidden" //safari needs visibility hidden
  a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvRows.join('\n'))
  a.download = 'testfile.csv'
  document.body.appendChild(a)
  a.click()
  a.remove()
}

export const init = (results, a11yresults) => {

  async function renderQueueStats() {
    // Create the data for the chart.
    // [0] = text, [1] = language, [3] = layout
    const data = [
      results.scanned.total,
      results.inqueue.total,
      results.notinqueue.total
    ];

    // Create the progress element.
    const elementArray = ['Scanned', 'In Queue', 'Not in Queue'].map((mod, index) => {
      let background;
      if (mod === 'Scanned') {
        background = 'bg-success'
      }
      if (mod === 'In Queue') {
        background = 'bg-secondary'
      }
      if (mod === 'Not in Queue') {
        background = 'bg-warning'
      }
      // layout gets default progressbar

      return `<div class="progress-bar ${background}" role="progressbar" aria-label="${mod}" aria-valuenow="${data[index]}" aria-valuemin="0" aria-valuemax="${results.totalpdfs}" style="width: ${data[index]}%"></div>`;

    })

    $('#a11yDetailsProgress')
      .append($(`<h6>PDF Scan Progress</h6>`))
      .append($(`<div class="progress">${elementArray.join(' ').replace(/\n\s+/g, '')}</div>`))
      .append($(`<div class="d-flex justify-content-between"><p>${results.inqueue.total} in queue</p><p>${results.notinqueue.total + results.scanned.total} found</p></div>`))

    // Create the results table.
    let tablecontent = '<table id="a11y-check-details-table" class="table table-bordered table-hover table-sm">';
    tablecontent += `<thead><tr><th>Filename</th><th>Filesize</th><th>Bookmarks</th><th>Has Lang</th><th>Has text</th><th>Has title</th><th>Is tagged</th><th>Pages</th><th>status</th></tr></thead>`

    // Get all pdfs in a11yresults.fail, a11yresults.pass, and a11yresults.warn
    const failingpdfs = a11yresults.fail.pdfs
    const passingpdfs = a11yresults.pass.pdfs
    const warningpdfs = a11yresults.warn.pdfs

    const allpdfs = [].concat(failingpdfs, passingpdfs, warningpdfs)

    for (let i=0; i<allpdfs.length; i++) {
      tablecontent += '<tr>' +
          `<td>${allpdfs[i].filename}</td>` +
          `<td>${sizeInMb(allpdfs[i].filesize)}</td>` +
          `<td>${allpdfs[i].hasbookmarks ? 'Yes' : 'No'}</td>` +
          `<td>${allpdfs[i].haslanguage ? 'Yes' : 'No'}</td>` +
          `<td>${allpdfs[i].hastext ? 'Yes' : 'No'}</td>` +
          `<td>${allpdfs[i].hastitle ? 'Yes' : 'No'}</td>` +
          `<td>${allpdfs[i].istagged ? 'Yes' : 'No'}</td>` +
          `<td>${allpdfs[i].pagecount || 'No'}</td>` +
          `<td>${allpdfs[i].hastitle ? 'Yes' : 'No'}</td>` +
          `<td>${allpdfs[i].scanstatus}</td>`
    '</tr>'
    }

    tablecontent += '</table>';

    // Create the download to csv button
    const $downloadToCsvButton = $('<button>Download to CSV</button>')

    // Create the modal body
    const $modalbody = $('<div></div>')
    $modalbody.append(tablecontent).append($downloadToCsvButton)

    // Create the modal
    const modal = await ModalFactory.create({
      title: 'A11y Check Details',
      body: $modalbody,
      footer: '<p>Last updated</p>',
      large: true
    })

    // I think this needs to come after the modal is created...
    $downloadToCsvButton.click(() => downloadAsCSV(document.getElementById('a11y-check-details-table')))

    // Create the button triggering the modal
    const $modalbtn = $('<button class="btn btn-secondary">Details</button>')
    $modalbtn.click(() => modal.show())

    // Append the button trigger to the DOM
    $("#more-details-container").append($modalbtn)

  }

//  function renderA11yScanChart() {
//    const canvas = document.querySelector('#a11yScanChart')
//
//    if (!canvas) {
//      throw new Error('Could not find canvas {#a11yRadarChart}')
//    }
//
//    const ctx = canvas.getContext('2d')
//
//    new ChartJS(ctx, {
//      type: 'pie',
//      data: {
//        labels: ['Scanned', 'In queue', 'Not in queue'],
//        datasets: [{
//          label: 'Scan Queue',
//          data: [results.scanned.total, results.inqueue.total, results.notinqueue.total],
//          backgroundColor: ['blue', 'green', 'orange']
//        }],
//      }
//    })
//  }
//
//  function renderA11yDetailsChart() {
//    const canvas = document.querySelector('#a11yRadarChart')
//
//    if (!canvas) {
//      throw new Error('Could not find canvas {#a11yRadarChart}')
//    }
//
//    const ctx = canvas.getContext('2d')
//
//    const chartData = {
//      labels: [
//        'Has Text',
//        'Has Title',
//        'Has Language',
//        'Is Tagged'
//      ],
//      datasets: [{
//        label: 'A11y Breakdown',
//        data: [80, 70, 55, 99],
//        fill: true,
//        backgroundColor: 'rgba(255, 99, 132, 0.2)',
//        borderColor: 'rgb(255, 99, 132)',
//        pointBackgroundColor: 'rgb(255, 99, 132)',
//        pointBorderColor: '#fff',
//        pointHoverBackgroundColor: '#fff',
//        pointHoverBorderColor: 'rgb(255, 99, 132)'
//      }]
//    }
//
//    new ChartJS(ctx, {
//      type: 'radar',
//      data: chartData
//    })
//
//  }

  function renderA11yPieChart() {

    // Create the canvas element.
    const canvas = $('<canvas/>').attr({
      id: '#a11yPieChart',
      'aria-label': 'A11y check pie chart describing the a11y status of the course PDFs.'
    })

    const ctx = canvas[0].getContext('2d')

    // Append a11y fallback

    // canvas.append(```<p>
    //   There are {{a11yresults.pass.total}} PDFs that passed all a11y checks.
    //   There are {{a11yresults.warn.total}} PDFs that are partially accessible.
    //   There are {{a11yresults.fail.total}} PDFs that failed all a11y checks.
    // </p>```)

    // Append the canvas to the DOM
    $('#a11y-chart-container').append(canvas)

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
