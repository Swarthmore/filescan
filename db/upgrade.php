<?php
 
function xmldb_block_pdfcheck_upgrade($oldversion) {
    global $CFG, $DB;

	$dbman = $DB->get_manager();

 
    $result = TRUE;
 
    if ($oldversion < 2015101602) {

        // Define table block_pdfcheck to be created.
        $table = new xmldb_table('block_pdfcheck');

        // Adding fields to table block_pdfcheck.
        $field = new xmldb_field('blockid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

       if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pdfcheck savepoint reached.
        upgrade_block_savepoint(true, 2015101602, 'pdfcheck');
    }

 
    return $result;
}
?>