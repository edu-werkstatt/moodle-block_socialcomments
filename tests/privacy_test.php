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
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\tests\provider_testcase;
use \block_socialcomments\privacy\provider;

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * Unit tests for blocks\socialcomments\classes\privacy\provider.php
 *
 * @copyright 2019 Paul Steffen, EDU-Werkstatt GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_socialcomments_testcase extends provider_testcase {

    /**
     * Basic setup for these tests.
     */
    public function setUp() {
        $this->resetAfterTest(true);
    }


    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $collection = new collection('block_socialcomments');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(4, $itemcollection);

        $table = reset($itemcollection);
        $this->assertEquals('block_socialcomments_cmmnts', $table->get_name());
        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('contextid', $privacyfields);
        $this->assertArrayHasKey('content', $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('groupid', $privacyfields);
        $this->assertArrayHasKey('courseid', $privacyfields);
        $this->assertArrayHasKey('timemodified', $privacyfields);
        $this->assertEquals('privacy:metadata:block_socialcomments_cmmnts', $table->get_summary());

        $table = next($itemcollection);
        $this->assertEquals('block_socialcomments_subscrs', $table->get_name());
        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('courseid', $privacyfields);
        $this->assertArrayHasKey('contextid', $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('timelastsent', $privacyfields);
        $this->assertArrayHasKey('timemodified', $privacyfields);
        $this->assertEquals('privacy:metadata:block_socialcomments_subscrs', $table->get_summary());

        $table = next($itemcollection);
        $this->assertEquals('block_socialcomments_pins', $table->get_name());
        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('itemtype', $privacyfields);
        $this->assertArrayHasKey('itemid', $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertEquals('privacy:metadata:block_socialcomments_pins', $table->get_summary());

        $table = next($itemcollection);
        $this->assertEquals('block_socialcomments_replies', $table->get_name());
        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('commentid', $privacyfields);
        $this->assertArrayHasKey('content', $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('timemodified', $privacyfields);
        $this->assertEquals('privacy:metadata:block_socialcomments_replies', $table->get_summary());
    }

    /**
     * Test getting the context for the user ID related to this plugin.
     */
    public function test_get_contexts_for_userid() {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $coursecontext = context_course::instance($course->id);

        $record = array('courseid' => $course->id, 'name' => 'Group');
        $group = $generator->create_group($record);

        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');

        $generator->create_group_member(array('userid' => $user->id, 'groupid' => $group->id));

        $this->assertEmpty(provider::get_contexts_for_userid($user->id));

        // Create a comment.
        $this->add_comment($coursecontext, $user, 'Comment0');

        $comment = $DB->get_record('block_socialcomments_cmmnts', array('userid' => $user->id));
        $this->assertNotFalse($comment);

        $data = $DB->count_records('block_socialcomments_cmmnts');
        $this->assertEquals(1, $data);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($coursecontext, $contextlist->current());
    }


    /**
     * Test that data is exported correctly for this plugin.
     */
    public function test_export_user_data() {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $component = 'block_socialcomments';

        $student = $generator->create_user();

        // Enrol user in course and add course items.
        $course = $generator->create_course();
        $coursecontext = context_course::instance($course->id);
        $generator->enrol_user($student->id, $course->id, 'student');


        // Generate some data.
        $commentid0 = $this->add_comment($coursecontext, $student, 'Comment0');
        $commentid1 = $this->add_comment($coursecontext, $student, 'Comment1');

        // Confirm data is present.
        $params = [
          'contextid' => $coursecontext->id,
          'userid' => $student->id,
        ];

        $result = $DB->count_records('block_socialcomments_cmmnts', $params);
        $this->assertEquals(2, $result);

        // Export data for student.
        $approvedlist = new approved_contextlist($student, $component, [$coursecontext->id]);
        provider::export_user_data($approvedlist);

        // Confirm student's data is exported.
        $writer = \core_privacy\local\request\writer::with_context($coursecontext);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * Test that only users within a course context are fetched.
     */
    public function test_get_users_in_context() {
        global $DB, $USER;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $student = $generator->create_user();
        $teacher = $generator->create_user();

        // Enrol users in course.
        $course = $generator->create_course();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');

        // Check nothing is found before socialcomments data iscreated.
        $coursecontext = context_course::instance($course->id);
        $userlist = new \core_privacy\local\request\userlist($coursecontext, 'block_socialcomments');
        provider::get_users_in_context($userlist);
        $this->assertCount(0, $userlist);

        // Generate some data for both users.

        // Crate a pin to course context for teacher.
        $this->add_pin($coursecontext, $teacher);

        // Create a comment for student.
        $this->add_comment($coursecontext, $student, 'Comment0');

        $userlist = new \core_privacy\local\request\userlist($coursecontext, 'block_socialcomments');
        provider::get_users_in_context($userlist);
        $this->assertCount(2, $userlist);
        $userids = $userlist->get_userids();
        $this->assertContains( $student->id, $userids );
        $this->assertContains( $teacher->id, $userids );
    }


    /**
     * Test that data for users in approved userlist is deleted.
     */
    public function test_delete_data_for_users() {
    }

    /**
     * Test that user data is deleted using the context.
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $student = $generator->create_user();
        $studentcontext = context_user::instance($student->id);
        $teacher = $generator->create_user();

        // Enrol users in course and add course items.
        $course1 = $generator->create_course();
        $generator->enrol_user($student->id, $course1->id, 'student');
        $generator->enrol_user($teacher->id, $course1->id, 'editingteacher');

        $course2 = $generator->create_course();
        $generator->enrol_user($student->id, $course2->id, 'student');
        $generator->enrol_user($teacher->id, $course2->id, 'editingteacher');

        // Generate data for each user.
        $i = 0;
        $users = [$student, $teacher];
        $courses = [$course1, $course2];

        foreach ($courses as $course) {
            $coursecontext = context_course::instance($course->id);
            foreach ($users as $user) {
                // Create a comment.
                $this->add_comment($coursecontext, $user, "Comment{i}");
                $i++;
            }
        }

        // Confirm data is present for all users.
        $params = [
            'userid' => $teacher->id,
        ];
        $result = $DB->count_records('block_socialcomments_cmmnts', $params);
        $this->assertEquals(2, $result);

        $params = [
            'userid' => $student->id,
        ];
        $result = $DB->count_records('block_socialcomments_cmmnts', $params);
        $this->assertEquals(2, $result);

        // Attempt system context deletion (should have no effect).
        $systemcontext = context_system::instance();
        provider::delete_data_for_all_users_in_context($systemcontext);

        $params = [];
        $result = $DB->count_records('block_socialcomments_cmmnts', $params);
        $this->assertEquals(4, $result);

        // Delete all data in course1 context.
        $course_1_context = context_course::instance($course1->id);
        $course_2_context = context_course::instance($course2->id);
        provider::delete_data_for_all_users_in_context($course_1_context);

        // Confirm only course1 data is deleted.
        $params = [ 'contextid' => $course_1_context->id ];
        $result = $DB->count_records('block_socialcomments_cmmnts', $params);
        $this->assertEquals(0, $result);

        $params['contextid'] = $course_2_context->id;
        $result = $DB->count_records('block_socialcomments_cmmnts', $params);
        $this->assertEquals(2, $result);
    }

    /**
     * Test that user data is deleted for this user.
     */
    public function test_delete_data_for_user() {
    }

    /**
     * Call external API to create a comment.
     */
    protected function add_comment($coursecontext, $user, $content = 'Comment') {
        global $USER;
        $this->setUser($user);
        // Needed for calling the webservice without sesskey.
        $USER->ignoresesskey = true;

        $params = array('contextid' => $coursecontext->id, 'content' => $content, 'groupid' => 0, 'id' => 0);
        $result = external_api::call_external_function('block_socialcomments_save_comment', $params);
        $this->assertFalse($result['error']);
        return $result['data']['id'];
    }

    /**
     * Call external API to create a pin.
     */
    protected function add_pin($coursecontext, $user, $commentid = 0) {
        global $USER;
        $this->setUser($user);
        // Needed for calling the webservice without sesskey.
        $USER->ignoresesskey = true;
        $params = array(
            'contextid' => $coursecontext->id,
            'checked' => true,
            'commentid' => $commentid
        );
        $result = external_api::call_external_function('block_socialcomments_set_pinned', $params);
        $this->assertFalse($result['error']);
        $this->assertEquals($commentid, $result['data']['commentid']);
     }

     /**
      * Call external API to create a reply.
      */
     protected function add_reply($coursecontext, $user, $commentid, $content = 'Reply') {
         global $USER;
         $this->setUser($user);
         // Needed for calling the webservice without sesskey.
         $USER->ignoresesskey = true;

         $params = array(
             'contextid' => $coursecontext->id,
             'content' => $content,
             'commentid' => $commentid,
             'id' => 0
         );
         $result = external_api::call_external_function('block_socialcomments_save_reply', $params);
         var_dump($result);
         $this->assertFalse($result['error']);
     }
}
