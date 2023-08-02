<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version information for block_a11y_check
 *
 * @package   block_a11y_check
 * @copyright 2023 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

class block_a11y_check extends block_base
{

  public function init()
  {
    $this->title = get_string('pluginname', 'block_a11y_check');
  }


  public function has_config()
  {
    return true;
  }


  public function applicable_formats()
  {
    return [
      'site' => true,
      'my' => true,
      'course' => true
    ];
  }

  /**
   * Get the data required to render the block.
   */
  public function get_stats(string $courseId): array
  {

    // Moodle global to access database operations.
    global $DB;

    // This query joins the {files} table with the local_a11y_check tables, returning stats
    // about the files themselves, and their scan results.
    $sql = 'select f.id as "fileid", f.filesize as "filesize", f.filename as "filename", ' .
      'c.id as "courseid", c.shortname as "courseshortname", c.fullname as "coursefullname", ' .
      'lacq.id as "scanid", lactp.hastext as "hastext", lactp.hastitle as "hastitle", ' .
      'lactp.haslanguage as "haslanguage", lactp.hasbookmarks as "hasbookmarks", ' .
      'lactp.istagged as "istagged", lactp.pagecount as "pagecount", lacq.status as "scanstatus" ' .
      'from {files} f ' .
      'inner join {context} ctx on ctx.id = f.contextid ' .
      'inner join {course_modules} cm on cm.id = ctx.instanceid ' .
      'inner join {course} c on c.id = cm.course ' .
      'left outer join {local_a11y_check_pivot} lacp on lacp.fileid = f.id and lacp.courseid = c.id ' .
      'left outer join {local_a11y_check_queue} lacq on lacq.id = lacp.scanid ' .
      'left outer join {local_a11y_check_type_pdf} lactp on lacq.id = lactp.scanid ' .
      'where c.id = :course ' .
      "and f.mimetype = 'application/pdf' " .
      'and ctx.contextlevel = 70';

    // Query the database and save the results as a recordset.
    $recordset = $DB->get_recordset_sql($sql, ['course' => $courseId]);

    // Create the results object.
    $results = [
      "totals" => [
        "scanned" => 0,
        "inqueue" => 0,
        "notinqueue" => 0,
        "pass" => 0,
        "warn" => 0,
        "fail" => 0
      ],
      "pdfs" => [
        "pass" => [],
        "warn" => [],
        "fail" => []
      ]
    ];

    // Iterate through each $record in $recordset, adding both the queue status and scan
    // results (if they exist) to $results along the way.
    foreach ($recordset as $record) {

      // TODO: Figure out a way to create the file URL so that it can be linked to in the template.

      // If the record has yet to be queued, $record->scanid will be NULL
      if (is_null($record->scanid)) {
        $results["totals"]["notinqueue"]++;
      }

      // If the record is in the queue but hasn't been scanned, $record->scantatus will be '0'
      // @ See local/a11y_check/locallib.php
      else if ($record->scanstatus == 0) {
        $results["totals"]["inqueue"]++;
      }

      // If the record has already been scanned, $record->status will be 1
      // @ See local/a11y_check/locallib.php
      else if ($record->scanstatus == 1) {
        $results["totals"]["scanned"]++;
      }

      // Determine the a11y level of the file.
      // ✅ Accessible if file passes all rules
      // ⚠ Partially accessible if files passes any of the rules
      // ❌ Inaccessible if no checks pass

      // Ignore any files that haven't been scanned.
      if (is_null($record->scanid) || $record->scanstatus == 0) {
        continue;
      }

      // Set $mod based on a11y of file.
      // $mod is either "pass", "warn", or "fail"
      if (
        $record->hastext == 1
        && $record->hastitle == 1
        && $record->haslanguage == 1
        && $record->hasbookmarks == 1
        && $record->istagged == 1
      ) {
        $mod = "pass";
        $results["totals"]["pass"]++;
      } else if (
        $record->hastext == 0
        && $record->hastitle == 0
        && $record->haslanguage == 0
        && $record->hasbookmarks == 0
        && $record->istagged == 0
      ) {
        $mod = "fail";
        $results["totals"]["fail"]++;
      } else {
        $mod = "warn";
        $results["totals"]["warn"]++;
      }

      $results["pdfs"][$mod][] = [
        "filename" => $record->filename,
        "filesize" => $record->filesize,
        "hastext" => $record->hastext,
        "hastitle" => $record->hastitle,
        "haslanguage" => $record->haslanguage,
        "hasbookmarks" => $record->hasbookmarks,
        "istagged" => $record->istagged,
        "pagecount" => $record->pagecount,
        "scanstatus" => $record->scanstatus
      ];

      $results["totals"][$mod]++;

    }

    $recordset->close();

    return $results;
  }

  /**
   * @throws moodle_exception
   */
  public function get_content()
  {

    global $COURSE;
    global $CFG;
    global $PAGE;

    require_once($CFG->dirroot . '/course/lib.php');

    // TODO: Cache the block
    if ($this->content !== null) {
      return $this->content;
    }

    // Create the DOM element the plugin will attach to.
    $this->content = new stdClass();
    $this->content->text = '<div id="block-a11y-check-root"></div>';
    $this->content->footer = '';


    // Generate the results.
    $data = $this->get_stats(strval($COURSE->id));

    // This pages requires an AMD module.
    $PAGE->requires->js_call_amd('block_a11y_check/init', 'init', [$data]);

  }
}
