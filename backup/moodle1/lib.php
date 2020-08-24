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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 *
 * @package mod_diary
 * @copyright  2020 AL Rachels <drachels@drachels.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Diary conversion handler.
 */
class moodle1_mod_diary_handler extends moodle1_mod_handler {

    /**
     * Declare the paths in moodle.xml we are able to convert.
     *
     * The method returns list of {@link convert_path} instances.
     * For each path returned, the corresponding conversion method must be
     * defined.
     *
     * Note that the path /MOODLE_BACKUP/COURSE/MODULES/MOD/DIARY does not
     * actually exist in the file. The last element with the module name was
     * appended by the moodle1_converter class.
     *
     * @return array of {@link convert_path} instances.
     */
    public function get_paths() {
        return array(
            new convert_path(
                'diary', '/MOODLE_BACKUP/COURSE/MODULES/MOD/diary',
                array(
                    'renamefields' => array(
                        'assessed' => 'grade'
                    )
                )
            ),
            new convert_path('entries', '/MOODLE_BACKUP/COURSE/MODULES/MOD/diary/ENTRIES'),
            new convert_path('entry', '/MOODLE_BACKUP/COURSE/MODULES/MOD/diary/ENTRIES/ENTRY'),
        );
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/DIARY
     * data available.
     *
     * @param array $data
     * @return array $data
     */
    public function process_diary($data) {

        // Get the course module id and context id.
        $instanceid = $data['id'];
        $cminfo     = $this->get_cminfo($instanceid);
        $moduleid   = $cminfo['id'];
        $contextid  = $this->converter->get_contextid(CONTEXT_MODULE, $moduleid);

        // We now have all information needed to start writing into the file.
        $this->open_xml_writer("activities/diary_{$moduleid}/diary.xml");
        $this->xmlwriter->begin_tag('activity', array('id' => $instanceid, 'moduleid' => $moduleid,
            'modulename' => 'diary', 'contextid' => $contextid));
        $this->xmlwriter->begin_tag('diary', array('id' => $instanceid));

        unset($data['id']);
        foreach ($data as $field => $value) {
            $this->xmlwriter->full_tag($field, $value);
        }

        return $data;
    }

    /**
     * This is executed when the parser reaches the <ENTRIES> opening element.
     */
    public function on_entries_start() {
        $this->xmlwriter->begin_tag('entries');
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/DIARY/ENTRIES/ENTRY
     * data available.
     */
    public function process_entry($data) {
        $this->write_xml('entry', $data, array('/entry/id'));
    }

    /**
     * This is executed when the parser reaches the closing </ENTRIES> element.
     */
    public function on_entries_end() {
        $this->xmlwriter->end_tag('entries');
    }

    /**
     * This is executed when we reach the closing </MOD> tag of our 'diary' path.
     */
    public function on_diary_end() {

        $this->xmlwriter->end_tag('diary');
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();
    }
}
