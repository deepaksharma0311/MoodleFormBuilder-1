<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');

$id = optional_param('id', 0, PARAM_INT);

$context = context_system::instance();
require_login();
require_capability('local/formbuilder:createforms', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/formbuilder/builder.php', array('id' => $id));
$PAGE->set_title(get_string('formbuilder', 'local_formbuilder'));
$PAGE->set_heading(get_string('formbuilder', 'local_formbuilder'));
$PAGE->set_pagelayout('standard');

// Load jQuery UI for drag and drop
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->js_call_amd('local_formbuilder/form_builder', 'init');

$form = null;
if ($id) {
    $form = $DB->get_record('local_formbuilder_forms', array('id' => $id), '*', MUST_EXIST);
    // Check if user can edit this form
    if ($form->userid != $USER->id && !has_capability('local/formbuilder:manageforms', $context)) {
        throw new moodle_exception('nopermissions', 'error', '', 'edit this form');
    }
}

$customdata = array();
if ($form) {
    $customdata = array(
        'id' => $form->id,
        'name' => $form->name,
        'description' => $form->description,
        'formdata' => $form->formdata,
        'settings' => $form->settings
    );
}

$mform = new \local_formbuilder\form\form_builder(null, $customdata);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/formbuilder/index.php'));
} else if ($data = $mform->get_data()) {
    $record = new stdClass();
    $record->name = $data->name;
    $record->description = $data->description;
    $record->formdata = $data->formdata;
    $record->settings = $data->settings;
    $record->timemodified = time();

    if ($id) {
        // Update existing form
        $record->id = $id;
        $DB->update_record('local_formbuilder_forms', $record);
        
        // Log event
        $event = \core\event\course_module_updated::create(array(
            'context' => $context,
            'objectid' => $id,
        ));
        $event->trigger();
        
        redirect(new moodle_url('/local/formbuilder/index.php'), 
                 get_string('formupdated', 'local_formbuilder'), null, 
                 \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Create new form
        $record->userid = $USER->id;
        $record->timecreated = time();
        $record->active = 1;
        
        $newid = $DB->insert_record('local_formbuilder_forms', $record);
        
        // Log event
        $event = \core\event\course_module_created::create(array(
            'context' => $context,
            'objectid' => $newid,
        ));
        $event->trigger();
        
        redirect(new moodle_url('/local/formbuilder/index.php'), 
                 get_string('formcreated', 'local_formbuilder'), null, 
                 \core\output\notification::NOTIFY_SUCCESS);
    }
}

$output = $PAGE->get_renderer('local_formbuilder');

echo $OUTPUT->header();

// Set form data if editing
if ($form) {
    $mform->set_data($form);
}

echo $output->render_form_builder($mform->render());

echo $OUTPUT->footer();
