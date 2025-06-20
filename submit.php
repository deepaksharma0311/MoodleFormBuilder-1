<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');

$sesskey = required_param('sesskey', PARAM_ALPHANUMEXT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/formbuilder/submit.php');
$PAGE->set_title(get_string('formsubmitted', 'local_formbuilder'));
$PAGE->set_heading(get_string('formsubmitted', 'local_formbuilder'));
$PAGE->set_pagelayout('standard');

// Get form data from session
$sessionkey = 'formbuilder_submission_' . $sesskey;
if (!isset($SESSION->$sessionkey)) {
    throw new moodle_exception('invalidsesskey');
}

$data = $SESSION->$sessionkey;
unset($SESSION->$sessionkey); // Clean up session

// Get form
$form = $DB->get_record('local_formbuilder_forms', array('id' => $data->formid, 'active' => 1), '*', MUST_EXIST);

// Prepare submission data
$submissiondata = array();
foreach ($data as $key => $value) {
    if (strpos($key, 'field_') === 0) {
        $submissiondata[$key] = $value;
    }
}

// Save submission to database
$record = new stdClass();
$record->formid = $data->formid;
$record->userid = $USER->id ?? null;
$record->submissiondata = json_encode($submissiondata);
$record->timecreated = time();
$record->userip = getremoteaddr();

$submissionid = $DB->insert_record('local_formbuilder_submissions', $record);

// Send email notifications
$settings = json_decode($form->settings, true);
if ($settings) {
    // Notify form owner
    if (isset($settings['notifyowner']) && $settings['notifyowner']) {
        $owner = $DB->get_record('user', array('id' => $form->userid));
        if ($owner) {
            $subject = 'New form submission: ' . $form->name;
            $message = "A new submission has been received for your form '{$form->name}'.\n\n";
            $message .= "Submission ID: {$submissionid}\n";
            $message .= "Submitted on: " . userdate(time()) . "\n\n";
            $message .= "View submissions: " . (new moodle_url('/local/formbuilder/manage.php', 
                array('id' => $form->id, 'action' => 'submissions')))->out() . "\n";
            
            email_to_user($owner, $USER, $subject, $message);
        }
    }
    
    // Send confirmation to submitter
    if (isset($settings['notifysubmitter']) && $settings['notifysubmitter'] && $USER->id) {
        $subject = 'Form submission confirmation: ' . $form->name;
        $message = "Thank you for your submission to '{$form->name}'.\n\n";
        $message .= "Your submission has been received and recorded.\n";
        $message .= "Submission ID: {$submissionid}\n";
        $message .= "Submitted on: " . userdate(time()) . "\n";
        
        email_to_user($USER, core_user::get_noreply_user(), $subject, $message);
    }
}

echo $OUTPUT->header();

// Display success message
$custommessage = '';
if ($settings && isset($settings['custommessage'])) {
    $custommessage = $settings['custommessage'];
}

if (empty($custommessage)) {
    $custommessage = get_string('thankyou', 'local_formbuilder');
}

echo $OUTPUT->notification(get_string('formsubmitted', 'local_formbuilder'), 'notifysuccess');
echo $OUTPUT->box($custommessage, 'generalbox');

// Redirect if specified
if ($settings && isset($settings['redirecturl']) && !empty($settings['redirecturl'])) {
    echo html_writer::tag('p', 'You will be redirected in 3 seconds...');
    $PAGE->requires->js_init_code("setTimeout(function() { window.location.href = '{$settings['redirecturl']}'; }, 3000);");
}

echo $OUTPUT->continue_button(new moodle_url('/'));

echo $OUTPUT->footer();
