<?php

defined('MOODLE_INTERNAL') || die();

class block_filescan_observer {

	// This function is called when a Moodle event may mean we need to update the filescan summary data.
	// The summary data is cached using Moodle Cache API so that the summary does not need to be regenerated
	// on every page load.  If a file resource or folder is created, updated, or deleted the summary data
	// should be regenerated.  This is done by deleting the filescan cache for the course.
	// There isn't a specific event for creating or deleting files and folders (or updating files), so
	// we need to catch all events that create, update, or delete course modules (as well as update mod_folder)
	// and then check to see if they match the criteria to delete the cache.
	
	// Note: it can take a few minutes for some of these events to fire.

    public static function update(\core\event\base $event) {
    
    	$event_data = $event->get_data();
          
       	// Determine if we should invalidate the filescan cache
       	// If file resource is affected at all
       	// If folder is created, updated, or deleted
       	if (
       		(isset($event_data["eventname"]) && $event_data["eventname"]=="\\mod_folder\\event\\folder_updated") ||
       		(isset($event_data["eventname"]) && isset($event_data["other"]) && isset($event_data["other"]["modulename"]) && $event_data["eventname"]=="\\core\\event\\course_module_deleted" && $event_data["other"]["modulename"]=="folder") ||
       		(isset($event_data["eventname"]) && isset($event_data["other"]) && isset($event_data["other"]["modulename"]) && $event_data["eventname"]=="\\core\\event\\course_module_created" && $event_data["other"]["modulename"]=="folder") ||
       		(isset($event_data["eventname"]) && isset($event_data["other"]) && isset($event_data["other"]["modulename"]) && $event_data["eventname"]=="\\core\\event\\course_module_deleted" && $event_data["other"]["modulename"]=="resource") ||
       		(isset($event_data["eventname"]) && isset($event_data["other"]) && isset($event_data["other"]["modulename"]) && $event_data["eventname"]=="\\core\\event\\course_module_created" && $event_data["other"]["modulename"]=="resource") ||
			    (isset($event_data["eventname"]) && isset($event_data["other"]) && isset($event_data["other"]["modulename"]) && $event_data["eventname"]=="\\core\\event\\course_module_updated" && $event_data["other"]["modulename"]=="resource") )
          {
            // Clear any existing cache data for this course
            $cache = cache::make('block_filescan', 'filescan');
            $filescan_cache = $cache->delete($event_data["courseid"]);
          }
       	
    } 

}