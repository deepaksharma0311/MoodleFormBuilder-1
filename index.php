<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->dirroot . '/local/formbuilder/classes/form_manager.php');

use local_formbuilder\form_manager;

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/formbuilder/index.php');
$PAGE->set_title(get_string('formbuilder', 'local_formbuilder'));
$PAGE->set_heading(get_string('formbuilder', 'local_formbuilder'));
$PAGE->set_pagelayout('standard');

// Check capabilities
$cancreate = has_capability('local/formbuilder:create', $context);
$canmanage = has_capability('local/formbuilder:manage', $context);

// Get user's forms
$forms = form_manager::get_user_forms();

echo $OUTPUT->header();

// Page header with create button
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-4');
echo html_writer::tag('h2', get_string('formbuilder', 'local_formbuilder'));
if ($cancreate) {
    echo html_writer::link(
        new moodle_url('/local/formbuilder/builder.php'),
        get_string('createform', 'local_formbuilder'),
        ['class' => 'btn btn-primary']
    );
}
echo html_writer::end_div();

if (empty($forms)) {
    // Empty state
    echo html_writer::start_div('text-center py-5');
    echo html_writer::tag('h3', get_string('noforms', 'local_formbuilder'), ['class' => 'text-muted']);
    if ($cancreate) {
        echo html_writer::tag('p', 'Get started by creating your first form.');
        echo html_writer::link(
            new moodle_url('/local/formbuilder/builder.php'),
            get_string('createform', 'local_formbuilder'),
            ['class' => 'btn btn-primary btn-lg mt-3']
        );
    }
    echo html_writer::end_div();
} else {
    // Forms grid
    echo html_writer::start_div('row');
    foreach ($forms as $form) {
        echo html_writer::start_div('col-md-6 col-lg-4 mb-4');
        echo html_writer::start_div('card h-100');
        
        // Card header
        echo html_writer::start_div('card-header d-flex justify-content-between align-items-start');
        echo html_writer::tag('h5', format_text($form->name), ['class' => 'card-title mb-0']);
        
        // Actions dropdown
        echo html_writer::start_div('dropdown');
        echo html_writer::tag('button', '⋮', [
            'class' => 'btn btn-sm btn-outline-secondary',
            'type' => 'button',
            'data-bs-toggle' => 'dropdown'
        ]);
        
        echo html_writer::start_tag('ul', ['class' => 'dropdown-menu']);
        echo html_writer::start_tag('li');
        echo html_writer::link(
            new moodle_url('/local/formbuilder/builder.php', ['id' => $form->id]),
            '✏️ Edit',
            ['class' => 'dropdown-item']
        );
        echo html_writer::end_tag('li');
        
        echo html_writer::start_tag('li');
        echo html_writer::link(
            new moodle_url('/local/formbuilder/view.php', ['id' => $form->id]),
            '👁️ View',
            ['class' => 'dropdown-item']
        );
        echo html_writer::end_tag('li');
        
        echo html_writer::start_tag('li');
        echo html_writer::link(
            new moodle_url('/local/formbuilder/submissions.php', ['id' => $form->id]),
            '📝 Submissions',
            ['class' => 'dropdown-item']
        );
        echo html_writer::end_tag('li');
        
        if ($canmanage) {
            echo html_writer::tag('li', '<hr class="dropdown-divider">');
            echo html_writer::start_tag('li');
            echo html_writer::link(
                '#',
                '🗑️ Delete',
                [
                    'class' => 'dropdown-item text-danger',
                    'onclick' => "confirmDelete({$form->id}); return false;"
                ]
            );
            echo html_writer::end_tag('li');
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_div(); // dropdown
        echo html_writer::end_div(); // card-header
        
        // Card body
        echo html_writer::start_div('card-body');
        if (!empty($form->description)) {
            echo html_writer::tag('p', format_text($form->description), ['class' => 'card-text text-muted']);
        }
        
        echo html_writer::start_div('small text-muted');
        echo html_writer::tag('div', 'Created: ' . userdate($form->timecreated));
        echo html_writer::tag('div', 'Modified: ' . userdate($form->timemodified));
        echo html_writer::end_div();
        echo html_writer::end_div(); // card-body
        
        // Card footer
        echo html_writer::start_div('card-footer');
        $status = $form->active ? 'Active' : 'Inactive';
        $statusclass = $form->active ? 'bg-success' : 'bg-secondary';
        echo html_writer::tag('span', $status, ['class' => "badge {$statusclass}"]);
        echo html_writer::end_div(); // card-footer
        
        echo html_writer::end_div(); // card
        echo html_writer::end_div(); // col
    }
    echo html_writer::end_div(); // row
}

// Add custom CSS
echo html_writer::start_tag('style');
echo '
.card { transition: transform 0.2s, box-shadow 0.2s; }
.card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.form-card { border-radius: 8px; }
';
echo html_writer::end_tag('style');

// Add JavaScript for delete confirmation
echo html_writer::start_tag('script');
echo '
function confirmDelete(formId) {
    if (confirm("Are you sure you want to delete this form? This action cannot be undone.")) {
        window.location.href = "' . $CFG->wwwroot . '/local/formbuilder/delete.php?id=" + formId;
    }
}
';
echo html_writer::end_tag('script');

echo $OUTPUT->footer();