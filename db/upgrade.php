<?php

function xmldb_block_filescan_upgrade($oldversion) {

    global $DB;
    $dbman = $DB->get_manager();

    /// Add a new column newcol to the mdl_myqtype_options
    if ($oldversion < 2017062810) {
    
        // Define field id to be added to block_filescan_files.
        $table = new xmldb_table('block_filescan_files');
        $field = new xmldb_field('timechecked', XMLDB_TYPE_DATETIME, null, null, XMLDB_NOTNULL, null, 0, null);

        // Conditionally launch add field timechecked.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Filescan savepoint reached.
        upgrade_plugin_savepoint(true, 2017062810, 'error', 'filescan');
    }

    return true;
} 
 
