<?php

/**
 * @package   block_afs
 * @copyright 2019 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/afs:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW,
                'coursecreator' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    'block/afs:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
                'user' => CAP_PREVENT
        )
    ),

    'block/afs:viewpages' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
                'guest' => CAP_PREVENT,
                'student' => CAP_PREVENT,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'coursecreator' => CAP_ALLOW,
                'manager' => CAP_ALLOW
        )
    ),

    'block/afs:managepages' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
                'guest' => CAP_PREVENT,
                'student' => CAP_PREVENT,
                'teacher' => CAP_PREVENT,
                'editingteacher' => CAP_ALLOW,
                'coursecreator' => CAP_ALLOW,
                'manager' => CAP_ALLOW
        )
    ),

    'block/afs:scan' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
                'guest' => CAP_PREVENT,
                'student' => CAP_PREVENT,
                'teacher' => CAP_PREVENT,
                'coursecreator' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
        )
    ),

    'block/afs:viewsummary' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
                'guest' => CAP_PREVENT,
                'student' => CAP_PREVENT,
                'teacher' => CAP_PREVENT,
                'editingteacher' => CAP_PREVENT,
                'coursecreator' => CAP_PREVENT,
                'manager' => CAP_ALLOW
        )
    )
  
  );
