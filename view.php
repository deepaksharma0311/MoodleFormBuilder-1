<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->dirroot . '/local/formbuilder/classes/form_manager.php');

use local_formbuilder\form_manager;

$id = required_param('id', PARAM_INT);
$submitted = optional_param('submitted', 0, PARAM_INT);

// Get form
$form = form_manager::get_form($id);
if (!$form || !$form->active) {
    print_error('Form not found or inactive');
}

// Check if login required
$settings = json_decode($form->settings, true);
if (($settings['require_login'] ?? false) && !isloggedin()) {
    require_login();
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/formbuilder/view.php', ['id' => $id]);
$PAGE->set_title($form->name);
$PAGE->set_heading($form->name);
$PAGE->set_pagelayout('standard');

// Handle form submission
if ($data = data_submitted() && confirm_sesskey()) {
    $errors = form_manager::validate_form_data($form, $_POST);
    
    if (empty($errors)) {
        // Save submission
        $submission_data = [];
        $formdata = json_decode($form->formdata, true);
        
        foreach ($formdata['fields'] as $field) {
            $fieldid = $field['id'];
            $value = $_POST[$fieldid] ?? '';
            $submission_data[$fieldid] = [
                'label' => $field['label'],
                'type' => $field['type'],
                'value' => $value
            ];
        }
        
        form_manager::save_submission($id, $submission_data);
        
        // Redirect or show success message
        if (!empty($settings['redirect_url'])) {
            redirect($settings['redirect_url']);
        } else {
            redirect(new moodle_url('/local/formbuilder/view.php', ['id' => $id, 'submitted' => 1]));
        }
    }
}

echo $OUTPUT->header();

// Show success message if submitted
if ($submitted) {
    $success_message = $settings['success_message'] ?? 'Thank you for your submission!';
    echo html_writer::div($success_message, 'alert alert-success');
    echo html_writer::link(
        new moodle_url('/local/formbuilder/view.php', ['id' => $id]),
        'Submit Another Response',
        ['class' => 'btn btn-primary']
    );
} else {
    // Show form
    echo html_writer::start_div('container');
    
    // Form header
    echo html_writer::tag('h2', format_text($form->name));
    if (!empty($form->description)) {
        echo html_writer::tag('p', format_text($form->description), ['class' => 'lead']);
    }
    
    // Display validation errors
    if (!empty($errors)) {
        echo html_writer::start_div('alert alert-danger');
        echo html_writer::tag('h4', 'Please correct the following errors:');
        echo html_writer::start_tag('ul');
        foreach ($errors as $error) {
            echo html_writer::tag('li', $error);
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_div();
    }
    
    // Form
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'enctype' => 'multipart/form-data',
        'class' => 'needs-validation',
        'novalidate' => true
    ]);
    
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey()
    ]);
    
    // Render form fields
    echo form_manager::render_form($form);
    
    // Submit button
    echo html_writer::start_div('mt-4');
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => 'Submit',
        'class' => 'btn btn-primary btn-lg'
    ]);
    echo html_writer::end_div();
    
    echo html_writer::end_tag('form');
    echo html_writer::end_div();
}

echo $OUTPUT->footer();