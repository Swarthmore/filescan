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

// This should allow for easier debugging
if (!function_exists('dd')) {
function dd()
    {
        echo '<pre>';
        array_map(function($x) { var_dump($x); }, func_get_args());
        die;
    }
}

class block_filescan_external extends external_api {

    /**
     * Returns description of get_access_files parameters
     *
     * @return external_function_parameters
     */

    public static function get_access_files_parameters() {

        return new external_function_parameters (

            array(
                'draw'    => new external_value (PARAM_INT, 'Draw counter'),
                'start'   => new external_value (PARAM_INT, 'Paging first record indicator'),
                'length'  => new external_value (PARAM_INT, 'Number of records the table can display in the current draw.'),
                'search'  => new external_single_structure(array(
                        'value' => new external_value(PARAM_TEXT, 'Search string'),
                        'regex' => new external_value(PARAM_RAW, 'Is a  regex search')
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
     * Returns access file information (set foo to something to test)
     *
     * @param $start
     * @param $length
     * @return array
     */

    public static function get_access_files ($draw, $start, $length, $search, $order, $columns) {

        global $DB;

        $table = 'block_filescan_files';
        //
        $p = array(
            'draw'      => $draw,
            'start'     => $start,
            'length'    => $length,
            'search'    => $search,
            'order'     => $order,
            'columns'   => $columns
        );
        //
        //$params = self::validate_parameters(self::get_access_files_parameters(), $p);

        // these are the columns (db) that we want to select, along with a datatables id (dt) for the column
        $columns = array(
            array('db' => 'pagecount',    'dt' => 0),
            array('db' => 'status',       'dt' => 1),
            array('db' => 'checked',      'dt' => 2),
            array('db' => 'hastext',      'dt' => 3),
            array('db' => 'hastitle',     'dt' => 4),
            array('db' => 'hasoutline',   'dt' => 5),
            array('db' => 'haslanguage',  'dt' => 6),
            array('db' => 'timechecked',  'dt' => 7),
            array('db' => 'courseinfo',   'dt' => 8)
        );

        $select = 'id >=' . $p['start'] . ' and id <= ' . ($p['start'] + $p['length']);

        $results = $DB->get_records_select($table, $select);

        $warnings = [];

        return array(
            'draw' => (int) $draw,
            'data' => $results,
            'warnings' => $warnings
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

    public function limit ($start, $length) {
       $limit = '';

       if (isset($start) && isset($length)) {
           $limit = "LIMIT " . intval($start) . "," . intval($length);
       }

       return $limit;
    }

    /**
     * Describes the fields returned from get_access_files
     * In this case, these are columns in the block_filescan_files table
     *
     * @return external_single_structure
     */

    public static function get_access_files_returns() {
        return new external_single_structure(
            array(
                'draw' => new external_value(PARAM_INT, 'Draw counter'),
                'data' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'            => new external_value(PARAM_INT,    'file id'),
                            'contenthash'   => new external_value(PARAM_TEXT,   'hash'),
                            'pagecount'     => new external_value(PARAM_TEXT,   'page count'),
                            'status'        => new external_value(PARAM_RAW,    'status'),
                            'checked'       => new external_value(PARAM_BOOL,   'checked'),
                            'hastext'       => new external_value(PARAM_BOOL,   'hastext'),
                            'hastitle'      => new external_value(PARAM_BOOL,   'hastitle'),
                            'hasoutline'    => new external_value(PARAM_BOOL,   'hasoutline'),
                            'haslanguage'   => new external_value(PARAM_BOOL,   'haslanguage'),
                            'timechecked'   => new external_value(PARAM_RAW,    'time last checked'),
                            'courseinfo'    => new external_value(PARAM_RAW,    'courseinfo')
                        ), 'File')),

                'warnings' => new external_warnings(),
            )
        );
    }

}
