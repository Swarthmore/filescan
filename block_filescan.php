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
     * @return stdClass
     */
    public function get_content() {

        global $COURSE;
        global $CFG;
        global $DB;
        global $PAGE;

        require_once($CFG->dirroot . '/course/lib.php');

        $table = 'block_filescan_files';

        $results['pass']    = $DB->count_records($table, ['status' => 'pass']);
        $results['fails']   = $DB->count_records($table, ['status' => 'fail']);
        $results['errors']  = $DB->count_records($table, ['status' => 'error']);
        $results['checks']  = $DB->count_records($table, ['status' => 'check']);

        $context            = context_course::instance($COURSE->id);
        $canview            = has_capability('block/filescan:viewpages', $context);
        $canviewadmin       = has_capability('block/filescan:viewadminreport', $context);

        $this->content = new stdClass;

        // Check if the user has the viewadminreport capability. If they do, then the URL and Summary are changed.
        if ($canviewadmin && ($this->page->pagetype == 'my-index' || $this->page->pagetype == 'site-index')) {

            $url = new moodle_url('/blocks/filescan/admin.php');

            // Determine if the file scan block content has been previously cached or not.
            $cache = cache::make('block_filescan', 'filescan');
            $filescancache = $cache->get(0);   // Hard code 0 = admin.
            if ($filescancache) {
                $filescansummary = $filescancache;
            } else {
                $filescansummary = $this->generate_summary();
                $result = $cache->set(0, $filescansummary);
            }

            $this->title = get_string('reportheading', 'block_filescan');
            $this->content->text = $filescansummary;

            $this->content->footer = html_writer::link($url, get_string('reportheading', 'block_filescan'));

        } else if (!$canviewadmin && ($this->page->pagetype == 'site-index' || $this->page->pagetype == 'my-index')) {
            $this->content = new stdClass;
            $this->content->text = "";
            $this->content->footer = "";

        } else if ($canview) {

            // Determine course metadata.
            $coursename       = $COURSE->fullname;
            $courseshortname  = $COURSE->shortname;
            $courseurl        = course_get_url($COURSE);

            // Determine if the file scan has been previously cached or not.
            $cache = cache::make('block_filescan', 'filescan');
            $filescancache = $cache->get($COURSE->id);

            if ($filescancache) {
                $filescansummary = $filescancache;
            } else {
                $filescansummary = $this->generate_summary($COURSE->id);
                $result = $cache->set($COURSE->id, $filescansummary);
            }

            $url = new moodle_url('/blocks/filescan/view.php', ['courseid' => $COURSE->id]);
            $this->content->text = $filescansummary;
            $this->content->footer = html_writer::link($url, get_string('viewdetailspage', 'block_filescan'));

        } else {
            $this->content = new stdClass;
            $this->content->text = "";
            $this->content->footer = "";
        }

        if ($this->content !== null) {
            return $this->content;
        }
    }
}
