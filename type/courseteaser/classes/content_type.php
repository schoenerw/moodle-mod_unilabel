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
 * unilabel type course teaser
 *
 * @package     unilabeltype_courseteaser
 * @author      Andreas Grabs <info@grabs-edv.de>
 * @copyright   2018 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace unilabeltype_courseteaser;

defined('MOODLE_INTERNAL') || die;

/**
 * Content type definition
 * @package     unilabeltype_courseteaser
 * @author      Andreas Grabs <info@grabs-edv.de>
 * @copyright   2018 onwards Grabs EDV {@link https://www.grabs-edv.de}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_type extends \mod_unilabel\content_type {
    /** @var \stdClass $unilabeltyperecord */
    private $unilabeltyperecord;

    /**
     * Add elements to the activity settings form.
     *
     * @param \mod_unilabel\edit_content_form $form
     * @param \context $context
     * @return void
     */
    public function add_form_fragment(\mod_unilabel\edit_content_form $form, \context $context) {
        $mform = $form->get_mform();
        $prefix = 'unilabeltype_courseteaser_';

        $mform->addElement('advcheckbox', $prefix.'showintro', get_string('showunilabeltext', 'unilabeltype_courseteaser'));

        $mform->addElement('header', $prefix.'hdr', $this->get_name());
        $mform->addHelpButton($prefix.'hdr', 'pluginname', 'unilabeltype_courseteaser');

        $mform->addElement('course', $prefix.'courses', get_string('courses', 'unilabeltype_courseteaser'), array('multiple' => true));
        $mform->addRule($prefix.'courses', get_string('required'), 'required', null, 'client');

        $select = array(
            'carousel' => get_string('carousel', 'unilabeltype_courseteaser'),
            'grid' => get_string('grid', 'unilabeltype_courseteaser'),
        );

        $mform->addElement('select', $prefix.'presentation', get_string('presentation', 'unilabeltype_courseteaser'), $select);
    }

    /**
     * Get the default values for the settings form
     *
     * @param array $data
     * @param \stdClass $unilabel
     * @return array
     */
    public function get_form_default($data, $unilabel) {
        global $DB;
        $config = get_config('unilabeltype_courseteaser');
        $prefix = 'unilabeltype_courseteaser_';

        if (!$unilabeltyperecord = $this->load_unilabeltype_record($unilabel->id)) {
            $data[$prefix.'presentation'] = $config->presentation;
            $data[$prefix.'showintro'] = $config->showintro;
        } else {
            $data[$prefix.'presentation'] = $unilabeltyperecord->presentation;
            $data[$prefix.'showintro'] = $unilabeltyperecord->showintro;
            $data[$prefix.'courses'] = explode(',', $unilabeltyperecord->courses);
        }

        return $data;
    }

    /**
     * Get the namespace of this content type
     *
     * @return string
     */
    public function get_namespace() {
        return __NAMESPACE__;
    }

    /**
     * Get the html formated content for this type.
     *
     * @param \stdClass $unilabel
     * @param \stdClass $cm
     * @param \plugin_renderer_base $renderer
     * @return string
     */
    public function get_content($unilabel, $cm, \plugin_renderer_base $renderer) {
        $config = get_config('unilabeltype_courseteaser');

        if (!$unilabeltyperecord = $this->load_unilabeltype_record($unilabel->id)) {
            $content = [
                'cmid' => $cm->id,
                'hasitems' => false,
            ];
            $template = 'default';
        } else {
            $intro = $this->format_intro($unilabel, $cm);
            $showintro = !empty($unilabeltyperecord->showintro);
            $items = $this->get_course_infos($unilabel);
            $content = [
                'showintro' => $showintro,
                'intro' => $showintro ? $intro : '',
                'interval' => $config->carouselinterval,
                'height' => 300,
                'items' => array_values($items),
                'hasitems' => count($items) > 0,
                'cmid' => $cm->id,
            ];
            switch ($unilabeltyperecord->presentation) {
                case 'carousel':
                    $template = 'carousel';
                    break;
                case 'grid':
                    $template = 'grid';
                    break;
                default:
                    $template = 'default';
            }
        }
        $content = $renderer->render_from_template('unilabeltype_courseteaser/'.$template, $content);
        return $content;
    }

    /**
     * Delete the content of this type
     *
     * @param int $unilabelid
     * @return void
     */
    public function delete_content($unilabelid) {
        global $DB;

        $DB->delete_records('unilabeltype_courseteaser', array('unilabelid' => $unilabelid));
    }

    /**
     * Save the content from settings page
     *
     * @param \stdClass $formdata
     * @param \stdClass $unilabel
     * @return bool
     */
    public function save_content($formdata, $unilabel) {
        global $DB;

        if (!$unilabletyperecord = $this->load_unilabeltype_record($unilabel->id)) {
            $unilabletyperecord = new \stdClass();
            $unilabletyperecord->unilabelid = $unilabel->id;
        }
        $prefix = 'unilabeltype_courseteaser_';

        $unilabletyperecord->presentation = $formdata->{$prefix.'presentation'};
        $unilabletyperecord->showintro = $formdata->{$prefix.'showintro'};
        $unilabletyperecord->courses = implode(',', $formdata->{$prefix.'courses'});

        if (empty($unilabletyperecord->id)) {
            $unilabletyperecord->id = $DB->insert_record('unilabeltype_courseteaser', $unilabletyperecord);
        } else {
            $DB->update_record('unilabeltype_courseteaser', $unilabletyperecord);
        }

        return !empty($unilabletyperecord->id);
    }

    /**
     * Load and cache the unilabel record
     *
     * @param int $unilabelid
     * @return \stdClass
     */
    private function load_unilabeltype_record($unilabelid) {
        global $DB;

        if (empty($this->unilabeltyperecord)) {
            $this->unilabeltyperecord = $DB->get_record('unilabeltype_courseteaser', array('unilabelid' => $unilabelid));
        }
        return $this->unilabeltyperecord;
    }

    /**
     * Get all needed info to the courses
     *
     * @param \stdClass $unilabel
     * @return array
     */
    public function get_course_infos($unilabel) {
        global $DB, $CFG;

        require_once($CFG->libdir.'/coursecatlib.php');

        $unilabeltyperecord = $this->load_unilabeltype_record($unilabel->id);

        if (empty($unilabeltyperecord->courses)) {
            return array();
        }

        $courseids = explode(',', $unilabeltyperecord->courses);
        $items = array();
        $counter = 0;
        foreach ($courseids as $id) {
            if (!$course = $DB->get_record('course', array('id' => $id))) {
                continue;
            }
            $cil = new \course_in_list($course); // Special core object with some nice methods.
            $item = new \stdClass();

            $item->courseid = $course->id;
            $item->courseurl = new \moodle_url('/course/view.php', array('id' => $course->id));
            $item->title = $course->fullname;
            if ($cil->has_course_overviewfiles()) {
                $overviewfiles = $cil->get_course_overviewfiles();

                $file = array_shift($overviewfiles);

                // We have to build our own pluginfile url so we can control the output by our self.
                $imageurl = \moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    'unilabeltype_courseteaser',
                    'overviewfiles',
                    $file->get_itemid(),
                    '/',
                    $file->get_filename()
                );
                $item->imageurl = $imageurl;
            }
            $item->nr = $counter;
            if ($counter == 0) {
                $item->first = true;
            }
            $counter++;
            $items[] = $item;
        }
        return $items;
    }
}
