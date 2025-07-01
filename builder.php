<?php
session_start();
require_once 'database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel'])) {
        header('Location: index.php');
        exit;
    } else {
        try {
            $db = new FormBuilderDB();
            $formData = [
                'id' => $id,
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'formdata' => $_POST['formdata'] ?? '{}',
                'settings' => json_encode([
                    'notifyowner' => isset($_POST['notifyowner']),
                    'notifysubmitter' => isset($_POST['notifysubmitter']),
                    'redirecturl' => $_POST['redirecturl'] ?? '',
                    'custommessage' => $_POST['custommessage'] ?? '',
                    'multipages' => isset($_POST['multipages'])
                ])
            ];
            
            $savedId = $db->saveForm($formData);
            header('Location: index.php?success=1');
            exit;
        } catch (Exception $e) {
            $error = "Failed to save form: " . $e->getMessage();
        }
    }
}

// Load form data for editing
$form = null;
if ($id) {
    try {
        $db = new FormBuilderDB();
        $form = $db->getFormById($id);
    } catch (Exception $e) {
        $error = "Failed to load form: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <div class="local-formbuilder-builder">
            <div class="form-builder-toolbar d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <h2><i class="fas fa-tools"></i> <?php echo $id ? 'Edit Form' : 'Create Form'; ?></h2>
                <div class="toolbar-actions">
                    <a href="index.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back</a>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" class="form-builder-form">
                <!-- Basic form information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Form Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo $form ? htmlspecialchars($form->name) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Form Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo $form ? htmlspecialchars($form->description) : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Form builder interface -->
                <div id="form-builder-container" class="mb-4">
                    <div class="row">
                        <!-- Field palette -->
                        <div class="col-md-3">
                            <div id="field-palette" class="p-3 border rounded">
                                <h5>Available Fields</h5>
                                <div class="field-types">
                                    <?php 
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
                                    
                                    foreach ($fieldTypes as $fieldType): ?>
                                        <div class="field-type p-2 mb-2 border rounded cursor-pointer" data-type="<?php echo $fieldType['type']; ?>">
                                            <i class="fa <?php echo $fieldType['icon']; ?> me-2"></i><?php echo $fieldType['label']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Form canvas -->
                        <div class="col-md-6">
                            <div id="form-canvas" class="p-3 border rounded">
                                <h5>Form Preview</h5>
                                <div id="form-fields" class="sortable min-height-300">
                                    <div class="drop-zone text-center text-muted p-5 border-dashed">Drop fields here to build your form</div>
                                </div>
                            </div>
                        </div>

                        <!-- Field properties -->
                        <div class="col-md-3">
                            <div id="field-properties" class="p-3 border rounded">
                                <h5>Field Properties</h5>
                                <div id="properties-content">
                                    <p class="text-muted">Select a field to edit its properties</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings section -->
                <div class="settings-section border rounded p-3 mb-4">
                    <h5>Form Settings</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="multipages" class="form-check-input" id="multipages">
                                <label class="form-check-label" for="multipages">Multi-page form</label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="notifyowner" class="form-check-input" id="notifyowner">
                                <label class="form-check-label" for="notifyowner">Notify form owner of submissions</label>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="notifysubmitter" class="form-check-input" id="notifysubmitter">
                                <label class="form-check-label" for="notifysubmitter">Send confirmation email to submitter</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Redirect URL after submission</label>
                                <input type="url" name="redirecturl" class="form-control" placeholder="https://example.com/thank-you">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Custom success message</label>
                                <textarea name="custommessage" class="form-control" rows="2" placeholder="Thank you for your submission!"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden field for form data -->
                <input type="hidden" name="formdata" id="formdata" value="<?php echo $form ? htmlspecialchars($form->formdata) : ''; ?>">

                <!-- Action buttons -->
                <div class="form-actions d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Form</button>
                    <button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <style>
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
    </style>

    <script>
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
                case "radio":
                    return "<div class=\"form-check\"><input type=\"radio\" class=\"form-check-input\" disabled><label class=\"form-check-label\">Radio Option</label></div>";
                case "file":
                    return "<label>File Upload</label><input type=\"file\" class=\"form-control\" disabled>";
                case "email":
                    return "<label>Email Address</label><input type=\"email\" class=\"form-control\" placeholder=\"name@example.com\" disabled>";
                case "number":
                    return "<label>Number</label><input type=\"number\" class=\"form-control\" disabled>";
                case "date":
                    return "<label>Date</label><input type=\"date\" class=\"form-control\" disabled>";
                case "heading":
                    return "<h3>Heading Text</h3>";
                case "paragraph":
                    return "<p>Paragraph text goes here. You can add descriptive content or instructions.</p>";
                case "grid":
                    return "<label>Grid/Table</label><table class=\"table table-bordered\"><thead><tr><th>Column 1</th><th>Column 2</th></tr></thead><tbody><tr><td><input type=\"text\" class=\"form-control\" disabled></td><td><input type=\"text\" class=\"form-control\" disabled></td></tr></tbody></table>";
                case "calculation":
                    return "<label>Calculation Field</label><div class=\"input-group\"><input type=\"text\" class=\"form-control\" placeholder=\"Result\" disabled><span class=\"input-group-text\"><i class=\"fa fa-calculator\"></i></span></div>";
                case "image":
                    return "<label>Image</label><div class=\"border p-3 text-center\"><i class=\"fa fa-image fa-2x text-muted\"></i><br><small>Image placeholder</small></div>";
                case "video":
                    return "<label>Video</label><div class=\"border p-3 text-center\"><i class=\"fa fa-video fa-2x text-muted\"></i><br><small>Video placeholder</small></div>";
                case "pagebreak":
                    return "<hr><div class=\"text-center text-muted\"><i class=\"fa fa-cut\"></i> Page Break</div><hr>";
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
                if ($("#form-fields .form-field").length === 0) {
                    $("#form-fields").append('<div class="drop-zone text-center text-muted p-5 border-dashed">Drop fields here to build your form</div>');
                }
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
            
            if (!["heading", "paragraph", "pagebreak", "image", "video"].includes(fieldType)) {
                html += "<div class=\"mb-3\">" +
                    "<div class=\"form-check\">" +
                    "<input type=\"checkbox\" id=\"field-required\" class=\"form-check-input\">" +
                    "<label class=\"form-check-label\" for=\"field-required\">Required</label>" +
                    "</div></div>";
                    
                html += "<div class=\"mb-3\">" +
                    "<label class=\"form-label\">Help Text</label>" +
                    "<input type=\"text\" id=\"field-help\" class=\"form-control\" placeholder=\"Optional help text\">" +
                    "</div>";
            }
            
            if (["text", "textarea", "email", "number"].includes(fieldType)) {
                html += "<div class=\"mb-3\">" +
                    "<label class=\"form-label\">Placeholder</label>" +
                    "<input type=\"text\" id=\"field-placeholder\" class=\"form-control\" placeholder=\"Placeholder text\">" +
                    "</div>";
            }
            
            if (["select", "checkbox", "radio"].includes(fieldType)) {
                html += "<div class=\"mb-3\">" +
                    "<label class=\"form-label\">Options (one per line)</label>" +
                    "<textarea id=\"field-options\" class=\"form-control\" rows=\"3\" placeholder=\"Option 1\\nOption 2\\nOption 3\"></textarea>" +
                    "</div>";
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
        
        $(document).on("change", "#field-placeholder", function() {
            if (selectedField) {
                var newPlaceholder = $(this).val();
                selectedField.find("input, textarea").attr("placeholder", newPlaceholder);
                updateFormData();
            }
        });
        
        // Load existing form data if editing
        <?php if ($form && !empty($form->formdata)): ?>
        var existingData = <?php echo $form->formdata; ?>;
        if (existingData && existingData.fields) {
            $("#form-fields .drop-zone").remove();
            existingData.fields.forEach(function(fieldData) {
                var field = createFieldElement(fieldData.type);
                field.find("label").first().text(fieldData.label);
                $("#form-fields").append(field);
            });
            updateFormData();
        }
        <?php endif; ?>
    });
    </script>
</body>
</html>