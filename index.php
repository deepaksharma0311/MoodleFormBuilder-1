<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');

$context = context_system::instance();
require_login();
require_capability('local/formbuilder:createforms', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/formbuilder/index.php');
$PAGE->set_title(get_string('formbuilder', 'local_formbuilder'));
$PAGE->set_heading(get_string('formbuilder', 'local_formbuilder'));
$PAGE->set_pagelayout('standard');

// Get all forms
$forms = $DB->get_records('local_formbuilder_forms', null, 'timemodified DESC');

// Check capabilities
$canmanage = has_capability('local/formbuilder:manageforms', $context);
$cancreate = has_capability('local/formbuilder:createforms', $context);

$output = $PAGE->get_renderer('local_formbuilder');
$page = new \local_formbuilder\output\form_builder_page($forms, $canmanage, $cancreate);

echo $OUTPUT->header();
echo $output->render($page);
echo $OUTPUT->footer();
