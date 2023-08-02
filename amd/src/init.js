/* eslint-disable */
import $ from 'jquery';
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';
import ChartJS from 'core/chartjs';
import ModalFactory from 'core/modal_factory';
import {download_table_as_csv, renderFailIcon, renderSuccessIcon} from './util'

/**
 * This function acts as the main entry point and renderer for the plugin. It will attach to DOM elements created in
 * the templates/**.mustache, and populate them with data.
 *
 * @param {object} data Information about the scan queue and scan results
 * @param {object} data.totals
 * @param {number} data.totals.scanned How many document have been scanned
 * @param {number} data.totals.inqueue How many documents are waiting to be scanned
 * @param {number} data.totals.notinqueue How many documents are not yet in the queue
 * @param {object} data.pdfs
 * @param {object[]} data.pdfs.pass An array of PDFs that meet 100% of a11y checks performed by scanner
 * @param {object[]} data.pdfs.warn An array of PDFs that meet between 100% and 0% of a11y checks performed by scanner
 * @param {object[]} data.pdfs.fail An array of PDFs that meet 0% of a11y checks performed by scanner
 **/
export const init = (data) => {

  /**
   * Create stats breakdown in pie graph and attach to the DOM.
   * Pie slices are pas, warn, and fail.
   */
  function renderPieChart() {

    // Create the canvas element.
    const canvas = $('<canvas/>')
      .attr({
        id: '#block-a11y-check-pie-chart-canvas',
        'aria-label': 'A11y check pie chart describing the a11y status of the course PDFs.'
      })

    const ctx = canvas[0].getContext('2d')

    // Append the canvas to the DOM.
    $('#block-a11y-check-pie-chart-root')
      .append(
        $('<h6/>')
          .addClass('mb-2')
          .text('PDF Accessibility')
      )
      .append(canvas)

    const chartData = {
      labels: [`Pass (${data.pdfs.pass.length})`, `Warn (${data.pdfs.warn.length})`, `Fail (${data.pdfs.fail.length})`],
      datasets: [{
        label: 'A11y Check',
        data: [
          data.pdfs.pass.length,
          data.pdfs.warn.length,
          data.pdfs.fail.length
        ],
        backgroundColor: [
          // Green.
          '#5cb85c',
          // Yellow.
          '#f0ad4e',
          // Red.
          '#d9534f'
        ]
      }]
    }
    new ChartJS(ctx, {
      type: 'pie',
      data: chartData
    })
  }

  function createNoDataParagraph() {
    return $('<p/>')
      .text("No data to show")
  }

  /**
   * Creates details table showing breakdown of stats returned by the scanner.
   * @returns {*|jQuery|void}
   */
  function createDetailsTable() {
    // Create the table content.
    const $table = $('<table/>')
      .attr('id', 'block-a11y-check-table')
      .addClass('table table-bordered table-hover table-responsive')
      .append(
        $('<thead/>')
          .append(
            $('<tr/>')
              .append(
                $('<th/>')
                  .attr('scope', 'col')
                  .text('Filename')
              )
              .append(
                $('<th/>')
                  .attr('scope', 'col')
                  .text('Bookmarks')
              )
              .append(
                $('<th/>')
                  .attr('scope', 'col')
                  .text('Lang')
              )
              .append(
                $('<th/>')
                  .attr('scope', 'col')
                  .text('Text')
              )
              .append(
                $('<th/>')
                  .attr('scope', 'col')
                  .text('Title')
              )
              .append(
                $('<th/>')
                  .attr('scope', 'col')
                  .text('Tagged')
              )
              .append(
                $('<th/>')
                  .attr('scope', 'col')
                  .text('Pages')
              )
          )
      )
    // Create the table body.
    const $tableBody = $('<tbody/>')

    // Concatenate an array with all pdfs in data arg.
    const pdfs = [].concat(
      data.pdfs.pass,
      data.pdfs.warn,
      data.pdfs.fail
    )

    // Generate the table rows
    const $tableRows = pdfs.map(
      pdf => $('<tr/>')
        .append(
          $('<td/>')
            .addClass('text-truncate')
            .append(
              $('<a/>')
                .attr('href', pdf.url)
                .text(pdf.filename)
            )
        )
        .append(
          $('<td/>')
            .append(pdf.hasbookmarks === "1"
              ? $(renderSuccessIcon())
              : $(renderFailIcon()))
        )
        .append(
          $('<td/>')
            .append(pdf.haslanguage === "1"
              ? $(renderSuccessIcon())
              : $(renderFailIcon()))
        )
        .append(
          $('<td/>')
            .append(pdf.hastext === "1"
              ? $(renderSuccessIcon())
              : $(renderFailIcon()))
        )
        .append(
          $('<td/>')
            .append(pdf.hastitle === "1"
              ? $(renderSuccessIcon())
              : $(renderFailIcon()))
        )
        .append(
          $('<td/>')
            .append(pdf.istagged === "1"
              ? $(renderSuccessIcon())
              : $(renderFailIcon()))
        )
        .append(
          $('<td/>')
            .append(pdf.pagecount >= 1
              ? $('<span/>').text(pdf.pagecount)
              : $(renderFailIcon()))
        )
    )

    // Append the table rows to the table.
    $tableBody.append($tableRows)
    $table.append($tableBody)

    return $table
  }

  /**
   * Creates a download button that when clicked, downloads a CSV version of the table.
   * @param $table
   * @returns {*|jQuery|void}
   */
  function createDownloadButton($table) {
    // Create the download to csv button.
    return $('<button/>')
      .addClass('btn btn-secondary mb-2')
      .text('Download to CSV')
      .click(() => download_table_as_csv('block-a11y-check-table'))
  }

  /**
   * Create the button element that when clicked, opens the modal.
   * @param modal
   * @returns {*|void}
   */
  function createModalTriggerButton(modal) {
    // Create the button triggering the modal.
    return $('<button/>')
      .addClass('btn btn-secondary')
      .text('Details')
      .click(() => modal.show())
  }

  /**
   * Creates a modal window that displays a table of each PDF scanned by the plugin in the current course.
   * @returns {Promise<*>}
   */
  async function createModal() {

    const $table = createDetailsTable()

    // Create the modal body first, so we can attach it during the modal's instantiation.
    const $modalBody = $('<div/>')
      .append(createDownloadButton($table))
      .append($table)

    // Create the modal.
    const modal = await ModalFactory.create({
      title: 'A11y Check Details',
      body: $modalBody,
      // TODO: Add last updated timestamp
      footer: '<p>Last updated mm dd at ##:##:##</p>',
      large: true
    })

    // This allows the modal to have 95% of screen width.
    modal.getRoot()[0].childNodes[1].style.maxWidth = '95vw' || undefined

    return modal
  }

  // Create the context. This gets passed to the template.
  const context = {
    name: 'A11y Check'
  };

  // This will call the function to load and render our template.
  Templates.renderForPromise('block_a11y_check/summary', context)
    .then(({html, js}) => {

      // Render the template.
      Templates.appendNodeContents("#block-a11y-check-root", html, js);

      // Create the modal.
      createModal()
        .then(modal => {

          // Append the button trigger to the DOM.
          $("#block-a11y-check-more-details-root").append(
            createModalTriggerButton(modal)
          )

          // Render the queue stats.
          if (data.pdfs.pass.length > 0 || data.pdfs.warn.length > 0 || data.pdfs.fail.length > 0) {
            renderPieChart()
          } else {
            $('#block-a11y-check-root').append(
              createNoDataParagraph()
            )
          }

          // TODO: Render the queue stats.

        }).catch((error) => displayException(error));

    })
    // Deal with this exception (Using core/notify exception function is recommended).
    .catch((error) => displayException(error));
};
