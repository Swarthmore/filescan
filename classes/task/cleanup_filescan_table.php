<?php

namespace block_filescan\task;

class cleanup_filescan_table extends \core\task\scheduled_task
{

  public function get_name()
  {
    // Shown in admin screens
    return get_string('cleanup_task', 'block_filescan');
  }

  public function execute() {
    global $DB;

    /*
     * get all files in block_filescan_files that do not have an actual file
     *
     * select bfs.contenthash
     * from {block_filescan_files} bfs
     * where bfs.contenthash is not in (select f.contenthash from mdl_files f)
     *
     * delete the row in block_filescan_files where there is no contenthash in mdl_files
     *
     *
     */

    // to delete files that that don't exist anymore
    // check content hash in block filescan table against content hash in the files table
    // if the contenthash doesnt exist in the files table then we can remove the record from the
    // block filescan table

    $sql = 'select fs.contenthash from {block_filescan_files} fs where fs.contenthash not in (select f.contenthash from {files} f)';

    $rs = $DB->get_recordset_sql($sql);

    mtrace('Exectuing ' . $sql);
    mtrace($rs);

    foreach($rs as $key => $record) {
      mtrace($key);
      mtrace($record->contenthash);

      $DB->delete_records('block_filescan_files', array('contenthash' => $record->contenthash));
    }

    mtrace('Records with missing files deleted.');
  }
}