<?php

namespace block_filescan\task;


ini_set('display_errors', 'On');
error_reporting(E_ALL);


/* Per https://docs.moodle.org/dev/Task_API#Failures
	A task, either scheduled or adhoc can sometimes fail. An example would be updating an RSS field when the network 
	is temporarily down. This is handled by the task system automatically - all the failing task needs to do is throw 
	an exception. The task will be retried after 1 minute. If the task keeps failing, the retry algorithm will add 
	more time between each successive attempts up to a max of 24 hours.
	
	Will throw exceptions when fatal errors occur
*/







class generate_report extends \core\task\scheduled_task {



    public function get_name() {
        // Shown in admin screens
        return get_string('reportgeneration_task', 'block_filescan');
    }




    public function execute() {

		global $CFG, $DB;
		$teacher_role_id = $DB->get_record('role', array('shortname' => 'editingteacher'))->id;
		
		
	 //$DB->set_debug(true);
		
        mtrace( "Acessibility report generation script is running" );

       
        // Select all PDF files in the system
        $query = 'select * from {files} f, {context} ctx where
f.component in ("course", "course", "block_html", "mod_assign", "mod_book", "mod_data", "mod_folder", "mod_forum", "mod_glossary","mod_label", "mod_lesson", "mod_page", "mod_publication", "mod_questionnaire", "mod_quiz", "mod_resource", "mod_scorm", "mod_url", "mod_workshop", "qtype_essay", "question") AND f.filesize <> 0  
	AND f.mimetype = "application/pdf"
	AND f.contextid = ctx.id
	ORDER BY f.timemodified DESC limit 100';
        		
        // Get a number of records as a moodle_recordset using a SQL statement.		
		$pdf_rs = $DB->get_recordset_sql($query);
		  
		if ($pdf_rs->valid()) {
    		// The recordset contains records.
    		
    		foreach($pdf_rs as $key => $value) {
    		
    			$course_object = new \stdClass();
    		
    			mtrace(print_r($key,true));
    			mtrace(print_r($value,true));
    			
    			// Get an array of contexts for this file.  Look for the one that is a course
    			$contexts = array_reverse(explode("/",$value->path));
    			array_shift($contexts);
    			
    			list($insql, $params) = $DB->get_in_or_equal($contexts);
				$sql = "select * from {context} where id $insql";
				$context_results = $DB->get_records_sql($sql, $params);
    			
    			$courseid = NULL;
    			foreach ($context_results as $c) {
    				// Find the context where contextlevel = 50 (course)
        			if ($c->contextlevel == 50) {
        				$courseid = $c->instanceid;
        				break;
        			}
        		}
        		
        		// Lookup course info
        		if ($courseid) {
					$context = \context_course::instance($courseid);
					$course = get_course($courseid);	
					$num_users = count(get_enrolled_users($context,'',0));
					$teachers = get_role_users($teacher_role_id, $context,NULL , "u.id, u.firstname, u.lastname, u.email");
					
					$teacher_string = "";
					foreach($teachers as $t) {
						$teacher_string .= sprintf("%s %s, ", $t->firstname, $t->lastname);
					}
					$teacher_string = substr($teacher_string, 0, -2);
					
					$course_object->fullname = $course->fullname;
					$course_object->shortname = $course->shortname;
					$course_object->courseid = $courseid;
					$course_object->teachers = $teachers;
					$course_object->student_enrollment = $num_users - count($teachers);
					
					
					mtrace(serialize($course_object));
				}

    			echo "\n";
			}
    		
    		
		} else {
		    mtrace("No files found");
            return false;
		}
			
			
		$pdf_rs->close();
		
		

 	}       

}
	

