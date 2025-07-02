<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');
require_once($CFG->dirroot . '/local/formbuilder/classes/form_manager.php');

use local_formbuilder\form_manager;

require_login();

$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/local/formbuilder/builder.php', ['id' => $id]);
$PAGE->set_title($id ? get_string('editform', 'local_formbuilder') : get_string('createform', 'local_formbuilder'));
$PAGE->set_heading($id ? get_string('editform', 'local_formbuilder') : get_string('createform', 'local_formbuilder'));
$PAGE->set_pagelayout('standard');

// Check capabilities
require_capability('local/formbuilder:create', $context);

// Load existing form if editing
$form = null;
if ($id) {
    $form = form_manager::get_form($id);
    if (!$form) {
        print_error('Form not found');
    }
}

// Handle form submission
if ($data = data_submitted()) {
    if (isset($data->cancel)) {
        redirect(new moodle_url('/local/formbuilder/index.php'));
    }
    
    // Validate and save form
    if (!empty($data->name) && !empty($data->formdata)) {
        $formdata = new stdClass();
        $formdata->name = $data->name;
        $formdata->description = $data->description ?? '';
        $formdata->formdata = $data->formdata;
        $formdata->settings = json_encode([
            'notifications' => [
                'owner' => !empty($data->notify_owner),
                'submitter' => !empty($data->notify_submitter)
            ],
            'redirect_url' => $data->redirect_url ?? '',
            'success_message' => $data->success_message ?? '',
            'allow_multiple' => !empty($data->allow_multiple),
            'require_login' => !empty($data->require_login)
        ]);
        $formdata->active = !empty($data->active) ? 1 : 0;
        
        if ($id) {
            form_manager::update_form($id, $formdata);
            $successmsg = get_string('formsaved', 'local_formbuilder');
        } else {
            $newid = form_manager::create_form($formdata);
            $successmsg = get_string('formsaved', 'local_formbuilder');
        }
        
        redirect(new moodle_url('/local/formbuilder/index.php'), $successmsg);
    }
}

echo $OUTPUT->header();

// Form builder interface
echo html_writer::start_div('container-fluid');

// Header
echo html_writer::start_div('row mb-4');
echo html_writer::start_div('col-12');
echo html_writer::start_div('d-flex justify-content-between align-items-center');
echo html_writer::tag('h2', $id ? get_string('editform', 'local_formbuilder') : get_string('createform', 'local_formbuilder'));
echo html_writer::link(
    new moodle_url('/local/formbuilder/index.php'),
    '← Back to Forms',
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Main form
echo html_writer::start_tag('form', [
    'method' => 'post',
    'id' => 'formbuilder-form',
    'class' => 'needs-validation',
    'novalidate' => true
]);

// Form basic info
echo html_writer::start_div('row mb-4');
echo html_writer::start_div('col-md-6');
echo html_writer::start_div('card');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', 'Form Information', ['class' => 'mb-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');

// Form name
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', 'Form Name *', ['for' => 'form-name', 'class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'form-name',
    'name' => 'name',
    'class' => 'form-control',
    'value' => $form ? $form->name : '',
    'required' => true
]);
echo html_writer::end_div();

// Form description
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', 'Description', ['for' => 'form-description', 'class' => 'form-label']);
echo html_writer::tag('textarea', $form ? $form->description : '', [
    'id' => 'form-description',
    'name' => 'description',
    'class' => 'form-control',
    'rows' => 3
]);
echo html_writer::end_div();

// Form status
echo html_writer::start_div('form-check');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'id' => 'form-active',
    'name' => 'active',
    'class' => 'form-check-input',
    'value' => '1',
    'checked' => !$form || $form->active
]);
echo html_writer::tag('label', 'Active', ['for' => 'form-active', 'class' => 'form-check-label']);
echo html_writer::end_div();

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col

// Form settings
echo html_writer::start_div('col-md-6');
echo html_writer::start_div('card');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', 'Form Settings', ['class' => 'mb-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');

$settings = $form ? json_decode($form->settings, true) : [];

// Notifications
echo html_writer::start_div('mb-3');
echo html_writer::tag('h6', 'Email Notifications');
echo html_writer::start_div('form-check');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'id' => 'notify-owner',
    'name' => 'notify_owner',
    'class' => 'form-check-input',
    'checked' => $settings['notifications']['owner'] ?? false
]);
echo html_writer::tag('label', 'Notify form owner', ['for' => 'notify-owner', 'class' => 'form-check-label']);
echo html_writer::end_div();

echo html_writer::start_div('form-check');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'id' => 'notify-submitter',
    'name' => 'notify_submitter',
    'class' => 'form-check-input',
    'checked' => $settings['notifications']['submitter'] ?? false
]);
echo html_writer::tag('label', 'Send confirmation email', ['for' => 'notify-submitter', 'class' => 'form-check-label']);
echo html_writer::end_div();
echo html_writer::end_div();

// Redirect URL
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', 'Redirect URL', ['for' => 'redirect-url', 'class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'url',
    'id' => 'redirect-url',
    'name' => 'redirect_url',
    'class' => 'form-control',
    'value' => $settings['redirect_url'] ?? '',
    'placeholder' => 'https://example.com/thank-you'
]);
echo html_writer::end_div();

// Success message
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', 'Success Message', ['for' => 'success-message', 'class' => 'form-label']);
echo html_writer::tag('textarea', $settings['success_message'] ?? 'Thank you for your submission!', [
    'id' => 'success-message',
    'name' => 'success_message',
    'class' => 'form-control',
    'rows' => 2
]);
echo html_writer::end_div();

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col
echo html_writer::end_div(); // row

// Form builder interface
echo html_writer::start_div('row');

// Field types palette
echo html_writer::start_div('col-md-3');
echo html_writer::start_div('card');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', 'Field Types', ['class' => 'mb-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body p-2', ['id' => 'field-palette']);

$field_types = [
    'text' => ['icon' => '📝', 'label' => 'Text Input'],
    'textarea' => ['icon' => '📄', 'label' => 'Textarea'],
    'email' => ['icon' => '📧', 'label' => 'Email'],
    'number' => ['icon' => '🔢', 'label' => 'Number'],
    'date' => ['icon' => '📅', 'label' => 'Date'],
    'select' => ['icon' => '📋', 'label' => 'Dropdown'],
    'checkbox' => ['icon' => '☑️', 'label' => 'Checkbox'],
    'radio' => ['icon' => '🔘', 'label' => 'Radio Button'],
    'file' => ['icon' => '📎', 'label' => 'File Upload'],
    'heading' => ['icon' => '📰', 'label' => 'Heading'],
    'paragraph' => ['icon' => '📃', 'label' => 'Paragraph'],
    'pagebreak' => ['icon' => '➖', 'label' => 'Page Break']
];

foreach ($field_types as $type => $info) {
    echo html_writer::start_div('field-type p-2 mb-2 border rounded cursor-pointer', [
        'data-type' => $type,
        'onclick' => "addField('$type')"
    ]);
    echo $info['icon'] . ' ' . $info['label'];
    echo html_writer::end_div();
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col

// Form canvas
echo html_writer::start_div('col-md-6');
echo html_writer::start_div('card');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', 'Form Preview', ['class' => 'mb-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body', ['id' => 'form-canvas']);
echo html_writer::start_div('', ['id' => 'form-fields']);
echo html_writer::start_div('drop-zone text-center p-5 border border-dashed text-muted');
echo 'Click field types on the left to add them here';
echo html_writer::end_div();
echo html_writer::end_div(); // form-fields
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col

// Field properties panel
echo html_writer::start_div('col-md-3');
echo html_writer::start_div('card');
echo html_writer::start_div('card-header');
echo html_writer::tag('h5', 'Field Properties', ['class' => 'mb-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body', ['id' => 'field-properties']);
echo html_writer::tag('p', 'Select a field to edit its properties', ['class' => 'text-muted']);
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // col

echo html_writer::end_div(); // row

// Hidden field for form data
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'formdata',
    'id' => 'formdata',
    'value' => $form ? $form->formdata : ''
]);

// Action buttons
echo html_writer::start_div('row mt-4');
echo html_writer::start_div('col-12');
echo html_writer::start_div('d-flex gap-2');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('saveform', 'local_formbuilder'),
    'class' => 'btn btn-primary'
]);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'name' => 'cancel',
    'value' => get_string('cancel'),
    'class' => 'btn btn-secondary'
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div(); // container-fluid

// Include CSS and JavaScript
echo html_writer::start_tag('style');
echo '
.field-type {
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.field-type:hover {
    background-color: #e9ecef !important;
    border-color: #007bff !important;
}
.form-field {
    position: relative;
    margin: 10px 0;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background: white;
    cursor: pointer;
}
.form-field:hover {
    border-color: #007bff;
}
.form-field.selected {
    border-color: #007bff;
    background-color: #f0f8ff;
}
.field-controls {
    position: absolute;
    top: 5px;
    right: 5px;
    display: flex;
    gap: 5px;
}
.btn-field-control {
    padding: 2px 6px;
    font-size: 12px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}
.btn-delete {
    background: #dc3545;
    color: white;
}
.btn-move {
    background: #6c757d;
    color: white;
}
#form-canvas {
    min-height: 400px;
}
.drop-zone {
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.cursor-pointer {
    cursor: pointer;
}
';
echo html_writer::end_tag('style');

echo html_writer::start_tag('script');
echo '
let fieldCounter = 0;
let selectedField = null;

// Add field to canvas
function addField(type) {
    fieldCounter++;
    const fieldId = "field_" + fieldCounter;
    
    console.log("Adding field:", type, "ID:", fieldId);
    
    // Remove drop zone
    document.querySelector(".drop-zone")?.remove();
    
    // Create field element
    const fieldElement = createFieldElement(type, fieldId);
    document.getElementById("form-fields").appendChild(fieldElement);
    
    // Select the new field
    selectField(fieldElement);
    
    // Update form data
    updateFormData();
    
    console.log("Field added successfully");
}

// Create field element
function createFieldElement(type, fieldId) {
    const div = document.createElement("div");
    div.className = "form-field";
    div.dataset.type = type;
    div.dataset.id = fieldId;
    div.onclick = function(e) {
        e.stopPropagation();
        selectField(this);
    };
    
    // Field controls
    const controls = document.createElement("div");
    controls.className = "field-controls";
    
    const deleteBtn = document.createElement("button");
    deleteBtn.type = "button";
    deleteBtn.className = "btn-field-control btn-delete";
    deleteBtn.innerHTML = "✕";
    deleteBtn.onclick = function(e) {
        e.stopPropagation();
        removeField(div);
    };
    
    controls.appendChild(deleteBtn);
    div.appendChild(controls);
    
    // Field content
    const content = getFieldHTML(type, fieldId);
    div.innerHTML += content;
    
    return div;
}

// Get field HTML based on type
function getFieldHTML(type, fieldId) {
    switch(type) {
        case "text":
            return `<label class="form-label">Text Input</label><input type="text" class="form-control" placeholder="Enter text" disabled>`;
        case "textarea":
            return `<label class="form-label">Textarea</label><textarea class="form-control" rows="3" placeholder="Enter text" disabled></textarea>`;
        case "email":
            return `<label class="form-label">Email</label><input type="email" class="form-control" placeholder="email@example.com" disabled>`;
        case "number":
            return `<label class="form-label">Number</label><input type="number" class="form-control" placeholder="Enter number" disabled>`;
        case "date":
            return `<label class="form-label">Date</label><input type="date" class="form-control" disabled>`;
        case "select":
            return `<label class="form-label">Dropdown</label><select class="form-control" disabled><option>Option 1</option><option>Option 2</option></select>`;
        case "checkbox":
            return `<div class="form-check"><input type="checkbox" class="form-check-input" disabled><label class="form-check-label">Checkbox Option</label></div>`;
        case "radio":
            return `<fieldset><legend class="form-label">Radio Button</legend><div class="form-check"><input type="radio" class="form-check-input" disabled><label class="form-check-label">Option 1</label></div></fieldset>`;
        case "file":
            return `<label class="form-label">File Upload</label><input type="file" class="form-control" disabled>`;
        case "heading":
            return `<h3 contenteditable="true">Heading Text</h3>`;
        case "paragraph":
            return `<p contenteditable="true">Paragraph text goes here.</p>`;
        case "pagebreak":
            return `<hr><div class="text-center text-muted small">Page Break</div><hr>`;
        default:
            return `<label class="form-label">Unknown Field</label><input type="text" class="form-control" disabled>`;
    }
}

// Select field
function selectField(fieldElement) {
    // Remove previous selection
    document.querySelectorAll(".form-field").forEach(f => f.classList.remove("selected"));
    
    // Select current field
    fieldElement.classList.add("selected");
    selectedField = fieldElement;
    
    // Show properties
    showFieldProperties(fieldElement);
}

// Show field properties
function showFieldProperties(fieldElement) {
    const type = fieldElement.dataset.type;
    const fieldId = fieldElement.dataset.id;
    const label = fieldElement.querySelector("label, legend, h3, p")?.textContent || "Field";
    
    let html = `
        <div class="mb-3">
            <label class="form-label">Field Label</label>
            <input type="text" id="prop-label" class="form-control" value="${label}" onchange="updateFieldLabel()">
        </div>
    `;
    
    if (!["heading", "paragraph", "pagebreak"].includes(type)) {
        html += `
            <div class="form-check mb-3">
                <input type="checkbox" id="prop-required" class="form-check-input" onchange="updateFieldRequired()">
                <label class="form-check-label" for="prop-required">Required</label>
            </div>
        `;
        
        if (["text", "textarea", "email", "number"].includes(type)) {
            html += `
                <div class="mb-3">
                    <label class="form-label">Placeholder</label>
                    <input type="text" id="prop-placeholder" class="form-control" onchange="updateFieldPlaceholder()">
                </div>
            `;
        }
        
        html += `
            <div class="mb-3">
                <label class="form-label">Help Text</label>
                <textarea id="prop-helptext" class="form-control" rows="2" onchange="updateFieldHelpText()"></textarea>
            </div>
        `;
        
        if (["select", "radio", "checkbox"].includes(type)) {
            html += `
                <div class="mb-3">
                    <label class="form-label">Options (one per line)</label>
                    <textarea id="prop-options" class="form-control" rows="3" onchange="updateFieldOptions()">Option 1\nOption 2\nOption 3</textarea>
                </div>
            `;
        }
    }
    
    document.getElementById("field-properties").innerHTML = html;
}

// Update field properties
function updateFieldLabel() {
    if (!selectedField) return;
    const label = document.getElementById("prop-label").value;
    const labelElement = selectedField.querySelector("label, legend, h3, p");
    if (labelElement) {
        labelElement.textContent = label;
    }
    updateFormData();
}

function updateFieldRequired() {
    if (!selectedField) return;
    const required = document.getElementById("prop-required").checked;
    selectedField.dataset.required = required;
    updateFormData();
}

function updateFieldPlaceholder() {
    if (!selectedField) return;
    const placeholder = document.getElementById("prop-placeholder").value;
    const inputElement = selectedField.querySelector("input, textarea");
    if (inputElement) {
        inputElement.placeholder = placeholder;
    }
    selectedField.dataset.placeholder = placeholder;
    updateFormData();
}

function updateFieldHelpText() {
    if (!selectedField) return;
    const helptext = document.getElementById("prop-helptext").value;
    selectedField.dataset.helptext = helptext;
    updateFormData();
}

function updateFieldOptions() {
    if (!selectedField) return;
    const options = document.getElementById("prop-options").value.split("\n").filter(o => o.trim());
    selectedField.dataset.options = JSON.stringify(options);
    
    // Update select options
    const selectElement = selectedField.querySelector("select");
    if (selectElement) {
        selectElement.innerHTML = options.map(opt => `<option>${opt}</option>`).join("");
    }
    
    updateFormData();
}

// Remove field
function removeField(fieldElement) {
    if (confirm("Are you sure you want to remove this field?")) {
        fieldElement.remove();
        selectedField = null;
        document.getElementById("field-properties").innerHTML = `<p class="text-muted">Select a field to edit its properties</p>`;
        
        // Add drop zone back if no fields
        if (document.querySelectorAll(".form-field").length === 0) {
            document.getElementById("form-fields").innerHTML = `<div class="drop-zone text-center p-5 border border-dashed text-muted">Click field types on the left to add them here</div>`;
        }
        
        updateFormData();
    }
}

// Update form data
function updateFormData() {
    const formData = { fields: [] };
    
    document.querySelectorAll(".form-field").forEach(field => {
        const fieldData = {
            id: field.dataset.id,
            type: field.dataset.type,
            label: field.querySelector("label, legend, h3, p")?.textContent || "Field",
            required: field.dataset.required === "true",
            placeholder: field.dataset.placeholder || "",
            helptext: field.dataset.helptext || ""
        };
        
        if (field.dataset.options) {
            try {
                fieldData.options = JSON.parse(field.dataset.options);
            } catch (e) {
                fieldData.options = [];
            }
        }
        
        formData.fields.push(fieldData);
    });
    
    document.getElementById("formdata").value = JSON.stringify(formData);
    console.log("Form data updated:", formData);
}

// Initialize existing form data if editing
document.addEventListener("DOMContentLoaded", function() {
    const existingData = document.getElementById("formdata").value;
    if (existingData && existingData !== "") {
        try {
            const data = JSON.parse(existingData);
            if (data.fields && data.fields.length > 0) {
                document.querySelector(".drop-zone")?.remove();
                
                data.fields.forEach(fieldData => {
                    const fieldElement = createFieldElement(fieldData.type, fieldData.id);
                    
                    // Set field properties
                    const labelElement = fieldElement.querySelector("label, legend, h3, p");
                    if (labelElement) {
                        labelElement.textContent = fieldData.label;
                    }
                    
                    fieldElement.dataset.required = fieldData.required || false;
                    fieldElement.dataset.placeholder = fieldData.placeholder || "";
                    fieldElement.dataset.helptext = fieldData.helptext || "";
                    
                    if (fieldData.options) {
                        fieldElement.dataset.options = JSON.stringify(fieldData.options);
                    }
                    
                    document.getElementById("form-fields").appendChild(fieldElement);
                });
            }
        } catch (e) {
            console.error("Error loading existing form data:", e);
        }
    }
    
    updateFormData();
    console.log("Form builder initialized");
});

// Form submission handler
document.getElementById("formbuilder-form").addEventListener("submit", function(e) {
    const formName = document.getElementById("form-name").value;
    if (!formName.trim()) {
        alert("Please enter a form name");
        e.preventDefault();
        return false;
    }
    
    updateFormData();
    const formData = document.getElementById("formdata").value;
    
    if (!formData || formData === "" || formData === "{}") {
        if (!confirm("This form has no fields. Do you want to continue?")) {
            e.preventDefault();
            return false;
        }
    }
    
    console.log("Submitting form with data:", formData);
});
';
echo html_writer::end_tag('script');

echo $OUTPUT->footer();