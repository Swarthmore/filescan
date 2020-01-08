<?php
/**
 * @package   block_afs
 * @copyright 2019 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_afs_request_files' => [
        'classname' => 'block_afs_external',
        'methodname' => 'request_files',
        'classpath' => 'blocks/afs/external.php',
        'description' => 'Get results from the afs table',
        'type' => 'read',
        'ajax' => true
    ]
];
