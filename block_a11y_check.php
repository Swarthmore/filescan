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

    /**
     * @return array
     */
    public function applicable_formats() {
        return [
            'site' => true,
            'my' => true,
            'course' => true
        ];
    }

    /** 
     * Get summary of the accessibility checks for the course.
     * @param int $courseid
     * @return string 
     */
    private function get_stats($courseid) {

        global $DB;

        // Placeholders to count the number of accessible files based on the accessibility checks.
        $hasText = 0;
        $hasTitle = 0;
        $hasLanguage = 0;
        $hasBookmarks = 0;
        $isTagged = 0;
        $totalPages = 0;
        $totalFiles = 0;
        $unscanned = 0;
        $errors = array();

        $sql = "SELECT ctx.id as ContextId, f.id as FileId, actp.scanid as ScanId, f.filename as FileName, actp.hastext as HasText, actp.hastitle as HasTitle, actp.haslanguage as HasLanguage, actp.hasbookmarks as HasBookmarks, actp.istagged as IsTagged, actp.pagecount as PageCount FROM {context} ctx INNER JOIN {course} c on ctx.instanceid = c.id INNER JOIN {files} f on ctx.id = f.contextid INNER JOIN {local_a11y_check_type_pdf} actp ON actp.contenthash = f.contenthash WHERE ctx.instanceid = :courseid";
        $recordset = $DB->get_recordset_sql($sql, ['courseid' => $courseid]);

        if ($recordset->valid()) {
            foreach ($recordset as $record) {

                if ($record->hastext == 1) {
                    $hasText++;
                }

                if ($record->hastitle == 1) {
                    $hasTitle++;
                }

                if ($record->haslanguage == 1) {
                    $hasLanguage++;
                }

                if ($record->hasbookmarks == 1) {
                    $hasBookmarks++;
                }

                if ($record->istagged == 1) {
                    $isTagged++;
                }

                if ($record->pagecount > 0) {
                    $totalPages += $record->pagecount;
                }

                $totalFiles++;

            }
        }

        $recordset->close();

        $stats = array(
            "hasText" => $hasText,
            "hasTitle" => $hasTitle,
            "hasLanguage" => $hasLanguage,
            "hasBookmarks" => $hasBookmarks,
            "isTagged" => $isTagged,
            "totalPages" => $totalPages,
            "totalFiles" => $totalFiles,
            "unscanned" => $unscanned,
            "errors" => $errors
        );

        return $stats;

    }

    /**
     * @return stdClass
     */
    public function get_content() {

        global $COURSE;
        global $CFG;
        global $DB;
        global $PAGE;
        global $OUTPUT;

        require_once($CFG->dirroot . '/course/lib.php');

        $context = context_course::instance($COURSE->id);
        $canview = has_capability('block/a11y_check:viewpages', $context);

        $this->content = new stdClass;

        // Determine course metadata.
        $coursename = $COURSE->fullname;
        $courseshortname  = $COURSE->shortname;
        $courseurl = course_get_url($COURSE);

        // Get the summary of the accessibility checks for the course.
        $stats = $this->get_stats($COURSE->id);

        $url = new moodle_url('/blocks/a11y_check/view.php', ['courseid' => $COURSE->id]);
        $this->content->text = "I am a block";
        $this->content->text = $OUTPUT->render_from_template('block_a11y_check/summary', $stats);
        $this->content->footer = "I am a footer";

        if ($this->content !== null) {
            return $this->content;
        }
    }
}
