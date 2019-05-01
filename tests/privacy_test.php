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
 * Base class for unit tests for block_socialcomments.
 *
 * @package   block_socialcomments
 * @copyright 2019 Paul Steffen, EDU-Werkstatt GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\tests\provider_testcase;
use \block_socialcomments\privacy\provider;

class block_socialcomments_testcase extends provider_testcase {
    public function setUp() {
        $this->resetAfterTest(true);
    }


    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $collection = new collection('block_socialcomments');
        $newcollection = provider::get_metadata($collection);
    }

 
    public function test_get_contexts_for_userid() {
    
    }

    public function test_export_user_data() {

    }

    public function test_get_users_in_context() {
    }

    public function test_delete_data_for_users() {
    }


    public function test_delete_data_for_all_users_in_context() {
 
    }

    public function test_delete_data_for_user() {

    }

}
