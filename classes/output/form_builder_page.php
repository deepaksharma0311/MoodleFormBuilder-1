<?php
// This file is part of Moodle - http://moodle.org/

namespace local_formbuilder\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

class form_builder_page implements renderable, templatable {

    private $forms;
    private $canmanage;
    private $cancreate;

    public function __construct($forms, $canmanage, $cancreate) {
        $this->forms = $forms;
        $this->canmanage = $canmanage;
        $this->cancreate = $cancreate;
    }

    public function export_for_template(renderer_base $output) {
        global $CFG;

        $forms = array();
        foreach ($this->forms as $form) {
            $forms[] = array(
                'id' => $form->id,
                'name' => format_text($form->name),
                'description' => format_text($form->description),
                'timecreated' => userdate($form->timecreated),
                'timemodified' => userdate($form->timemodified),
                'active' => $form->active,
                'editurl' => new \moodle_url('/local/formbuilder/builder.php', array('id' => $form->id)),
                'viewurl' => new \moodle_url('/local/formbuilder/view.php', array('id' => $form->id)),
                'submissionsurl' => new \moodle_url('/local/formbuilder/manage.php', array('id' => $form->id, 'action' => 'submissions')),
                'deleteurl' => new \moodle_url('/local/formbuilder/manage.php', array('id' => $form->id, 'action' => 'delete')),
            );
        }

        return array(
            'forms' => $forms,
            'canmanage' => $this->canmanage,
            'cancreate' => $this->cancreate,
            'createurl' => new \moodle_url('/local/formbuilder/builder.php'),
            'wwwroot' => $CFG->wwwroot,
        );
    }
}
