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

class block_a11y_check extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_a11y_check');
    }


    public function has_config() {
        return true;
    }


    public function applicable_formats() {
        return [
            'site' => true,
            'my' => true,
            'course' => true
        ];
    }


    /**
     * Get summary of the accessibility checks for the course.
     * @param string $courseid
     * @return array
     * @throws dml_exception
     */
    public function get_aggregate_course_stats(string $courseid): array {

        global $DB;

        // Create the query.
        $sql = 'select f.id as "fileid", f.filesize as "filesize", f.filename as "filename", '.
                'c.id as "courseid", c.shortname as "courseshortname", c.fullname as "coursefullname", '.
                'lacq.id as "scanid", lactp.hastext as "hastext", lactp.hastitle as "hastitle", '.
                'lactp.haslanguage as "haslanguage", lactp.hasbookmarks as "hasbookmarks", '.
                'lactp.istagged as "istagged", lactp.pagecount as "pagecount", lacq.status as "scanstatus" '.
                'from {files} f '.
                'inner join {context} ctx on ctx.id = f.contextid '.
                'inner join {course_modules} cm on cm.id = ctx.instanceid '.
                'inner join {course} c on c.id = cm.course '.
                'left outer join {local_a11y_check_pivot} lacp on lacp.fileid = f.id and lacp.courseid = c.id '.
                'left outer join {local_a11y_check_queue} lacq on lacq.id = lacp.scanid '.
                'left outer join {local_a11y_check_type_pdf} lactp on lacq.id = lactp.scanid '.
                'where c.id = :courseid '.
                "and f.mimetype = 'application/pdf' ".
                'and ctx.contextlevel = 70';

        // TODO: get file object from moodle file API
//        $url = moodle_url::make_pluginfile_url();

        $recordset = $DB->get_recordset_sql($sql, ['courseid' => $courseid]);

        $results = [
            "totalpdfs" => 0,
            "scanned" => [
                "total" => 0,
                "pdfs" => []
            ],
            "inqueue" => [
                "total" => 0,
                "pdfs" => []
            ],
            "notinqueue" => [
                "total" => 0,
                "pdfs" => []
            ]
        ];

        foreach ($recordset as $record) {

            // If the record has yet to be queued, $record->scanid will be NULL
            if (is_null($record->scanid)) {
                $results['notinqueue']["pdfs"][] = [
                    "filename" => $record->filename,
                    "filesize" => $record->filesize,
                ];
            }

            // If the record is in the queue but hasn't been scanned, $record->scantatus will be '0'
            // @ See local/a11y_check/locallib.php
            else if ($record->scanstatus == 0) {
                $results['inqueue']["pdfs"][] = [
                    "filename" => $record->filename,
                    "filesize" => $record->filesize
                ];
            }

            // If the record has already been scanned, $record->status will be 1
            // @ See local/a11y_check/locallib.php
            else if ($record->scanstatus == 1) {
                $results['scanned']["pdfs"][] = [
                    "filename" => $record->filename,
                    "filesize" => $record->filesize,
                    "hastext" => $record->hastext,
                    "hastitle" => $record->hastitle,
                    "haslanguage" => $record->haslanguage,
                    "hasbookmarks" => $record->hasbookmarks,
                    "istagged" => $record->istagged,
                    "pagecount" => $record->pagecount
                ];
            }

            $results["totalpdfs"]++;
        }

        // Aggregate the number of each type of result item.
        $results['scanned']['total'] = count($results['scanned']['pdfs']);
        $results['inqueue']['total'] = count($results['inqueue']['pdfs']);
        $results['notinqueue']['total'] = count($results['notinqueue']['pdfs']);

        $recordset->close();

        return $results;

    }

    /**
     * Return a11y totals for a course.
     * @param string $courseid
     * @return array
     * @throws dml_exception
     */
    public function get_a11y_totals(string $courseid): array {

        global $DB;

        // Create the query.
        $sql = 'select f.id as "fileid", f.filesize as "filesize", f.filename as "filename", '.
            'c.id as "courseid", c.shortname as "courseshortname", c.fullname as "coursefullname", '.
            'lacq.id as "scanid", lactp.hastext as "hastext", lactp.hastitle as "hastitle", '.
            'lactp.haslanguage as "haslanguage", lactp.hasbookmarks as "hasbookmarks", '.
            'lactp.istagged as "istagged", lactp.pagecount as "pagecount", lacq.status as "scanstatus" '.
            'from {files} f '.
            'inner join {context} ctx on ctx.id = f.contextid '.
            'inner join {course_modules} cm on cm.id = ctx.instanceid '.
            'inner join {course} c on c.id = cm.course '.
            'left outer join {local_a11y_check_pivot} lacp on lacp.fileid = f.id and lacp.courseid = c.id '.
            'left outer join {local_a11y_check_queue} lacq on lacq.id = lacp.scanid '.
            'left outer join {local_a11y_check_type_pdf} lactp on lacq.id = lactp.scanid '.
            'where c.id = :courseid '.
            "and f.mimetype = 'application/pdf' ".
            'and ctx.contextlevel = 70';

        $recordset = $DB->get_recordset_sql($sql, ['courseid' => $courseid]);

        // Create the results object.
        $results = [
            "pass" => [
                "total" => 0,
                "pdfs" => []
            ],
            "warn" => [
                "total" => 0,
                "pdfs" => []
            ],
            "fail" => [
                "total" => 0,
                "pdfs" => []
            ],
        ];

        foreach ($recordset as $record) {

            // Determine the a11y level of the file.
            // ✅ Accessible if file passes all rules
            // ⚠ Partially accessible if files passes any of the rules
            // ❌ Inaccessible if no checks pass

            // Ignore any files that haven't been scanned.
            if (is_null($record->scanid) || $record->scanstatus == 0 ) {
                continue;
            }

            // $mod is either "pass", "warn", or "fail"
            // Set $mod based on a11y of file.
            if (
                $record->hastext == 1
                && $record->hastitle == 1
                && $record->haslanguage == 1
                && $record->hasbookmarks == 1
                && $record->istagged == 1
            ) {
                $mod = "pass";
            } else if (
                $record->hastext == 0
                && $record->hastitle == 0
                && $record->haslanguage == 0
                && $record->hasbookmarks == 0
                && $record->istagged == 0
            ) {
                $mod = "fail";
            } else {
                $mod = "warn";
            }

            $results[$mod]['pdfs'][] = [
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

            $results[$mod]["total"]++;

        }

        $recordset->close();

        // Before returning the results, we need to remove the passing files from the partially passing count.
        // $results["warn"]["total"] -= $results["pass"]["total"];

        return $results;

    }

    /**
     * @throws moodle_exception
     */
    public function get_content() {

        global $COURSE;
        global $CFG;
        global $OUTPUT;
        global $PAGE;

        require_once($CFG->dirroot . '/course/lib.php');

        if ($this->content !== null) {
            return $this->content;
        }

        // Create the DOM element the plugin will attach to.
        $this->content = new stdClass();
        $this->content->text = '<div id="block_a11y_check_root"></div>';
        $this->content->footer = '';

        // Generate the results.
        $results = $this->get_aggregate_course_stats($COURSE->id);
        $a11yresults = $this->get_a11y_totals($COURSE->id);


        // Create the response. This gets ingested by the template.
        $data = [
            'scanresults' => $results,
            'a11yresults' => $a11yresults
        ];

        // This pages requires an AMD module.
        $PAGE->requires->js_call_amd('block_a11y_check/init', 'init', [$results, $a11yresults]);

    }
}
