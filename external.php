<?php

/**
 * PLUGIN filescan
 *
 * @package local_filescan
 * @copyright 2018 Swarthmore College ITS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

/**
 * returns the total records in $table
 *
 * @param $table
 * @return int
 */
function getTotalRecords($table)
{
  global $DB;

  if ($DB->count_records($table, $conditions = null)) {
    return $DB->count_records($table, $conditions = null);
  } else {
    return 0;
  }
}

function getTotalFilteredRecords($table, $conditions){
  $sql = '';
}

class block_filescan_external extends external_api
{

  /**
   * Returns description of get_access_files parameters
   *
   * @return external_function_parameters
   */

  public static function request_files_parameters()
  {

    return new external_function_parameters (

      array(
        'draw' => new external_value (PARAM_INT, 'Draw counter'),
        'start' => new external_value (PARAM_INT, 'Paging first record indicator'),
        'length' => new external_value (PARAM_INT, 'Number of records the table can display in the current draw.'),
        'search' => new external_single_structure (array(
          'value' => new external_value (PARAM_TEXT, 'Search string'),
          'regex' => new external_value (PARAM_RAW, 'Is a  regex search')
        )),

        'columns' => new external_multiple_structure(

          new external_single_structure([
            'data' => new external_value(PARAM_RAW, 'The column data.'),
            'name' => new external_value(PARAM_TEXT, 'The column name.'),
            'searchable' => new external_value(PARAM_RAW, 'Is the column searchable'),
            'orderable' => new external_value(PARAM_RAW, 'Is the column orderable'),

            'search' => new external_single_structure([
              'value' => new external_value(PARAM_TEXT, 'Search value'),
              'regex' => new external_value(PARAM_RAW, 'Is this a regex search')
            ])

          ])
        ),

        'order' => new external_multiple_structure(
          new external_single_structure([
            'column' => new external_value(PARAM_INT, 'Column to which ordering should be applied.'),
            'dir' => new external_value(PARAM_TEXT, 'Ordering direction for this column')
          ])
        )

      ));
  }

  /**
   * Returns access file information
   *
   * @param $start
   * @param $length
   * @return array
   */

  public static function request_files($draw, $start, $length, $search, $order, $columns)
  {

    global $DB;

    $table = 'block_filescan_files';

    $p = array(
      'draw'      => $draw,
      'start'     => $start,
      'length'    => $length,
      'search'    => $search,
      'order'     => $order,
      'columns'   => $columns
    );

    // todo: the column names should probably go into a config file
    $columnNames = array('id', 'pages', 'status', 'checked', 'text', 'title', 'outline', 'language', 'last checked', 'courses', 'filepath', 'action');

    // populate the required columns array
    for($i=0; $i<=count($columnNames); $i++) {
      $columns[$i]['name'] = $columnNames[$i];
      $columns[$i]['searchable'] = true;
      $columns[$i]['orderable'] = true;
      $columns[$i]['search']['value'] = '';
      $columns[$i]['search']['regex'] = true;
    }

    //$params = self::validate_parameters(self::get_access_files_parameters(), $p);

    // construct the sql statement
    $sql = 'SELECT id, contenthash, pagecount, status, checked, hastext, hastitle, hasoutline, haslanguage, timechecked, courseinfo
            FROM {block_filescan_files}';

    $conditions = array();

    // If there's a search parameter passed, we need to concatendate it to the sql statement and set it in the conditions
    if ($p['search']['value'] != '') {
      $like = $DB->sql_like('courseinfo', ':searchvalue');
      $conditions['searchvalue'] = '%' . $p['search']['value'] . '%';
      $sql .= ' WHERE ' . $like;
    }

    $rf = $DB->count_records($table, $conditions);

    $limit = self::limit($p['start'], $p['length']);
    $sql .= $limit;

    $results = $DB->get_records_sql(trim($sql, "\t\n"), $conditions);

    $warnings = [];

    return array(
      'draw'              => (int) $draw,
      'recordsTotal'      => getTotalRecords($table),
      'recordsFiltered'   => $rf, // the total amount of records after a filter has been applied
      'data'              => $results,
      'warnings'          => $warnings
    );
  }

  /**
   * Paging
   *
   * Construct the LIMIT clause for SQL query
   *
   * @param array $request Data sent to the server by DataTables
   * @param array $columns Information about the columns
   * @return string
   */

  public static function limit($start, $length)
  {
    $limit = '';

    if (isset($start) && isset($length)) {
      $limit = " LIMIT " . intval($start) . "," . intval($length);
    }

    return $limit;
  }

  /**
   * returns the order clause for the sql statement
   * @param $dir
   * @return string
   */

  public static function order($dir) {

    if ($dir != 'ASC' || $dir != 'DESC') {
      return 'Please pass ASC or DESC into this function';
    }

    $order = '';

  }

  /**
   * returns the where clause for the sql statement
   * @param $search
   * @return string
   */

  public static function filter($column, $operator, $search)
  {

    $operators = array(
      '=', '<>', '>', '<', '>=', '<=', 'BETWEEN', 'LIKE', 'IN', 'IS NULL', 'IS NOT NULL'
    );

    // error conditions
    if (!$search) {
      return ''; // do not return a where clause
    }

    if (in_array($operator, $operators)) {
      return 'An invalid operator was detected. There was an error generating the where clause of this query.';
    }

    // success conditions
    if ($operator = '=') {
      $where = 'WHERE ' . '\'' . $column . '\'' . $operator . $search;
      return $where;
    }
  }

  /**
   * Describes the fields returned from get_access_files
   * In this case, these are columns in the block_filescan_files table
   *
   * @return external_single_structure
   */

  public static function request_files_returns()
  {
    return new external_single_structure(
      array(
        'draw' => new external_value(PARAM_INT, 'Draw counter'),
        'recordsTotal' => new external_value(PARAM_INT, 'Total records'),
        'recordsFiltered' => new external_value(PARAM_INT, 'Total records after filtering'),
        'data' => new external_multiple_structure(
          new external_single_structure(
            array(
              'id' => new external_value(PARAM_INT, 'file id'),
              'contenthash' => new external_value(PARAM_TEXT, 'hash'),
              'pagecount' => new external_value(PARAM_TEXT, 'page count'),
              'status' => new external_value(PARAM_RAW, 'status'),
              'checked' => new external_value(PARAM_BOOL, 'checked'),
              'hastext' => new external_value(PARAM_BOOL, 'hastext'),
              'hastitle' => new external_value(PARAM_BOOL, 'hastitle'),
              'hasoutline' => new external_value(PARAM_BOOL, 'hasoutline'),
              'haslanguage' => new external_value(PARAM_BOOL, 'haslanguage'),
              'timechecked' => new external_value(PARAM_RAW, 'time last checked'),
              'courseinfo' => new external_value(PARAM_RAW, 'courseinfo')
            ), 'File')),

        'warnings' => new external_warnings(),
      )
    );
  }

}
