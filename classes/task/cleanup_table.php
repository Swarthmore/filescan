<?php

/**
 * @package   block_afs
 * @copyright 2019 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_afs\task;

class cleanup_table extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task:cleanup', 'block_afs');
    }

    public function execute() {
        global $DB;

        $sql = 'select fs.contenthash from {block_afs} fs where fs.contenthash not in (select f.contenthash from {files} f)';

        $rs = $DB->get_recordset_sql($sql);

        if (!$rs->valid()) {
            mtrace("Oops! There were no files found");
            return false;
        }

        foreach($rs as $key => $record) {

            mtrace('Deleting' . $record->contenthash);

            $DB->delete_records('block_afs', array('contenthash' => $record->contenthash));
        }

        $rs->close();

        mtrace('Records with missing files deleted.');
    }

}