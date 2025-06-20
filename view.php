<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');

$id = required_param('id', PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);

// Get form
$form = $DB->get_record('local_formbuilder_forms', array('id' => $id, 'active' => 1), '*', MUST_EXIST);

$PAGE->set_url('/local/formbuilder/view.php', array('id' => $id));
$PAGE->set_title($form->name);
$PAGE->set_heading($form->name);
$PAGE->set_pagelayout('standard');

// Check if user can submit forms
$cansubmit = has_capability('local/formbuilder:submitforms', $context);

if (!$cansubmit) {
    require_login();
}

$customdata = array('formid' => $id);
$mform = new \local_formbuilder\form\form_submission(null, $customdata);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));
} else if ($data = $mform->get_data()) {
    // Process form submission - redirect to submit.php
    $submiturl = new moodle_url('/local/formbuilder/submit.php');
    
    // Create a temporary session to store form data
    $sessionkey = 'formbuilder_submission_' . sesskey();
    $SESSION->$sessionkey = $data;
    
    redirect($submiturl->out(false, array('sesskey' => $sessionkey)));
}

$output = $PAGE->get_renderer('local_formbuilder');

echo $OUTPUT->header();

// Display form
echo $output->render_form_display(array('form' => $mform->render()));

echo $OUTPUT->footer();
