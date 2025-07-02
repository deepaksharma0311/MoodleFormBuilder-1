<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once('../../config.php');
require_once($CFG->dirroot . '/local/formbuilder/classes/form_manager.php');

require_login();

$formid = required_param('formid', PARAM_INT);
$context = context_system::instance();

$PAGE->set_url('/local/formbuilder/submissions.php', array('formid' => $formid));
$PAGE->set_context($context);
$PAGE->set_title(get_string('submissions', 'local_formbuilder'));
$PAGE->set_heading(get_string('submissions', 'local_formbuilder'));

require_capability('local/formbuilder:viewsubmissions', $context);

$form_manager = new local_formbuilder_form_manager();
$form = $form_manager->get_form($formid);

if (!$form) {
    print_error('formnotfound', 'local_formbuilder');
}

$submissions = $form_manager->get_form_submissions($formid);

echo $OUTPUT->header();

// Page header
echo html_writer::start_div('container-fluid');
echo html_writer::start_div('row');
echo html_writer::start_div('col-12');
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-4');
echo html_writer::tag('h2', get_string('submissions', 'local_formbuilder') . ': ' . $form->name, array('class' => 'mb-0'));
echo html_writer::link(
    new moodle_url('/local/formbuilder/index.php'),
    get_string('backtoforms', 'local_formbuilder'),
    array('class' => 'btn btn-secondary')
);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Statistics
echo html_writer::start_div('container-fluid mb-4');
echo html_writer::start_div('row');
echo html_writer::start_div('col-md-3');
echo html_writer::start_div('card bg-primary text-white');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', count($submissions), array('class' => 'card-title'));
echo html_writer::tag('p', get_string('totalsubmissions', 'local_formbuilder'), array('class' => 'card-text'));
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::start_div('card bg-success text-white');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', $form->name, array('class' => 'card-title'));
echo html_writer::tag('p', get_string('formname', 'local_formbuilder'), array('class' => 'card-text'));
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::start_div('card bg-info text-white');
echo html_writer::start_div('card-body');
$latest_submission = !empty($submissions) ? max(array_column($submissions, 'timecreated')) : 0;
echo html_writer::tag('h5', $latest_submission ? userdate($latest_submission) : get_string('none'), array('class' => 'card-title'));
echo html_writer::tag('p', get_string('latestsubmission', 'local_formbuilder'), array('class' => 'card-text'));
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-md-3');
echo html_writer::start_div('card bg-warning text-white');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', date('Y-m-d', $form->timecreated), array('class' => 'card-title'));
echo html_writer::tag('p', get_string('formcreated', 'local_formbuilder'), array('class' => 'card-text'));
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Submissions table
echo html_writer::start_div('container-fluid');
echo html_writer::start_div('row');
echo html_writer::start_div('col-12');
echo html_writer::start_div('card');
echo html_writer::start_div('card-header d-flex justify-content-between align-items-center');
echo html_writer::tag('h5', get_string('submissionlist', 'local_formbuilder'), array('class' => 'mb-0'));
echo html_writer::start_div('btn-group');
echo html_writer::link('#', get_string('exportcsv', 'local_formbuilder'), array('class' => 'btn btn-outline-primary btn-sm', 'onclick' => 'exportToCSV()'));
echo html_writer::link('#', get_string('exportjson', 'local_formbuilder'), array('class' => 'btn btn-outline-secondary btn-sm', 'onclick' => 'exportToJSON()'));
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card-body');

if (empty($submissions)) {
    echo html_writer::start_div('text-center py-5');
    echo html_writer::tag('h5', get_string('nosubmissions', 'local_formbuilder'), array('class' => 'text-muted'));
    echo html_writer::tag('p', get_string('nosubmissionsdesc', 'local_formbuilder'), array('class' => 'text-muted'));
    echo html_writer::end_div();
} else {
    // Parse form structure to get field labels
    $form_structure = json_decode($form->structure, true);
    $field_labels = array();
    if (isset($form_structure['fields'])) {
        foreach ($form_structure['fields'] as $field) {
            $field_labels[$field['id']] = $field['label'];
        }
    }

    echo html_writer::start_div('table-responsive');
    echo html_writer::start_tag('table', array('class' => 'table table-striped table-hover', 'id' => 'submissions-table'));
    
    // Table header
    echo html_writer::start_tag('thead', array('class' => 'table-dark'));
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', '#', array('scope' => 'col'));
    echo html_writer::tag('th', get_string('submissiondate', 'local_formbuilder'), array('scope' => 'col'));
    echo html_writer::tag('th', get_string('submitter', 'local_formbuilder'), array('scope' => 'col'));
    echo html_writer::tag('th', get_string('responses', 'local_formbuilder'), array('scope' => 'col'));
    echo html_writer::tag('th', get_string('actions', 'local_formbuilder'), array('scope' => 'col'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    // Table body
    echo html_writer::start_tag('tbody');
    
    foreach ($submissions as $index => $submission) {
        echo html_writer::start_tag('tr');
        
        // Submission number
        echo html_writer::tag('td', $index + 1);
        
        // Submission date
        echo html_writer::tag('td', userdate($submission->timecreated, get_string('strftimedaydatetime')));
        
        // Submitter
        $submitter = get_string('anonymous', 'local_formbuilder');
        if ($submission->userid) {
            $user = $DB->get_record('user', array('id' => $submission->userid));
            if ($user) {
                $submitter = fullname($user);
            }
        }
        echo html_writer::tag('td', $submitter);
        
        // Response count
        $responses = json_decode($submission->data, true);
        $response_count = is_array($responses) ? count($responses) : 0;
        echo html_writer::tag('td', $response_count . ' ' . get_string('fields', 'local_formbuilder'));
        
        // Actions
        echo html_writer::start_tag('td');
        echo html_writer::start_div('btn-group');
        echo html_writer::link(
            '#',
            get_string('view', 'local_formbuilder'),
            array(
                'class' => 'btn btn-outline-primary btn-sm',
                'onclick' => 'viewSubmission(' . $submission->id . ')',
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#submissionModal'
            )
        );
        echo html_writer::link(
            '#',
            get_string('delete', 'local_formbuilder'),
            array(
                'class' => 'btn btn-outline-danger btn-sm',
                'onclick' => 'deleteSubmission(' . $submission->id . ')'
            )
        );
        echo html_writer::end_div();
        echo html_writer::end_tag('td');
        
        echo html_writer::end_tag('tr');
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Submission detail modal
echo html_writer::start_div('modal fade', array('id' => 'submissionModal', 'tabindex' => '-1'));
echo html_writer::start_div('modal-dialog modal-lg');
echo html_writer::start_div('modal-content');
echo html_writer::start_div('modal-header');
echo html_writer::tag('h5', get_string('submissiondetails', 'local_formbuilder'), array('class' => 'modal-title'));
echo html_writer::tag('button', '', array('type' => 'button', 'class' => 'btn-close', 'data-bs-dismiss' => 'modal'));
echo html_writer::end_div();
echo html_writer::start_div('modal-body', array('id' => 'submission-details'));
echo html_writer::end_div();
echo html_writer::start_div('modal-footer');
echo html_writer::tag('button', get_string('close', 'local_formbuilder'), array('type' => 'button', 'class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal'));
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
?>

<script>
// Submission data for JavaScript
const submissionsData = <?php echo json_encode($submissions); ?>;
const fieldLabels = <?php echo json_encode($field_labels); ?>;

// View submission details
function viewSubmission(submissionId) {
    const submission = submissionsData.find(s => s.id == submissionId);
    if (!submission) {
        alert('Submission not found');
        return;
    }
    
    const responses = JSON.parse(submission.data);
    let html = '<div class="submission-details">';
    
    // Submission info
    html += '<div class="row mb-3">';
    html += '<div class="col-md-6"><strong>Submission Date:</strong> ' + new Date(submission.timecreated * 1000).toLocaleString() + '</div>';
    html += '<div class="col-md-6"><strong>Submission ID:</strong> #' + submission.id + '</div>';
    html += '</div>';
    
    // Responses
    html += '<h6>Responses:</h6>';
    html += '<div class="table-responsive">';
    html += '<table class="table table-bordered">';
    html += '<thead><tr><th>Field</th><th>Response</th></tr></thead>';
    html += '<tbody>';
    
    for (const [fieldId, value] of Object.entries(responses)) {
        const label = fieldLabels[fieldId] || fieldId;
        let displayValue = value;
        
        // Handle array values (for checkboxes, multi-select)
        if (Array.isArray(value)) {
            displayValue = value.join(', ');
        }
        
        html += '<tr>';
        html += '<td><strong>' + label + '</strong></td>';
        html += '<td>' + (displayValue || '<em>No response</em>') + '</td>';
        html += '</tr>';
    }
    
    html += '</tbody></table>';
    html += '</div>';
    html += '</div>';
    
    document.getElementById('submission-details').innerHTML = html;
}

// Delete submission
function deleteSubmission(submissionId) {
    if (!confirm('Are you sure you want to delete this submission? This action cannot be undone.')) {
        return;
    }
    
    // In a real implementation, this would make an AJAX call to delete the submission
    fetch('<?php echo $CFG->wwwroot; ?>/local/formbuilder/ajax/delete_submission.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            submissionId: submissionId,
            sesskey: M.cfg.sesskey
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error deleting submission: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting submission');
    });
}

// Export to CSV
function exportToCSV() {
    const form = <?php echo json_encode($form); ?>;
    const submissions = submissionsData;
    
    if (submissions.length === 0) {
        alert('No submissions to export');
        return;
    }
    
    // Create CSV content
    let csv = 'Submission ID,Date,Submitter';
    
    // Add field headers
    for (const fieldId in fieldLabels) {
        csv += ',' + fieldLabels[fieldId];
    }
    csv += '\n';
    
    // Add data rows
    submissions.forEach(submission => {
        const responses = JSON.parse(submission.data);
        let row = submission.id + ',' + new Date(submission.timecreated * 1000).toLocaleString() + ',';
        
        // Add submitter
        row += (submission.userid ? 'User ID: ' + submission.userid : 'Anonymous');
        
        // Add field responses
        for (const fieldId in fieldLabels) {
            let value = responses[fieldId] || '';
            if (Array.isArray(value)) {
                value = value.join('; ');
            }
            // Escape commas and quotes
            value = String(value).replace(/"/g, '""');
            if (value.includes(',') || value.includes('"') || value.includes('\n')) {
                value = '"' + value + '"';
            }
            row += ',' + value;
        }
        csv += row + '\n';
    });
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = form.name + '_submissions.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Export to JSON
function exportToJSON() {
    const form = <?php echo json_encode($form); ?>;
    const submissions = submissionsData;
    
    const exportData = {
        form: {
            id: form.id,
            name: form.name,
            description: form.description,
            structure: JSON.parse(form.structure)
        },
        submissions: submissions.map(s => ({
            id: s.id,
            date: new Date(s.timecreated * 1000).toISOString(),
            userid: s.userid,
            responses: JSON.parse(s.data)
        })),
        exported: new Date().toISOString()
    };
    
    const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = form.name + '_submissions.json';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

<style>
.table th {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.submission-details {
    font-size: 14px;
}

.submission-details .table td {
    vertical-align: top;
}

.submission-details .table th {
    background-color: #e9ecef;
    font-weight: 600;
}

.btn-group .btn {
    margin-right: 5px;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
</style>