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
 * @package   block_filescan
 * @copyright 2018 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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
      'guest'           => CAP_PREVENT,
      'student'         => CAP_PREVENT,
      'teacher'         => CAP_PREVENT,
      'coursecreator'   => CAP_ALLOW,
      'editingteacher'  => CAP_ALLOW,
      'manager'         => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/course:update',
  ),

  'block/filescan:viewadminreport' => array(
    'captype' => 'read',
    'contextlevel' => CONTEXT_BLOCK,
    'archetypes' => array(
      'guest'          => CAP_PREVENT,
      'student'        => CAP_PREVENT,
      'teacher'        => CAP_PREVENT,
      'editingteacher' => CAP_PREVENT,
      'coursecreator'  => CAP_PREVENT,
      'manager'        => CAP_ALLOW
    )
  )
);
