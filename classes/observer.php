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
 * Version information for block_accessibility_filescan
 *
 * @package   block_accessibility_filescan
 * @copyright 2023 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_accessibility_filescan_observer
{

  /**
   * Invalidate the cache for courseid.
   */
  //public static function invalidate_results_cache(\core\event\base $event, int $courseid) {
  //  $event_data = $event->get_data();
  //  $cache = cache::make('block_accessibility_filescan', 'accessibility_filescan_results');
  //  $cache->delete($courseid);
  //}

  // This function is called when a Moodle event may mean we need to update the summary data.
  // The summary data is cached using Moodle Cache API so that the summary does not need to be regenerated
  // on every page load.  If a file resource or folder is created, updated, or deleted the summary data
  // should be regenerated.  This is done by deleting the cache for the course.
  // There isn't a specific event for creating or deleting files and folders (or updating files), so
  // we need to catch all events that create, update, or delete course modules (as well as update mod_folder)
  // and then check to see if they match the criteria to delete the cache.
  // Note: it can take a few minutes for some of these events to fire.

  #public static function update(\core\event\base $event)
  #{

  #  $event_data = $event->get_data();

  #  // Clear any existing cache data for this course
  #  $cache = cache::make('block_accessibility_filescan', 'accessibility_filescan_results');
  #  $cache->delete($event_data["courseid"]);

  #  // Determine if we should invalidate the cache if file or folder activity is affected at all
  #  if (
  #    (isset($event_data["eventname"]) && $event_data["eventname"] == "\\mod_folder\\event\\folder_updated") ||
  #    (isset($event_data["eventname"]) && isset($event_data["other"]) && isset($event_data["other"]["modulename"]) && $event_data["eventname"] == "\\core\\event\\course_module_deleted" && $event_data["other"]["modulename"] == "folder") ||
  #    (isset($event_data["eventname"]) && isset($event_data["other"]) && isset($event_data["other"]["modulename"]) && $event_data["eventname"] == "\\core\\event\\course_module_created" && $event_data["other"]["modulename"] == "folder") ||
  #    (isset($event_data["eventname"]) && isset($event_data["other"]) && isset($event_data["other"]["modulename"]) && $event_data["eventname"] == "\\core\\event\\course_module_deleted" && $event_data["other"]["modulename"] == "resource") ||
  #    (isset($event_data["eventname"]) && isset($event_data["other"]) && isset($event_data["other"]["modulename"]) && $event_data["eventname"] == "\\core\\event\\course_module_created" && $event_data["other"]["modulename"] == "resource") ||
  #    (isset($event_data["eventname"]) && isset($event_data["other"]) && isset($event_data["other"]["modulename"]) && $event_data["eventname"] == "\\core\\event\\course_module_updated" && $event_data["other"]["modulename"] == "resource")) {

  #    // Clear any existing cache data for this course
  #    $cache = cache::make('block_accessibility_filescan', 'accessibility_filescan_results');
  #    $cache->delete($event_data["courseid"]);
  #  }

  #}

}
