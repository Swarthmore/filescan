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







class report_generation extends \core\task\scheduled_task {



    public function get_name() {
        // Shown in admin screens
        return get_string('reportgeneration_task', 'block_filescan');
    }




    public function execute() {

		global $CFG, $DB;
	
	//$DB->set_debug(true);
		
        mtrace( "Acessibility report generation script is running" );

       
        // Select all PDF files in the system
        $query = 'select * from mdl_files f where
f.component in ("course", "course", "block_html", "mod_assign", "mod_book", "mod_data", "mod_folder", "mod_forum", "mod_glossary","mod_label", "mod_lesson", "mod_page", "mod_publication", "mod_questionnaire", "mod_quiz", "mod_resource", "mod_scorm", "mod_url", "mod_workshop", "qtype_essay", "question")
AND f.filesize <> 0 
AND f.mimetype = "application/pdf"
ORDER BY f.timemodified DESC limit 100';
        		
        		
        		
        // Get a number of records as a moodle_recordset using a SQL statement.		
		$pdf_rs = $DB->get_recordset_sql($query, array $params=null, $limitfrom=0, $limitnum=0);
		  
		if ($rs->valid()) {
    		// The recordset contains records.
    		
    		foreach($rs as $key => $value) {
    			mtrace(print_r($key,true));
    			mtrace(print_r($value,true));
    			echo "\n";
			}
    		
    		
		} else {
		    mtrace("No files found");
            return false;
		}
			
			
		$rs->close();
		
		

 	}       

}
	

