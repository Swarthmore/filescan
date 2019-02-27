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
 * @package   block_filescan
 * @copyright 2018 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

class block_filescan extends block_base {

    public function init() {
        $this->title = get_string('summaryview', 'block_filescan');
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
     * @return array
     */
    private function get_course_files() {

        global $COURSE;
        global $CFG;
        global $DB;

        require_once($CFG->dirroot . '/course/lib.php');

        $cms = get_fast_modinfo($COURSE)->get_cms();
        $filelist = array();    // Array containing detailed file info.
        $fs = get_file_storage();

        // Loop through each module in the course looking for files.
        foreach ($cms as $cm) {

            if ($cm->is_user_access_restricted_by_capability()) {
                continue;
            }

            $cmtype = $cm->modname;
            $sectionnumber = $cm->get_course_module_record(true)->sectionnum;

            // Check if the resource is a folder. If it is a folder, then get all files with a mime type
            // of application/pdf and push them to $filelist
            // If the resourse is a file, then get all pdf files in the "file" resource.

            if ($cmtype === 'folder') {
                $cmfiles = $fs->get_area_files($cm->context->id, 'mod_folder', 'content', false, 'timemodified', false);
                foreach ($cmfiles as $f) {
                    if (isset($f) && ($f->get_mimetype() === 'application/pdf')) {
                        array_push($filelist, $f->get_contenthash());
                    }
                }
            } else if ($cmtype === 'resource') { // Check if the resource is a file.

                // Get files in "file" resource.
                $files = $fs->get_area_files($cm->context->id, 'mod_resource', 'content', false, 'timemodified', false);
                foreach ($files as $f) {
                    if (isset($f) && ($f->get_mimetype() === 'application/pdf')) {
                        array_push($filelist, $f->get_contenthash());
                    }
                }
            }
        }
        return $filelist;
    }

    /**
     * @param $courseid
     * @return string
     * If called with no course id, generate an admin summary
     */
    private function generate_summary($courseid = null) {
        global $DB;

        $totalfiles          = 0;
        $accessible           = 0;
        $partiallyaccessible = 0;
        $inaccessible         = 0;
        $error                = 0;
        $unknown              = 0;

        // If a course id is provided, calculate stats for all the files in the course.
        if (isset($courseid)) {
            $filelist             = $this->get_course_files();
            $totalfiles = count($filelist);

            foreach ($filelist as $f) {
                // For each file, lookup file scan status.
                $record = $DB->get_record("block_filescan_files", array('contenthash' => $f));

                if ($record) {
                    switch ($record->status) {
                        case 'pass':
                            $accessible++;
                            break;
                        case 'fail':
                            $inaccessible++;
                            break;
                        case 'check':
                            $partiallyaccessible++;
                            break;
                        case 'error':
                            $error++;
                            break;
                        default:
                            $unknown++;
                            break;
                    }
                }
            }
        } else {
            // A course id is not provided.  Calculate for the entire site.
            $table = 'block_filescan_files';

            $totalfiles = $DB->count_records($table);
            $accessible = $DB->count_records($table, ['status' => 'pass']);
            $inaccessible = $DB->count_records($table, ['status' => 'fail']);
            $error = $DB->count_records($table, ['status' => 'error']);
            $partiallyaccessible = $DB->count_records($table, ['status' => 'check']);
        }

        $output = get_string('summary:files_found', 'block_filescan', $totalfiles);

        if ($accessible > 0) {
            $output .= html_writer::empty_tag('br', null);
            $output .= html_writer::tag('i', null, ['class' => 'fa fa-check text-success fa-fw', 'aria-hidden' => 'true']);
            $output .= html_writer::tag('span', get_string('summary:files_accessible', 'block_filescan', $accessible));
        }

        if ($partiallyaccessible > 0) {
            $output .= html_writer::empty_tag('br', null);
            $output .= html_writer::tag('i', null, ['class' => 'fa fa-exclamation text-warning fa-fw', 'aria-hidden' => 'true']);
            $output .= html_writer::tag('span', get_string('summary:files_partially_accessible', 'block_filescan',
            $partiallyaccessible));
        }

        if ($inaccessible > 0) {
            $output .= html_writer::empty_tag('br', null);
            $output .= html_writer::tag('i', null, ['class' => 'fa fa-times text-danger fa-fw', 'aria-hidden' => 'true']);
            $output .= html_writer::tag('span', get_string('summary:files_inaccessible', 'block_filescan', $inaccessible));
        }

        if ($error > 0) {
            $output .= html_writer::empty_tag('br', null);
            $output .= html_writer::tag('i', null, ['class' => 'fa fa-exclamation-triangle text-danger fa-fw',
                'aria-hidden' => 'true']);
            $output .= html_writer::tag('span', get_string('summary:files_error', 'block_filescan', $error));
        }

        if ($unknown > 0) {
            $output .= html_writer::empty_tag('br', null);
            $output .= html_writer::tag('i', null, ['class' => 'fa fa-question text-info fa-fw', 'aria-hidden' => 'true']);
            $output .= html_writer::tag('span', get_string('summary:files_accessibility_unknown', 'block_filescan', $unknown));
        }

        $output .= html_writer::tag('br', null);
        $output .= html_writer::tag('p', get_string('summary:last_updated', 'block_filescan', date("m/d/Y g:iA")),
            ['style' => 'font-size:0.9em']);

        return $output;
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
