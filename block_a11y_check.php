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
    private function get_aggregate_course_stats(string $courseid): array {

        global $DB;

        // [x] TODO: Fix hard-coding of courseid
        // [x] TODO: Make sure using HEREDOC with variables is safe, or rewrite
        // [x] TODO: Address concerns about half of a course's files may be picked up, while the other half may not show up in the results.
        // [x] TODO: return NULL if there are no records found.. or a total count of scanned records, or a different structure completely that shows the status of each file and not just aggregates.

//        $sql = 'select , '.
//                'f.filename as "filename", f.filesize as "filesize" '.
//            'from {local_a11y_check_type_pdf} lactp '.
//            'inner join {local_a11y_check_pivot} lacp on lacp.scanid = lactp.scanid '.
//            'inner join {files} f on f.id = lacp.fileid '.
//            'where lacp.courseid = :courseid';


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

        // [x] TODO: Put scanned and unscanned files into separate keys
        $results = [
            "scanned" => [],
            "inqueue" => [],
            "notinqueue" => []
        ];

        foreach ($recordset as $record) {

            // If the record has yet to be queued, $record->scanid will be NULL
            if (is_null($record->scanid)) {
                $results['notinqueue'][] = [
                    "filename" => $record->filename,
                    "filesize" => $record->filesize,
                ];
            }

            // If the record is in the queue but hasn't been scanned, $record->scantatus will be '0'
            // @ See local/a11y_check/locallib.php
            else if ($record->scanstatus == 0) {
                $results['inqueue'][] = [
                    "filename" => $record->filename,
                    "filesize" => $record->filesize
                ];
            }

            // If the record has already been scanned, $record->status will be 1
            // @ See local/a11y_check/locallib.php
            else if ($record->scanstatus == 1) {
                $results['scanned'][] = [
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
        }

        $results['counts'] = [
            'scanned' => count($results['scanned']),
            'notinqueue' => count($results['notinqueue']),
            'inqueue' => count($results['inqueue'])
        ];

        $recordset->close();
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

        $this->content = new stdClass;

        $url = new moodle_url('/blocks/a11y_check/view.php', ['courseid' => $COURSE->id]);
        $results = ['results' => $this->get_aggregate_course_stats($COURSE->id)];

        $PAGE->requires->js_call_amd('block_a11y_check/init', 'init', $results);

        $this->content->header = 'Header';
//        $this->content->text = $OUTPUT->render_from_template(
//            'block_a11y_check/summary',
//            $results
//        );
        $this->content->footer = $OUTPUT->render_from_template(
            'block_a11y_check/footer',
            array('url' => $url)
        );

    }
}
