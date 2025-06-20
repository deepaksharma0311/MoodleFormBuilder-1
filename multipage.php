<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');

$id = required_param('id', PARAM_INT);
$page = optional_param('page', 1, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);

// Get form
$form = $DB->get_record('local_formbuilder_forms', array('id' => $id, 'active' => 1), '*', MUST_EXIST);
$formdata = json_decode($form->formdata, true);
$settings = json_decode($form->settings, true);

// Check if form is multi-page
if (!isset($settings['multipages']) || !$settings['multipages']) {
    redirect(new moodle_url('/local/formbuilder/view.php', array('id' => $id)));
}

$PAGE->set_url('/local/formbuilder/multipage.php', array('id' => $id, 'page' => $page));
$PAGE->set_title($form->name);
$PAGE->set_heading($form->name);
$PAGE->set_pagelayout('standard');

// Check if user can submit forms
$cansubmit = has_capability('local/formbuilder:submitforms', $context);
if (!$cansubmit) {
    require_login();
}

// Split form into pages based on page breaks or one field per page
$pages = array();
$currentPage = array();
$pageNumber = 1;

if (isset($settings['onefieldperpage']) && $settings['onefieldperpage']) {
    // One field per page
    foreach ($formdata['fields'] as $field) {
        if (!in_array($field['type'], ['heading', 'paragraph', 'image', 'video'])) {
            $pages[$pageNumber] = array($field);
            $pageNumber++;
        } else {
            // Add presentation fields to current page
            if (empty($pages[$pageNumber - 1])) {
                $pages[$pageNumber - 1] = array();
            }
            $pages[$pageNumber - 1][] = $field;
        }
    }
} else {
    // Split by page breaks
    foreach ($formdata['fields'] as $field) {
        if ($field['type'] === 'pagebreak') {
            if (!empty($currentPage)) {
                $pages[$pageNumber] = $currentPage;
                $pageNumber++;
                $currentPage = array();
            }
        } else {
            $currentPage[] = $field;
        }
    }
    // Add remaining fields to last page
    if (!empty($currentPage)) {
        $pages[$pageNumber] = $currentPage;
    }
}

$totalPages = count($pages);
if ($page > $totalPages) {
    $page = $totalPages;
}
if ($page < 1) {
    $page = 1;
}

// Get or create session data
$sessionkey = 'formbuilder_multipage_' . $id . '_' . sesskey();
if (!isset($SESSION->$sessionkey)) {
    $SESSION->$sessionkey = array();
}

// Process form submission
if ($_POST) {
    // Save current page data
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'field_') === 0) {
            $SESSION->$sessionkey[$key] = $value;
        }
    }
    
    $action = optional_param('action', '', PARAM_ALPHA);
    if ($action === 'next' && $page < $totalPages) {
        redirect(new moodle_url('/local/formbuilder/multipage.php', array('id' => $id, 'page' => $page + 1)));
    } elseif ($action === 'previous' && $page > 1) {
        redirect(new moodle_url('/local/formbuilder/multipage.php', array('id' => $id, 'page' => $page - 1)));
    } elseif ($action === 'submit') {
        // Final submission - redirect to submit.php
        $data = new stdClass();
        $data->formid = $id;
        foreach ($SESSION->$sessionkey as $key => $value) {
            $data->$key = $value;
        }
        
        $submitkey = 'formbuilder_submission_' . sesskey();
        $SESSION->$submitkey = $data;
        unset($SESSION->$sessionkey);
        
        redirect(new moodle_url('/local/formbuilder/submit.php', array('sesskey' => substr($submitkey, -32))));
    }
}

echo $OUTPUT->header();

// Display progress bar
if ($totalPages > 1) {
    $progress = (($page - 1) / ($totalPages - 1)) * 100;
    echo '<div class="multipage-progress mb-4">';
    echo '<div class="progress">';
    echo '<div class="progress-bar" role="progressbar" style="width: ' . $progress . '%" aria-valuenow="' . $progress . '" aria-valuemin="0" aria-valuemax="100"></div>';
    echo '</div>';
    echo '<p class="text-center mt-2">' . get_string('page', 'local_formbuilder') . ' ' . $page . ' ' . get_string('of', 'local_formbuilder') . ' ' . $totalPages . '</p>';
    echo '</div>';
}

// Display form title and description on first page
if ($page == 1) {
    if (!empty($form->name)) {
        echo '<h3>' . format_text($form->name) . '</h3>';
    }
    if (!empty($form->description)) {
        echo '<p>' . format_text($form->description) . '</p>';
    }
}

// Create form for current page
echo '<form method="post" class="multipage-form">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';

if (isset($pages[$page])) {
    foreach ($pages[$page] as $field) {
        echo '<div class="form-group mb-3">';
        
        $name = 'field_' . $field['id'];
        $label = $field['label'];
        $value = isset($SESSION->$sessionkey[$name]) ? $SESSION->$sessionkey[$name] : '';
        
        switch ($field['type']) {
            case 'text':
                echo '<label class="form-label">' . format_text($label) . '</label>';
                echo '<input type="text" name="' . $name . '" class="form-control" value="' . htmlspecialchars($value) . '">';
                break;
                
            case 'textarea':
                echo '<label class="form-label">' . format_text($label) . '</label>';
                echo '<textarea name="' . $name . '" class="form-control" rows="4">' . htmlspecialchars($value) . '</textarea>';
                break;
                
            case 'select':
                echo '<label class="form-label">' . format_text($label) . '</label>';
                echo '<select name="' . $name . '" class="form-control">';
                echo '<option value="">Please select...</option>';
                if (isset($field['options'])) {
                    foreach ($field['options'] as $option) {
                        $selected = ($value === $option) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars($option) . '</option>';
                    }
                }
                echo '</select>';
                break;
                
            case 'heading':
                $level = isset($field['level']) ? $field['level'] : 3;
                echo '<h' . $level . '>' . format_text($label) . '</h' . $level . '>';
                break;
                
            case 'paragraph':
                echo '<p>' . format_text($label) . '</p>';
                break;
        }
        
        echo '</div>';
    }
}

// Navigation buttons
echo '<div class="multipage-navigation mt-4 d-flex justify-content-between">';
if ($page > 1) {
    echo '<button type="submit" name="action" value="previous" class="btn btn-secondary">' . get_string('previouspage', 'local_formbuilder') . '</button>';
} else {
    echo '<div></div>';
}

if ($page < $totalPages) {
    echo '<button type="submit" name="action" value="next" class="btn btn-primary">' . get_string('nextpage', 'local_formbuilder') . '</button>';
} else {
    echo '<button type="submit" name="action" value="submit" class="btn btn-success">' . get_string('submitform', 'local_formbuilder') . '</button>';
}
echo '</div>';

echo '</form>';

// Add calculation support if needed
$PAGE->requires->js_init_code('
$(document).ready(function() {
    // Auto-calculation support
    $(".calculation-field").each(function() {
        var formula = $(this).data("formula");
        if (formula) {
            var result = 0;
            // Simple calculation parser - extend as needed
            try {
                // Replace field references with values
                var calcFormula = formula.replace(/field_(\w+)/g, function(match, fieldId) {
                    var fieldValue = parseFloat($("input[name=\\"field_" + fieldId + "\\"]").val()) || 0;
                    return fieldValue;
                });
                result = eval(calcFormula);
                $(this).val(result.toFixed(2));
            } catch (e) {
                console.log("Calculation error:", e);
            }
        }
    });
    
    // Update calculations when input values change
    $("input[type=number]").on("input", function() {
        $(".calculation-field").trigger("calculate");
    });
});
');

echo $OUTPUT->footer();