<?php
$capabilities = array(

  'block/filescan:addinstance' => array(
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

  'block/filescan:myaddinstance' => array(
    'captype' => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes' => array(
      'user' => CAP_PREVENT
    )
  ),

  'block/filescan:viewpages' => array(
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

  'block/filescan:managepages' => array(
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


  'block/filescan:scan' => array(
    'riskbitmask' => RISK_PERSONAL,
    'captype' => 'read',
    'contextlevel' => CONTEXT_COURSE,
    'archetypes' => array(
      'editingteacher' => CAP_ALLOW,
      'manager' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/course:update',
  ),

  'block/filescan:viewadminreport' => [
    'captype' => 'read',
    'contextlevel' => CONTEXT_BLOCK,
    'archetypes' => [
      'editingteacher' => CAP_ALLOW,
      'manager' => CAP_ALLOW
    ]
  ]

);
