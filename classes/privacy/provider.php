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


defined('MOODLE_INTERNAL') || die();

class provider implements
  
    public static function get_metadata(collection $collection) : collection {
      
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
