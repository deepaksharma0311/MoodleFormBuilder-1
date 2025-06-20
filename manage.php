<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', 'submissions', PARAM_ALPHA);

$context = context_system::instance();
require_login();

// Get form
$form = $DB->get_record('local_formbuilder_forms', array('id' => $id), '*', MUST_EXIST);

// Check permissions
if ($form->userid != $USER->id && !has_capability('local/formbuilder:manageforms', $context)) {
    require_capability('local/formbuilder:viewsubmissions', $context);
}

$PAGE->set_context($context);
$PAGE->set_url('/local/formbuilder/manage.php', array('id' => $id, 'action' => $action));
$PAGE->set_title($form->name);
$PAGE->set_heading($form->name);
$PAGE->set_pagelayout('standard');

if ($action == 'delete') {
    require_capability('local/formbuilder:manageforms', $context);
    require_sesskey();
    
    // Delete form and all submissions
    $DB->delete_records('local_formbuilder_submissions', array('formid' => $id));
    $DB->delete_records('local_formbuilder_forms', array('id' => $id));
    
    redirect(new moodle_url('/local/formbuilder/index.php'), 
             get_string('formdeleted', 'local_formbuilder'), null, 
             \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

if ($action == 'submissions') {
    // Display submissions
    echo $OUTPUT->heading(get_string('submissions', 'local_formbuilder'));
    
    $submissions = $DB->get_records('local_formbuilder_submissions', array('formid' => $id), 'timecreated DESC');
    
    if (empty($submissions)) {
        echo $OUTPUT->notification(get_string('nosubmissions', 'local_formbuilder'), 'notifymessage');
    } else {
        // Create table
        $table = new html_table();
        $table->head = array('ID', 'Submitted by', 'Date', 'Actions');
        $table->attributes['class'] = 'admintable generaltable';
        
        foreach ($submissions as $submission) {
            $submittedby = 'Anonymous';
            if ($submission->userid) {
                $user = $DB->get_record('user', array('id' => $submission->userid));
                if ($user) {
                    $submittedby = fullname($user);
                }
            }
            
            $actions = html_writer::link(
                new moodle_url('/local/formbuilder/manage.php', 
                    array('id' => $id, 'action' => 'viewsubmission', 'submissionid' => $submission->id)),
                'View',
                array('class' => 'btn btn-sm btn-primary')
            );
            
            $table->data[] = array(
                $submission->id,
                $submittedby,
                userdate($submission->timecreated),
                $actions
            );
        }
        
        echo html_writer::table($table);
    }
} else if ($action == 'viewsubmission') {
    $submissionid = required_param('submissionid', PARAM_INT);
    $submission = $DB->get_record('local_formbuilder_submissions', 
        array('id' => $submissionid, 'formid' => $id), '*', MUST_EXIST);
    
    echo $OUTPUT->heading('Submission #' . $submission->id);
    
    $submissiondata = json_decode($submission->submissiondata, true);
    $formdata = json_decode($form->formdata, true);
    
    if ($submissiondata && $formdata) {
        $table = new html_table();
        $table->head = array('Field', 'Value');
        $table->attributes['class'] = 'admintable generaltable';
        
        foreach ($formdata['fields'] as $field) {
            $fieldkey = 'field_' . $field['id'];
            $value = isset($submissiondata[$fieldkey]) ? $submissiondata[$fieldkey] : '';
            
            // Format value based on field type
            if ($field['type'] == 'file' && !empty($value)) {
                $value = 'File uploaded';
            } else if ($field['type'] == 'checkbox') {
                $value = $value ? 'Yes' : 'No';
            } else if (is_array($value)) {
                $value = implode(', ', $value);
            }
            
            $table->data[] = array(
                $field['label'],
                format_text($value)
            );
        }
        
        echo html_writer::table($table);
    }
    
    echo $OUTPUT->single_button(
        new moodle_url('/local/formbuilder/manage.php', array('id' => $id, 'action' => 'submissions')),
        'Back to submissions'
    );
}

echo $OUTPUT->footer();
