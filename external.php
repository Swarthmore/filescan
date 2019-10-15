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
 * Web services functions.
 * 
 * These functions are used for ajax calls.
 * 
 * @package   block_filescan
 * @copyright 2018 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . "/externallib.php");

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
            'name' => new external_value(PARAM_RAW, 'The column name.'),
            'searchable' => new external_value(PARAM_RAW, 'Is the column searchable'),
            'orderable' => new external_value(PARAM_RAW, 'Is the column orderable'),

            'search' => new external_single_structure([
              'value' => new external_value(PARAM_RAW, 'Search value'),
              'regex' => new external_value(PARAM_RAW, 'Is this a regex search')
            ])

          ])
        ),

        'order' => new external_multiple_structure(
          new external_single_structure([
            'column' => new external_value(PARAM_RAW, 'Column to which ordering should be applied.'),
            'dir' => new external_value(PARAM_RAW, 'Ordering direction for this column')
          ])
        )

      ));
  }

  /**
   * Makes the data tables request for data
   *
   * @param $draw
   * @param $start
   * @param $length
   * @param $search
   * @param $cols
   * @param $order
   *
   * @return array
   */

  public static function request_files($draw, $start, $length, $search, $cols, $order) {

    global $DB;

    // set up all the variables we will need to handle the request to the database

    $config = include_once 'config/config.php';

    $table = 'block_filescan_files';
    $primaryKey = 'id';

    $columns = array(
      array('db' => 'id',          'dt' => 'id'),
      array('db' => 'status',      'dt' => 'status'),
      array('db' => 'hastext',     'dt' => 'hastext'),
      array('db' => 'hastitle',    'dt' => 'hastitle'),
      array('db' => 'hasoutline',  'dt' => 'hasoutline'),
      array('db' => 'haslanguage', 'dt' => 'haslanguage'),
      array('db' => 'timechecked', 'dt' => 'timechecked'),
      array('db' => 'courseinfo',  'dt' => 'courseinfo')
    );

    // todo: validate parameters before sending the request
    // Set up the parameters required for DataTables
    // These are parameters given to us by DataTables
    $request = array(
      'draw'      => $draw,
      'start'     => $start,
      'length'    => $length,
      'search'    => $search,
      'columns'   => $cols,
      'order'     => $order,
    );

    // Build the SQL query string from the request
    $limit = self::limit( $request, $columns );
    $order = self::order( $request, $columns );
    $where = self::filter( $request, $columns );

    $dataSQL = "SELECT `".implode("`, `", self::pluck($columns, 'db'))."` FROM {block_filescan_files}";

    $search = null;

    $resFilterLengthSQL = "SELECT COUNT({$primaryKey}) FROM {block_filescan_files}";

    if ($request['search']['value'] || $request['search'][0]) {
      $dataSQL .= " WHERE courseinfo LIKE :search";
      $search = array('search' => "%" . $request['search']['value'] . "%");
      $resFilterLengthSQL .= '  WHERE courseinfo LIKE :search';
    }

    $recordsFiltered = reset($DB->get_record_sql($resFilterLengthSQL, $search));

    // Total data set length
    $resTotalLengthSQL = "SELECT COUNT({$primaryKey}) FROM {block_filescan_files}";
    $recordsTotal = reset($DB->get_record_sql($resTotalLengthSQL));

    $dataSQL .= ' ' . $order . ' ' . $limit;

    $warnings = [];

    $data = $DB->get_records_sql($dataSQL, $search);

    return array(
      "draw"            => isset ( $request['draw'] ) ? intval( $request['draw'] ) : 0,
      "recordsTotal"    => intval( $recordsTotal ),
      "recordsFiltered" => intval( $recordsFiltered ),
      "data"            => $data,
      "warnings"        => $warnings
    );
  }

  /**
   * Create the data output array for the DataTables rows
   *
   *  @param  array $columns Column information array
   *  @param  array $data    Data from the SQL get
   *  @return array          Formatted data in a row based format
   */
  static function data_output ( $columns, $data )
  {
    $out = array();

    for ( $i=0, $ien=count($data) ; $i<$ien ; $i++ ) {
      $row = array();

      for ( $j=0, $jen=count($columns) ; $j<$jen ; $j++ ) {
        $column = $columns[$j];

        // Is there a formatter?
        if ( isset( $column['formatter'] ) ) {
          $row[ $column['dt'] ] = $column['formatter']( $data[$i][ $column['db'] ], $data[$i] );
        }
        else {
          $row[ $column['dt'] ] = $data[$i][ $columns[$j]['db'] ];
        }
      }

      $out[] = $row;
    }

    return $out;
  }

  /**
   * Paging
   *
   * Construct the LIMIT clause for server-side processing SQL query
   *
   *  @param  array $request Data sent to server by DataTables
   *  @param  array $columns Column information array
   *  @return string SQL limit clause
   */
  static function limit ( $request, $columns )
  {
    $limit = '';

    if ( isset($request['start']) && $request['length'] != -1 ) {
      $limit = "LIMIT ".intval($request['start']).", ".intval($request['length']);
    }

    return $limit;
  }

  /**
   * Ordering
   *
   * Construct the ORDER BY clause for server-side processing SQL query
   *
   *  @param  array $request Data sent to server by DataTables
   *  @param  array $columns Column information array
   *  @return string SQL order by clause
   */
  static function order ( $request, $columns )
  {
    $order = '';

    if ( isset($request['order']) && count($request['order']) ) {
      $orderBy = array();
      $dtColumns = self::pluck( $columns, 'dt' );

      for ( $i=0, $ien=count($request['order']) ; $i<$ien ; $i++ ) {
        // Convert the column index into the column data property
        $columnIdx = intval($request['order'][$i]['column']);
        $requestColumn = $request['columns'][$columnIdx];

        $columnIdx = array_search( $requestColumn['data'], $dtColumns );
        $column = $columns[ $columnIdx ];

        if ( $requestColumn['orderable'] == 'true' ) {
          $dir = $request['order'][$i]['dir'] === 'asc' ?
            'ASC' :
            'DESC';

          $orderBy[] = '`'.$column['db'].'` '.$dir;
        }
      }

      if ( count( $orderBy ) ) {
        $order = 'ORDER BY '.implode(', ', $orderBy);
      }
    }

    return $order;
  }

  /**
   * Searching / Filtering
   *
   * Construct the WHERE clause for server-side processing SQL query.
   *
   * NOTE this does not match the built-in DataTables filtering which does it
   * word by word on any field. It's possible to do here performance on large
   * databases would be very poor
   *
   *  @param  array $request Data sent to server by DataTables
   *  @param  array $columns Column information array
   *  @param  array $bindings Array of values for PDO bindings, used in the
   *    sql_exec() function
   *  @return string SQL where clause
   */
  static function filter ($request, $columns)
  {

    global $DB;

    $globalSearch = array();
    $columnSearch = array();
    $dtColumns = self::pluck( $columns, 'dt' );

    if ( isset($request['search']) && $request['search']['value'] != '' ) {
      $str = $request['search']['value'];

      for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
        $requestColumn = $request['columns'][$i];
        $columnIdx = array_search( $requestColumn['data'], $dtColumns );
        $column = $columns[ $columnIdx ];

        if ( $requestColumn['searchable'] == 'true' ) {
          //$binding = self::bind( $bindings, '%'.$str.'%', PDO::PARAM_STR );

          $globalSearch[] = "`".$column['db']."` LIKE ".$str;

          //$globalSearch[] = "`".$column['db']."` LIKE ". $str;
        }
      }
    }

    // Individual column filtering
    if ( isset( $request['columns'] ) ) {
      for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
        $requestColumn = $request['columns'][$i];
        $columnIdx = array_search( $requestColumn['data'], $dtColumns );
        $column = $columns[ $columnIdx ];

        $str = $requestColumn['search']['value'];

        if ( $requestColumn['searchable'] == 'true' && $str != '' ) {
          //$binding = self::bind( $bindings, '%'.$str.'%', PDO::PARAM_STR );
          $columnSearch[] = "`".$column['db']."` LIKE ".$str;
          //$globalSearch[] = $column['db'] . " LIKE %\'".$str."\'";
        }
      }
    }

    // Combine the filters into a single string
    $where = '';

    if ( count( $globalSearch ) ) {
      $where = '('.implode(' OR ', $globalSearch).')';
    }

    if ( count( $columnSearch ) ) {
      $where = $where === '' ?
        implode(' AND ', $columnSearch) :
        $where .' AND '. implode(' AND ', $columnSearch);
    }

    if ( $where !== '' ) {
      $where = 'WHERE '.$where;
    }

    return $where;
  }

  /**
   * Pull a particular property from each assoc. array in a numeric array,
   * returning and array of the property values from each item.
   *
   *  @param  array  $a    Array to get data from
   *  @param  string $prop Property to read
   *  @return array        Array of property values
   */
  static function pluck ( $a, $prop )
  {
    $out = array();

    for ( $i=0, $len=count($a) ; $i<$len ; $i++ ) {
      $out[] = $a[$i][$prop];
    }

    return $out;
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
              'id' => new external_value(PARAM_INT, 'Unique identifier.'),
              'status' => new external_value(PARAM_TEXT, 'The file scan status.'),
              'hastext' => new external_value(PARAM_BOOL, 'Bool representing if the file has scannable text.'),
              'hastitle' => new external_value(PARAM_BOOL, 'Bool representing if the file has a title.'),
              'hasoutline' => new external_value(PARAM_BOOL, 'Bool representing if the file has an outline.'),
              'haslanguage' => new external_value(PARAM_BOOL, 'Bool representing if the file has a langauge.'),
              'timechecked' => new external_value(PARAM_RAW, 'Timestamp of the when the file was last scanned.'),
              'courseinfo' => new external_value(PARAM_RAW, 'JSON representation of course info.')
            ), 'File')),
        'warnings' => new external_warnings(),
      )
    );
  }

}
