<?php

defined('MOODLE_INTERNAL') || die();

$observers = array (
    array (
        'eventname' => '\core\event\course_module_created',
        'callback'  => 'block_filescan_observer::update',
    ),
    array (
        'eventname' => '\core\event\course_module_updated',
        'callback'  => 'block_filescan_observer::update',
    ),
    array (
        'eventname' => '\core\event\course_module_deleted',
        'callback'  => 'block_filescan_observer::update',
    ),
    array (
        'eventname' => '\mod_folder\event\folder_updated',
        'callback'  => 'block_filescan_observer::update',
    )   
);
