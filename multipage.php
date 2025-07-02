<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once('../../config.php');
require_once($CFG->dirroot . '/local/formbuilder/classes/form_manager.php');

$formid = required_param('id', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_url('/local/formbuilder/multipage.php', array('id' => $formid, 'page' => $page));
$PAGE->set_context($context);
$PAGE->set_title(get_string('form', 'local_formbuilder'));

$form_manager = new local_formbuilder_form_manager();
$form = $form_manager->get_form($formid);

if (!$form) {
    print_error('formnotfound', 'local_formbuilder');
}

$PAGE->set_heading($form->name);

// Parse form structure
$form_structure = json_decode($form->structure, true);
if (!$form_structure) {
    print_error('invalidformstructure', 'local_formbuilder');
}

// Get form pages
$pages = local_formbuilder_form_manager::get_form_pages($form_structure);
$total_pages = count($pages);

// Validate page number
if ($page < 0 || $page >= $total_pages) {
    $page = 0;
}

// Handle form submission
$errors = [];
$form_data = session_get_instance()->get('formbuilder_data_' . $formid, []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_data = $_POST;
    
    // Store current page data in session
    foreach ($pages[$page] as $field) {
        if (isset($submitted_data[$field['id']])) {
            $form_data[$field['id']] = $submitted_data[$field['id']];
        }
    }
    session_get_instance()->set('formbuilder_data_' . $formid, $form_data);
    
    // Validate current page
    $page_form = (object)['structure' => json_encode(['fields' => $pages[$page]])];
    $page_errors = local_formbuilder_form_manager::validate_form_data($page_form, $form_data);
    
    if (empty($page_errors)) {
        if (isset($submitted_data['next_page'])) {
            // Go to next page
            $next_page = $page + 1;
            if ($next_page < $total_pages) {
                redirect(new moodle_url('/local/formbuilder/multipage.php', ['id' => $formid, 'page' => $next_page]));
            }
        } elseif (isset($submitted_data['prev_page'])) {
            // Go to previous page
            $prev_page = $page - 1;
            if ($prev_page >= 0) {
                redirect(new moodle_url('/local/formbuilder/multipage.php', ['id' => $formid, 'page' => $prev_page]));
            }
        } elseif (isset($submitted_data['submit_form'])) {
            // Final submission
            $final_errors = local_formbuilder_form_manager::validate_form_data($form, $form_data);
            if (empty($final_errors)) {
                // Save submission
                $submission_data = array(
                    'formid' => $formid,
                    'userid' => $USER->id,
                    'data' => json_encode($form_data),
                    'timecreated' => time()
                );
                
                $form_manager->save_submission($submission_data);
                
                // Clear session data
                session_get_instance()->set('formbuilder_data_' . $formid, null);
                
                // Redirect to success page
                redirect(new moodle_url('/local/formbuilder/view.php', ['id' => $formid, 'submitted' => 1]));
            } else {
                $errors = $final_errors;
            }
        }
    } else {
        $errors = $page_errors;
    }
}

echo $OUTPUT->header();

// Form container
echo html_writer::start_div('container-fluid');
echo html_writer::start_div('row justify-content-center');
echo html_writer::start_div('col-md-8');

// Form card
echo html_writer::start_div('card');
echo html_writer::start_div('card-header');
echo html_writer::tag('h4', $form->name, array('class' => 'mb-0'));
if ($form->description) {
    echo html_writer::tag('p', $form->description, array('class' => 'text-muted mb-0 mt-2'));
}
echo html_writer::end_div();

echo html_writer::start_div('card-body');

// Progress indicator for multi-page forms
if ($total_pages > 1) {
    echo html_writer::start_div('progress mb-4');
    $progress = (($page + 1) / $total_pages) * 100;
    echo html_writer::start_div('progress-bar bg-primary', array(
        'role' => 'progressbar',
        'style' => "width: {$progress}%",
        'aria-valuenow' => $progress,
        'aria-valuemin' => '0',
        'aria-valuemax' => '100'
    ));
    echo "Step " . ($page + 1) . " of {$total_pages}";
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Display errors
if (!empty($errors)) {
    echo html_writer::start_div('alert alert-danger');
    echo html_writer::tag('strong', 'Please correct the following errors:');
    echo html_writer::start_tag('ul');
    foreach ($errors as $error) {
        echo html_writer::tag('li', $error);
    }
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
}

// Form
echo html_writer::start_tag('form', array('method' => 'post', 'enctype' => 'multipart/form-data'));

// Render current page fields
foreach ($pages[$page] as $field) {
    $value = $form_data[$field['id']] ?? '';
    echo local_formbuilder_form_manager::render_form_field($field, $value);
}

// Navigation buttons
echo html_writer::start_div('form-navigation mt-4 d-flex justify-content-between');

// Previous button
if ($page > 0) {
    echo html_writer::empty_tag('input', array(
        'type' => 'submit',
        'name' => 'prev_page',
        'value' => 'Previous',
        'class' => 'btn btn-secondary'
    ));
} else {
    echo html_writer::tag('span', ''); // Empty span for spacing
}

// Next/Submit button
if ($page < $total_pages - 1) {
    echo html_writer::empty_tag('input', array(
        'type' => 'submit',
        'name' => 'next_page',
        'value' => 'Next',
        'class' => 'btn btn-primary'
    ));
} else {
    echo html_writer::empty_tag('input', array(
        'type' => 'submit',
        'name' => 'submit_form',
        'value' => 'Submit',
        'class' => 'btn btn-success'
    ));
}

echo html_writer::end_div();
echo html_writer::end_tag('form');

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
?>

<style>
.form-navigation {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
}

.progress {
    height: 25px;
}

.progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.form-field {
    margin-bottom: 1.5rem;
}

.form-field label {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.form-field .form-text {
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.alert {
    border-radius: 0.375rem;
}

.btn {
    font-weight: 500;
    padding: 0.5rem 1rem;
}

.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
}

.btn-success {
    background-color: #198754;
    border-color: #198754;
}
</style>