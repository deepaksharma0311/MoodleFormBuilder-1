<?php
// This file is part of Moodle - http://moodle.org/

namespace local_formbuilder\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class form_submission extends \moodleform {

    protected function definition() {
        global $DB;
        
        $mform = $this->_form;
        $data = $this->_customdata;
        $formid = $data['formid'];

        // Get form data
        $form = $DB->get_record('local_formbuilder_forms', array('id' => $formid), '*', MUST_EXIST);
        $formdata = json_decode($form->formdata, true);
        $settings = json_decode($form->settings, true);

        // Add form title and description
        if (!empty($form->name)) {
            $mform->addElement('html', '<h3>' . format_text($form->name) . '</h3>');
        }
        if (!empty($form->description)) {
            $mform->addElement('html', '<p>' . format_text($form->description) . '</p>');
        }

        // Generate form fields from JSON data
        if (!empty($formdata['fields'])) {
            foreach ($formdata['fields'] as $field) {
                $this->add_form_field($mform, $field);
            }
        }

        // Hidden field for form ID
        $mform->addElement('hidden', 'formid', $formid);
        $mform->setType('formid', PARAM_INT);

        // Submit button
        $this->add_action_buttons(false, get_string('submitform', 'local_formbuilder'));
    }

    private function add_form_field($mform, $field) {
        $name = 'field_' . $field['id'];
        $label = $field['label'];
        $required = isset($field['required']) && $field['required'];
        $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
        $helptext = isset($field['helptext']) ? $field['helptext'] : '';

        switch ($field['type']) {
            case 'text':
                $attributes = array('placeholder' => $placeholder);
                $mform->addElement('text', $name, $label, $attributes);
                $mform->setType($name, PARAM_TEXT);
                break;

            case 'textarea':
                $attributes = array('placeholder' => $placeholder, 'rows' => 4, 'cols' => 50);
                $mform->addElement('textarea', $name, $label, $attributes);
                $mform->setType($name, PARAM_TEXT);
                break;

            case 'select':
                $options = array('' => 'Please select...');
                if (isset($field['options'])) {
                    foreach ($field['options'] as $option) {
                        $options[$option] = $option;
                    }
                }
                $mform->addElement('select', $name, $label, $options);
                break;

            case 'checkbox':
                $mform->addElement('checkbox', $name, $label);
                break;

            case 'radio':
                if (isset($field['options'])) {
                    $radioarray = array();
                    foreach ($field['options'] as $option) {
                        $radioarray[] = $mform->createElement('radio', $name, '', $option, $option);
                    }
                    $mform->addGroup($radioarray, $name . '_group', $label, array('<br/>'), false);
                }
                break;

            case 'file':
                $mform->addElement('filepicker', $name, $label, null, array('accepted_types' => '*'));
                break;

            case 'email':
                $attributes = array('placeholder' => $placeholder);
                $mform->addElement('text', $name, $label, $attributes);
                $mform->setType($name, PARAM_EMAIL);
                break;

            case 'number':
                $attributes = array('placeholder' => $placeholder);
                $mform->addElement('text', $name, $label, $attributes);
                $mform->setType($name, PARAM_INT);
                break;

            case 'date':
                $mform->addElement('date_selector', $name, $label);
                break;

            case 'heading':
                $level = isset($field['level']) ? $field['level'] : 3;
                $mform->addElement('html', '<h' . $level . '>' . format_text($label) . '</h' . $level . '>');
                break;

            case 'paragraph':
                $mform->addElement('html', '<p>' . format_text($label) . '</p>');
                break;

            case 'grid':
                $columns = isset($field['columns']) ? $field['columns'] : ['Column 1', 'Column 2'];
                $rows = isset($field['rows']) ? intval($field['rows']) : 3;
                $gridhtml = '<div class="grid-field"><label>' . $label . '</label>';
                $gridhtml .= '<table class="table table-bordered">';
                $gridhtml .= '<thead><tr>';
                foreach ($columns as $col) {
                    $gridhtml .= '<th>' . format_text($col) . '</th>';
                }
                $gridhtml .= '</tr></thead><tbody>';
                for ($i = 0; $i < $rows; $i++) {
                    $gridhtml .= '<tr>';
                    foreach ($columns as $j => $col) {
                        $gridhtml .= '<td><input type="text" name="' . $name . '[' . $i . '][' . $j . ']" class="form-control"></td>';
                    }
                    $gridhtml .= '</tr>';
                }
                $gridhtml .= '</tbody></table></div>';
                $mform->addElement('html', $gridhtml);
                break;

            case 'calculation':
                $formula = isset($field['formula']) ? $field['formula'] : '';
                $attributes = array('readonly' => 'readonly', 'data-formula' => $formula, 'class' => 'calculation-field');
                $mform->addElement('text', $name, $label, $attributes);
                $mform->setType($name, PARAM_FLOAT);
                break;

            case 'image':
                $mediaurl = isset($field['mediaurl']) ? $field['mediaurl'] : 'https://via.placeholder.com/300x200';
                $alttext = isset($field['alttext']) ? $field['alttext'] : 'Image';
                $mform->addElement('html', '<div class="image-field"><label>' . format_text($label) . '</label><br><img src="' . $mediaurl . '" alt="' . $alttext . '" class="img-responsive" style="max-width: 100%; height: auto;"></div>');
                break;

            case 'video':
                $mediaurl = isset($field['mediaurl']) ? $field['mediaurl'] : '';
                if (!empty($mediaurl)) {
                    $mform->addElement('html', '<div class="video-field"><label>' . format_text($label) . '</label><br><video width="100%" height="auto" controls><source src="' . $mediaurl . '" type="video/mp4">Your browser does not support the video tag.</video></div>');
                } else {
                    $mform->addElement('html', '<div class="video-field"><label>' . format_text($label) . '</label><br><p>Video URL not specified</p></div>');
                }
                break;

            case 'pagebreak':
                $mform->addElement('html', '<div class="page-break" data-page-break="true"><hr><h4 class="text-center">Page Break</h4><hr></div>');
                break;
        }

        // Add help text if provided
        if (!empty($helptext)) {
            $mform->addHelpButton($name, $name, 'local_formbuilder');
        }

        // Add required rule if field is required
        if ($required && !in_array($field['type'], array('heading', 'paragraph'))) {
            $mform->addRule($name, get_string('requiredfield', 'local_formbuilder'), 'required', null, 'client');
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // Additional custom validation can be added here
        return $errors;
    }
}
