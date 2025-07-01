<?php
// This file is part of Moodle - http://moodle.org/

require_once('./config.php');

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel'])) {
        redirect(new moodle_url('index.php'));
    } else {
        $record = new stdClass();
        $record->name = $_POST['name'] ?? '';
        $record->description = $_POST['description'] ?? '';
        $record->formdata = $_POST['formdata'] ?? '{}';
        $record->settings = json_encode([
            'notifyowner' => isset($_POST['notifyowner']),
            'notifysubmitter' => isset($_POST['notifysubmitter']),
            'redirecturl' => $_POST['redirecturl'] ?? '',
            'custommessage' => $_POST['custommessage'] ?? '',
            'multipages' => isset($_POST['multipages'])
        ]);
        $record->timemodified = time();

        if ($id) {
            // Update existing form
            $record->id = $id;
            $DB->update_record('local_formbuilder_forms', $record);
            redirect(new moodle_url('index.php'), get_string('formupdated', 'local_formbuilder'));
        } else {
            // Create new form
            $record->userid = $USER->id;
            $record->timecreated = time();
            $record->active = 1;
            $newid = $DB->insert_record('local_formbuilder_forms', $record);
            redirect(new moodle_url('index.php'), get_string('formcreated', 'local_formbuilder'));
        }
    }
}

echo $OUTPUT->header();

// Display form builder interface
echo '<div class="local-formbuilder-builder">';
echo '<div class="form-builder-toolbar d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">';
echo '<h2><i class="fas fa-tools"></i> ' . ($id ? 'Edit Form' : 'Create Form') . '</h2>';
echo '<div class="toolbar-actions">';
echo '<a href="index.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back</a>';
echo '</div>';
echo '</div>';

echo '<form method="post" class="form-builder-form">';

// Basic form information
echo '<div class="row mb-4">';
echo '<div class="col-md-6">';
echo '<div class="mb-3">';
echo '<label class="form-label">Form Name <span class="text-danger">*</span></label>';
echo '<input type="text" name="name" class="form-control" value="' . ($form ? htmlspecialchars($form->name) : '') . '" required>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-6">';
echo '<div class="mb-3">';
echo '<label class="form-label">Form Description</label>';
echo '<textarea name="description" class="form-control" rows="3">' . ($form ? htmlspecialchars($form->description) : '') . '</textarea>';
echo '</div>';
echo '</div>';
echo '</div>';

// Form builder interface
echo '<div id="form-builder-container" class="mb-4">';
echo '<div class="row">';

// Field palette
echo '<div class="col-md-3">';
echo '<div id="field-palette" class="p-3 border rounded">';
echo '<h5>Available Fields</h5>';
echo '<div class="field-types">';

$fieldTypes = [
    ['type' => 'text', 'icon' => 'fa-font', 'label' => 'Text Input'],
    ['type' => 'textarea', 'icon' => 'fa-align-left', 'label' => 'Textarea'],
    ['type' => 'select', 'icon' => 'fa-caret-down', 'label' => 'Dropdown'],
    ['type' => 'checkbox', 'icon' => 'fa-check-square', 'label' => 'Checkbox'],
    ['type' => 'radio', 'icon' => 'fa-dot-circle', 'label' => 'Radio Button'],
    ['type' => 'file', 'icon' => 'fa-upload', 'label' => 'File Upload'],
    ['type' => 'email', 'icon' => 'fa-envelope', 'label' => 'Email'],
    ['type' => 'number', 'icon' => 'fa-hashtag', 'label' => 'Number'],
    ['type' => 'date', 'icon' => 'fa-calendar', 'label' => 'Date'],
    ['type' => 'heading', 'icon' => 'fa-header', 'label' => 'Heading'],
    ['type' => 'paragraph', 'icon' => 'fa-paragraph', 'label' => 'Paragraph'],
    ['type' => 'grid', 'icon' => 'fa-table', 'label' => 'Grid/Table'],
    ['type' => 'calculation', 'icon' => 'fa-calculator', 'label' => 'Calculation'],
    ['type' => 'image', 'icon' => 'fa-image', 'label' => 'Image'],
    ['type' => 'video', 'icon' => 'fa-video', 'label' => 'Video'],
    ['type' => 'pagebreak', 'icon' => 'fa-cut', 'label' => 'Page Break']
];

foreach ($fieldTypes as $fieldType) {
    echo '<div class="field-type p-2 mb-2 border rounded cursor-pointer" data-type="' . $fieldType['type'] . '">';
    echo '<i class="fa ' . $fieldType['icon'] . ' me-2"></i>' . $fieldType['label'];
    echo '</div>';
}

echo '</div>';
echo '</div>';
echo '</div>';

// Form canvas
echo '<div class="col-md-6">';
echo '<div id="form-canvas" class="p-3 border rounded">';
echo '<h5>Form Preview</h5>';
echo '<div id="form-fields" class="sortable min-height-300">';
echo '<div class="drop-zone text-center text-muted p-5 border-dashed">Drop fields here to build your form</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Field properties
echo '<div class="col-md-3">';
echo '<div id="field-properties" class="p-3 border rounded">';
echo '<h5>Field Properties</h5>';
echo '<div id="properties-content">';
echo '<p class="text-muted">Select a field to edit its properties</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // row
echo '</div>'; // form-builder-container

// Settings section
echo '<div class="settings-section border rounded p-3 mb-4">';
echo '<h5>Form Settings</h5>';
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<div class="form-check mb-2">';
echo '<input type="checkbox" name="multipages" class="form-check-input" id="multipages">';
echo '<label class="form-check-label" for="multipages">Multi-page form</label>';
echo '</div>';
echo '<div class="form-check mb-2">';
echo '<input type="checkbox" name="notifyowner" class="form-check-input" id="notifyowner">';
echo '<label class="form-check-label" for="notifyowner">Notify form owner of submissions</label>';
echo '</div>';
echo '<div class="form-check mb-3">';
echo '<input type="checkbox" name="notifysubmitter" class="form-check-input" id="notifysubmitter">';
echo '<label class="form-check-label" for="notifysubmitter">Send confirmation email to submitter</label>';
echo '</div>';
echo '</div>';
echo '<div class="col-md-6">';
echo '<div class="mb-3">';
echo '<label class="form-label">Redirect URL after submission</label>';
echo '<input type="url" name="redirecturl" class="form-control" placeholder="https://example.com/thank-you">';
echo '</div>';
echo '<div class="mb-3">';
echo '<label class="form-label">Custom success message</label>';
echo '<textarea name="custommessage" class="form-control" rows="2" placeholder="Thank you for your submission!"></textarea>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Hidden field for form data
echo '<input type="hidden" name="formdata" id="formdata" value="">';

// Action buttons
echo '<div class="form-actions d-flex gap-2">';
echo '<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Form</button>';
echo '<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>';
echo '</div>';

echo '</form>';
echo '</div>'; // local-formbuilder-builder

// Include CSS and JavaScript
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">';
echo '<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css">';
echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
echo '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>';

// Add custom CSS
echo '<style>
.local-formbuilder-builder { padding: 20px; }
#form-builder-container { min-height: 500px; }
#field-palette, #form-canvas, #field-properties { background: #f8f9fa; min-height: 400px; }
.field-type { cursor: move; transition: all 0.2s; }
.field-type:hover { background: #e9ecef !important; border-color: #007bff !important; }
.drop-zone { border: 2px dashed #ddd; border-radius: 5px; }
.form-field { margin: 10px 0; padding: 15px; background: white; border: 1px solid #ddd; border-radius: 5px; position: relative; cursor: pointer; }
.form-field:hover { border-color: #007bff; }
.form-field.selected { border-color: #007bff; background: #f0f8ff; }
.field-controls { position: absolute; top: 5px; right: 5px; }
.delete-field { background: #dc3545; color: white; border: none; border-radius: 3px; padding: 2px 6px; cursor: pointer; }
.min-height-300 { min-height: 300px; }
.border-dashed { border-style: dashed !important; }
.cursor-pointer { cursor: pointer; }
</style>';

// Add JavaScript for drag and drop functionality
echo '<script>
$(document).ready(function() {
    var fieldCounter = 0;
    var selectedField = null;
    
    // Make field types draggable
    $(".field-type").draggable({
        helper: "clone",
        connectToSortable: "#form-fields",
        start: function(event, ui) {
            ui.helper.addClass("dragging");
        }
    });
    
    // Make form canvas sortable
    $("#form-fields").sortable({
        placeholder: "field-placeholder",
        items: ".form-field",
        receive: function(event, ui) {
            var fieldType = ui.item.data("type");
            createField(fieldType, ui.item);
        },
        update: function(event, ui) {
            updateFormData();
        }
    });
    
    // Click handler for field types
    $(".field-type").click(function() {
        var fieldType = $(this).data("type");
        addFieldToCanvas(fieldType);
    });
    
    function addFieldToCanvas(fieldType) {
        var field = createFieldElement(fieldType);
        $("#form-fields .drop-zone").remove();
        $("#form-fields").append(field);
        selectField(field);
        updateFormData();
    }
    
    function createField(fieldType, element) {
        var field = createFieldElement(fieldType);
        element.replaceWith(field);
        $("#form-fields .drop-zone").remove();
        selectField(field);
        updateFormData();
    }
    
    function createFieldElement(fieldType) {
        fieldCounter++;
        var fieldId = "field_" + fieldCounter;
        var fieldHtml = getFieldHtml(fieldType, fieldId);
        
        var field = $("<div class=\"form-field\" data-type=\"" + fieldType + "\" data-id=\"" + fieldId + "\">" +
            "<div class=\"field-controls\">" +
            "<button type=\"button\" class=\"delete-field\">×</button>" +
            "</div>" +
            fieldHtml +
            "</div>");
        
        return field;
    }
    
    function getFieldHtml(fieldType, fieldId) {
        switch (fieldType) {
            case "text":
                return "<label>Text Input</label><input type=\"text\" class=\"form-control\" placeholder=\"Enter text\" disabled>";
            case "textarea":
                return "<label>Multi-line Text</label><textarea class=\"form-control\" rows=\"3\" disabled></textarea>";
            case "select":
                return "<label>Dropdown</label><select class=\"form-control\" disabled><option>Option 1</option></select>";
            case "checkbox":
                return "<div class=\"form-check\"><input type=\"checkbox\" class=\"form-check-input\" disabled><label class=\"form-check-label\">Checkbox</label></div>";
            case "heading":
                return "<h3>Heading Text</h3>";
            case "paragraph":
                return "<p>Paragraph text goes here.</p>";
            case "grid":
                return "<label>Grid/Table</label><table class=\"table table-bordered\"><thead><tr><th>Column 1</th><th>Column 2</th></tr></thead><tbody><tr><td><input type=\"text\" class=\"form-control\" disabled></td><td><input type=\"text\" class=\"form-control\" disabled></td></tr></tbody></table>";
            default:
                return "<label>" + fieldType + " Field</label><input type=\"text\" class=\"form-control\" disabled>";
        }
    }
    
    // Field selection
    $(document).on("click", ".form-field", function(e) {
        e.stopPropagation();
        selectField($(this));
    });
    
    // Field deletion
    $(document).on("click", ".delete-field", function(e) {
        e.stopPropagation();
        var field = $(this).closest(".form-field");
        if (confirm("Delete this field?")) {
            field.remove();
            clearProperties();
            updateFormData();
        }
    });
    
    function selectField(field) {
        $(".form-field").removeClass("selected");
        field.addClass("selected");
        selectedField = field;
        showFieldProperties(field);
    }
    
    function showFieldProperties(field) {
        var fieldType = field.data("type");
        var label = field.find("label").first().text() || "Field Label";
        
        var html = "<div class=\"mb-3\">" +
            "<label class=\"form-label\">Field Label</label>" +
            "<input type=\"text\" id=\"field-label\" class=\"form-control\" value=\"" + label + "\">" +
            "</div>";
        
        if (!["heading", "paragraph", "pagebreak"].includes(fieldType)) {
            html += "<div class=\"mb-3\">" +
                "<div class=\"form-check\">" +
                "<input type=\"checkbox\" id=\"field-required\" class=\"form-check-input\">" +
                "<label class=\"form-check-label\" for=\"field-required\">Required</label>" +
                "</div></div>";
        }
        
        $("#properties-content").html(html);
    }
    
    function clearProperties() {
        $("#properties-content").html("<p class=\"text-muted\">Select a field to edit its properties</p>");
        selectedField = null;
    }
    
    function updateFormData() {
        var formData = { fields: [] };
        
        $("#form-fields .form-field").each(function() {
            var field = $(this);
            var fieldData = {
                id: field.data("id"),
                type: field.data("type"),
                label: field.find("label").first().text() || "Field"
            };
            formData.fields.push(fieldData);
        });
        
        $("#formdata").val(JSON.stringify(formData));
    }
    
    // Property updates
    $(document).on("change", "#field-label", function() {
        if (selectedField) {
            var newLabel = $(this).val();
            selectedField.find("label").first().text(newLabel);
            updateFormData();
        }
    });
});
</script>';

echo $OUTPUT->footer();
