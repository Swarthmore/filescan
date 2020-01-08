<?php

/**
 * @package   block_afs
 * @copyright 2019 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\core\event\course_module_created',
        'callback' => 'block_afs_observer::update',
    ),
    array(
        'eventname' => '\core\event\course_module_updated',
        'callback' => 'block_afs_observer::update',
    ),
    array(
        'eventname' => '\core\event\course_module_deleted',
        'callback' => 'block_afs_observer::update',
    ),
    array(
        'eventname' => '\mod_folder\event\folder_updated',
        'callback' => 'block_afs_observer::update',
    )
);
