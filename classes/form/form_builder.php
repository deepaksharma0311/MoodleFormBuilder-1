<?php
// This file is part of Moodle - http://moodle.org/

namespace local_formbuilder\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class form_builder extends \moodleform {

    protected function definition() {
        $mform = $this->_form;
        $data = $this->_customdata;

        // Form name
        $mform->addElement('text', 'name', get_string('formname', 'local_formbuilder'), 'maxlength="255" size="50"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Form description
        $mform->addElement('textarea', 'description', get_string('formdescription', 'local_formbuilder'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('description', PARAM_TEXT);

        // Hidden field for form data (JSON)
        $mform->addElement('hidden', 'formdata', '');
        $mform->setType('formdata', PARAM_RAW);

        // Hidden field for form settings (JSON)
        $mform->addElement('hidden', 'settings', '');
        $mform->setType('settings', PARAM_RAW);

        // Multi-page form option
        $mform->addElement('checkbox', 'multipages', get_string('multipageform', 'local_formbuilder'));
        $mform->addHelpButton('multipages', 'multipageform', 'local_formbuilder');

        // Email notifications settings
        $mform->addElement('header', 'emailsettings', get_string('emailnotifications', 'local_formbuilder'));
        $mform->addElement('checkbox', 'notifyowner', get_string('notifyowner', 'local_formbuilder'));
        $mform->addElement('checkbox', 'notifysubmitter', get_string('notifysubmitter', 'local_formbuilder'));
        
        // Redirect URL
        $mform->addElement('text', 'redirecturl', get_string('redirecturl', 'local_formbuilder'), 'maxlength="255" size="50"');
        $mform->setType('redirecturl', PARAM_URL);
        
        // Custom message
        $mform->addElement('textarea', 'custommessage', get_string('custommessage', 'local_formbuilder'), 'wrap="virtual" rows="3" cols="50"');
        $mform->setType('custommessage', PARAM_TEXT);

        // Hidden field for form ID (if editing)
        if (isset($data['id'])) {
            $mform->addElement('hidden', 'id', $data['id']);
            $mform->setType('id', PARAM_INT);
        }

        // Form builder container
        $html = '<div id="form-builder-container">
                    <div id="field-palette">
                        <h4>Available Fields</h4>
                        <div class="field-types">
                            <div class="field-type" data-type="text">
                                <i class="fa fa-font"></i> Text Input
                            </div>
                            <div class="field-type" data-type="textarea">
                                <i class="fa fa-align-left"></i> Textarea
                            </div>
                            <div class="field-type" data-type="select">
                                <i class="fa fa-caret-down"></i> Dropdown
                            </div>
                            <div class="field-type" data-type="checkbox">
                                <i class="fa fa-check-square"></i> Checkbox
                            </div>
                            <div class="field-type" data-type="radio">
                                <i class="fa fa-dot-circle"></i> Radio Button
                            </div>
                            <div class="field-type" data-type="file">
                                <i class="fa fa-upload"></i> File Upload
                            </div>
                            <div class="field-type" data-type="email">
                                <i class="fa fa-envelope"></i> Email
                            </div>
                            <div class="field-type" data-type="number">
                                <i class="fa fa-hashtag"></i> Number
                            </div>
                            <div class="field-type" data-type="date">
                                <i class="fa fa-calendar"></i> Date
                            </div>
                            <div class="field-type" data-type="heading">
                                <i class="fa fa-header"></i> Heading
                            </div>
                            <div class="field-type" data-type="paragraph">
                                <i class="fa fa-paragraph"></i> Paragraph
                            </div>
                            <div class="field-type" data-type="grid">
                                <i class="fa fa-table"></i> Grid/Table
                            </div>
                            <div class="field-type" data-type="calculation">
                                <i class="fa fa-calculator"></i> Calculation
                            </div>
                            <div class="field-type" data-type="image">
                                <i class="fa fa-image"></i> Image
                            </div>
                            <div class="field-type" data-type="video">
                                <i class="fa fa-video"></i> Video
                            </div>
                            <div class="field-type" data-type="pagebreak">
                                <i class="fa fa-cut"></i> Page Break
                            </div>
                        </div>
                    </div>
                    <div id="form-canvas">
                        <h4>Form Preview</h4>
                        <div id="form-fields" class="sortable">
                            <div class="drop-zone">Drop fields here</div>
                        </div>
                    </div>
                    <div id="field-properties">
                        <h4>Field Properties</h4>
                        <div id="properties-content">
                            Select a field to edit its properties
                        </div>
                    </div>
                </div>';

        $mform->addElement('html', $html);

        // Action buttons
        $this->add_action_buttons(true, get_string('saveform', 'local_formbuilder'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['formdata'])) {
            $errors['formdata'] = 'Form must contain at least one field';
        }

        return $errors;
    }
}
