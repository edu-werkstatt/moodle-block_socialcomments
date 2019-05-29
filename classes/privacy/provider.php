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
use \block_socialcomments\local\comments_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for block block_socialcomments.
 *
 * @package   block_socialcomments
 * @copyright 2019 Paul Steffen, EDU-Werkstatt GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin does store course related comments entered by users.
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
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

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * In the case of socialcomments, this is the context of any course where
     * the user has made a comment or replied to.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist  The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        $params = [
            'userid' => $userid
        ];

        // Get context by comments.
        $sql = "SELECT c.id
                FROM {context} c
                INNER JOIN {block_socialcomments_cmmnts} s ON s.contextid = c.id
                WHERE (s.userid = :userid)";
        $contextlist->add_from_sql($sql, $params);

        // Get context by replies.
        $sql = "SELECT c.id
                FROM {context} c
                INNER JOIN mdl_block_socialcomments_cmmnts s ON s.contextid = c.id
                INNER JOIN mdl_block_socialcomments_replies r ON r.commentid = s.id
                WHERE (r.userid = :userid)
                GROUP BY id";
        $contextlist->add_from_sql($sql, $params);

        // Get context by subscriptions.
        $sql = "SELECT c.id
                FROM {context} c
                INNER JOIN {block_socialcomments_subscrs} s ON s.contextid = c.id
                WHERE (s.userid = :userid)";
        $contextlist->add_from_sql($sql, $params);

        // Get context by pins.
        $sql = "SELECT c.id
                FROM {context} c
                INNER JOIN {block_socialcomments_cmmnts} s ON s.contextid = c.id
                INNER JOIN {block_socialcomments_pins} p ON p.itemid = s.id
                WHERE (p.userid = :userid_comment)
                AND (p.itemtype = :pin_type_comment)
                UNION
                SELECT c.id
                FROM {context} c
                INNER JOIN  {block_socialcomments_pins} p ON p.itemid = c.id
                WHERE (p.userid = :userid_page)
                AND (p.itemtype = :pin_type_page)";
        $params = [
          'userid_comment' => $userid,
          'pin_type_comment' => comments_helper::PINNED_COMMENT,
          'userid_page' => $userid,
          'pin_type_page' => comments_helper::PINNED_PAGE,
        ];
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        $params = [
            'contextid' => $context->id,
        ];

        // Get userlist by comments.
        $sql = "SELECT userid
            FROM {block_socialcomments_cmmnts}
            WHERE contextid = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Get userlist by replies.
        $sql = "SELECT r.userid
            FROM {block_socialcomments_replies}
            INNER JOIN {block_socialcomments_cmmnts} sc ON sc.id = r.commentid
            WHERE contextid = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Get userlist by subscriptions.
        $sql = "SELECT userid
            FROM {block_socialcomments_subscrs}
            WHERE contextid = :contextid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Get userlist by pins.
        $sql = "SELECT p.userid
            FROM {block_socialcomments_pins} p
            INNER JOIN {block_socialcomments_cmmnts} sc ON sc.id = p.itemid
            WHERE (contextid = :contextid)";
        $userlist->add_from_sql('userid', $sql, $params);

        return $userlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
    }

  
    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context A user context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
  
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

    }

    /**
     * Delete all user data for the specified user.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
    }
}
