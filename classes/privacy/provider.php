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
 * Privacy Subsystem implementation for block_socialcomments.
 *
 * @package   block_socialcomments
 * @copyright 2019 Paul Steffen, EDU-Werkstatt GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_socialcomments\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
    // This plugin does store course related comments entered by users.
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'block_socialcomments_cmmnts',
             [
                'contextid' => 'privacy:metadata:block_socialcomments_cmmnts:contextid',
                'content' => 'privacy:metadata:block_socialcomments_cmmnts:content',
                'userid' => 'privacy:metadata:block_socialcomments_cmmnts:userid',
                'groupid' => 'privacy:metadata:block_socialcomments_cmmnts:groupid',
                'courseid' => 'privacy:metadata:block_socialcomments_cmmnts:courseid',
                'timemodified' => 'privacy:metadata:block_socialcomments_cmmnts:timemodified',
             ],
            'privacy:metadata:block_socialcomments_cmmnts'
        );
        $collection->add_database_table(
            'block_socialcomments_subscrs',
             [
                'courseid' => 'privacy:metadata:block_socialcomments_subscrs:courseid',
                'contextid' => 'privacy:metadata:block_socialcomments_subscrs:contextid',
                'userid' => 'privacy:metadata:block_socialcomments_subscrs:userid',
                'timelastsent' => 'privacy:metadata:block_socialcomments_subscrs:timelastsent',
                'timemodified' => 'privacy:metadata:block_socialcomments_subscrs:timemodified',
             ],
            'privacy:metadata:block_socialcomments_subscrs'
        );
        $collection->add_database_table(
            'block_socialcomments_pins',
             [
                'itemtype' => 'privacy:metadata:block_socialcomments_pins:itemtype',
                'itemid' => 'privacy:metadata:block_socialcomments_pins:itemid',
                'userid' => 'privacy:metadata:block_socialcomments_pins:userid',
             ],
            'privacy:metadata:block_socialcomments_pins'
        );
        $collection->add_database_table(
            'block_socialcomments_replies',
             [
                'commentid' => 'privacy:metadata:block_socialcomments_replies:commentid',
                'content' => 'privacy:metadata:block_socialcomments_replies:content',
                'userid' => 'privacy:metadata:block_socialcomments_replies:userid',
                'timemodified' => 'privacy:metadata:block_socialcomments_replies:timemodified',
             ],
            'privacy:metadata:block_socialcomments_replies'
        );
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        $params = [
            'userid' => $userid
        ];

        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        $params = [
            'contextid' => $context->id,
        ];

        return $userlist;
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
    }

  
    public static function delete_data_for_all_users_in_context(\context $context) {
  
    }

    public static function delete_data_for_users(approved_userlist $userlist) {

    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
    }
}
