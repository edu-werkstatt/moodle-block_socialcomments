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
 * Block socialcomments restore steplib.
 *
 * @package    block_socialcomments
 * @subpackage backup-moodle2
 * @copyright  2019 Paul Steffen, EDU-Werkstatt GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete socialcomments  structure for restore
 *
 * @copyright  2019 Paul Steffen, EDU-Werkstatt GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_socialcomments_block_structure_step extends restore_structure_step {

    /**
     * Define structure.
     */
    protected function define_structure() {
        $paths = array();

        $paths[] = new restore_path_element('comment', '/block/socialcomments/comment', true);
        $paths[] = new restore_path_element('reply', '/block/socialcomments/comment/replies/reply');
        $paths[] = new restore_path_element('pin', '/block/socialcomments/pins/pin', true);
        $paths[] = new restore_path_element('subscription', '/block/socialcomments/subscriptions/subscription', true);
        return $paths;
    }

    /**
     * Restore comment entries.
     *
     * @global type $DB
     * @param type $data
     */
    public function process_comment($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->courseid = $this->get_courseid();
        $data->contextid = context_course::instance($data->courseid)->id;
        $data->userid = $this->get_mappingid('user', $data->userid);
        // TODO: Check if groupid mapping is required.

        $newitemid = $DB->insert_record('block_socialcomments_cmmnts', $data);
        $this->set_mapping('block_socialcomments_cmmnts', $oldid, $newitemid);

        if (isset($data->replies['reply'])) {
            foreach ($data->replies['reply'] as $reply) {
                $this->process_comment_reply($reply);
            }
        }
    }

    /**
     * Restore reply entries.
     *
     * @global type $DB
     * @param type $data
     */
    public function process_comment_reply($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->commentid = $this->get_mappingid('block_socialcomments_cmmnts', $data->commentid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $newitemid = $DB->insert_record('block_socialcomments_replies', $data);
        $this->set_mapping('block_socialcomments_replies', $oldid, $newitemid);
    }

    /**
     * Restore pin entries.
     *
     * @global type $DB
     * @param type $data
     */
    public function process_pin($data) {
      global $DB;
      $data = (object)$data;
      $courseid = $this->get_courseid();
      $data->userid = $this->get_mappingid('user', $data->userid);

      if ($data->itemtype == block_socialcomments\local\comments_helper::PINNED_PAGE) {
          $data->itemid = context_course::instance($courseid)->id;
      } elseif ($data->itemtype == block_socialcomments\local\comments_helper::PINNED_COMMENT) {
          $data->itemid =  $this->get_mappingid('block_socialcomments_cmmnts', $data->itemid);
      }
      $newitemid = $DB->insert_record('block_socialcomments_pins', $data);
    }

    /**
     * Restore subscription entries.
     *
     * @global type $DB
     * @param type $data
     */
    public function process_subscription($data) {
        global $DB;
        $data = (object)$data;
        $data->courseid = $this->get_courseid();
        $data->contextid = context_course::instance($data->courseid)->id;
        $data->userid = $this->get_mappingid('user', $data->userid);
        // TODO: check if timelastsent needs to be updated or modified in any way.
        $newitemid = $DB->insert_record('block_socialcomments_subscrs', $data);
    }
}
