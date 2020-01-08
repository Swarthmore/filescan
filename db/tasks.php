<?php
/**
 * @package   block_afs
 * @copyright 2019 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'block_afs\task\scan_files',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),

    array(
        'classname' => 'block_afs\task\generate_report',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '4',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),

    array(
        'classname' => 'block_afs\task\cleanup_table',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '4',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    )
);
