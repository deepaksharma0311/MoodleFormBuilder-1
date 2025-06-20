<?php
// This file is part of Moodle - http://moodle.org/

namespace local_formbuilder\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    public function render_form_builder_page(form_builder_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_formbuilder/form_list', $data);
    }

    public function render_form_builder($form) {
        return $this->render_from_template('local_formbuilder/form_builder', array('form' => $form));
    }

    public function render_form_display($formdata) {
        return $this->render_from_template('local_formbuilder/form_display', $formdata);
    }
}
