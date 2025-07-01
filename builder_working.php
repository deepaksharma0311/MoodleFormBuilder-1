<?php
// Simplified working form builder
if (!defined('MOODLE_INTERNAL')) {
    // Mock DB for standalone demo
    class MockDB {
        public function get_record($table, $conditions) {
            return null; // No existing form for new forms
        }
        public function insert_record($table, $record) {
            return rand(1, 1000);
        }
    }
    $DB = new MockDB();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel'])) {
        header('Location: index.php');
        exit;
    }
    
    // Show what was submitted for debugging
    echo '<h2>Form Submitted Successfully!</h2>';
    echo '<p><strong>Form Name:</strong> ' . htmlspecialchars($_POST['name'] ?? 'No name') . '</p>';
    echo '<p><strong>Form Data:</strong> ' . htmlspecialchars($_POST['formdata'] ?? 'No form data') . '</p>';
    echo '<hr>';
}

$form = $id ? $DB->get_record('local_formbuilder_forms', ['id' => $id]) : null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    .field-type {
        cursor: pointer;
        padding: 10px;
        margin: 5px 0;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #f8f9fa;
        transition: all 0.2s;
    }
    .field-type:hover {
        background: #e9ecef;
        border-color: #007bff;
    }
    .form-field {
        margin: 10px 0;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: white;
        position: relative;
    }
    .form-field:hover {
        border-color: #007bff;
    }
    .delete-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 3px;
        padding: 2px 6px;
        cursor: pointer;
    }
    #form-canvas {
        min-height: 300px;
        border: 2px dashed #ddd;
        padding: 20px;
        border-radius: 5px;
    }
    .drop-zone {
        text-align: center;
        color: #666;
        padding: 50px;
    }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2><i class="fa fa-tools"></i> <?php echo $id ? 'Edit Form' : 'Create Form'; ?></h2>
        <a href="index.php" class="btn btn-secondary mb-3"><i class="fa fa-arrow-left"></i> Back</a>
        
        <form method="post" class="form-builder-form">
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Form Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?php echo $form ? htmlspecialchars($form->name) : ''; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Form Description</label>
                    <textarea name="description" class="form-control" rows="2"><?php echo $form ? htmlspecialchars($form->description) : ''; ?></textarea>
                </div>
            </div>

            <div class="row">
                <!-- Field Palette -->
                <div class="col-md-3">
                    <h5>Field Types</h5>
                    <div id="field-palette">
                        <div class="field-type" onclick="addField('text')">
                            <i class="fa fa-font"></i> Text Input
                        </div>
                        <div class="field-type" onclick="addField('textarea')">
                            <i class="fa fa-align-left"></i> Textarea
                        </div>
                        <div class="field-type" onclick="addField('email')">
                            <i class="fa fa-envelope"></i> Email
                        </div>
                        <div class="field-type" onclick="addField('number')">
                            <i class="fa fa-hashtag"></i> Number
                        </div>
                        <div class="field-type" onclick="addField('select')">
                            <i class="fa fa-caret-down"></i> Dropdown
                        </div>
                        <div class="field-type" onclick="addField('checkbox')">
                            <i class="fa fa-check-square"></i> Checkbox
                        </div>
                        <div class="field-type" onclick="addField('radio')">
                            <i class="fa fa-dot-circle"></i> Radio Button
                        </div>
                        <div class="field-type" onclick="addField('date')">
                            <i class="fa fa-calendar"></i> Date
                        </div>
                    </div>
                </div>

                <!-- Form Canvas -->
                <div class="col-md-9">
                    <h5>Form Preview</h5>
                    <div id="form-canvas">
                        <div id="form-fields">
                            <div class="drop-zone">Click field types on the left to add them here</div>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="formdata" id="formdata" value="">
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Form</button>
                <button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>

    <script>
    let fieldCounter = 0;
    
    function addField(type) {
        fieldCounter++;
        const fieldId = 'field_' + fieldCounter;
        
        console.log('Adding field:', type, 'with ID:', fieldId);
        
        // Remove drop zone if it exists
        $('.drop-zone').remove();
        
        // Create field HTML
        let fieldHtml = '';
        switch(type) {
            case 'text':
                fieldHtml = `<label>Text Input</label><input type="text" class="form-control" placeholder="Enter text" disabled>`;
                break;
            case 'textarea':
                fieldHtml = `<label>Multi-line Text</label><textarea class="form-control" rows="3" placeholder="Enter text" disabled></textarea>`;
                break;
            case 'email':
                fieldHtml = `<label>Email Address</label><input type="email" class="form-control" placeholder="name@example.com" disabled>`;
                break;
            case 'number':
                fieldHtml = `<label>Number</label><input type="number" class="form-control" placeholder="Enter number" disabled>`;
                break;
            case 'select':
                fieldHtml = `<label>Dropdown</label><select class="form-control" disabled><option>Option 1</option><option>Option 2</option></select>`;
                break;
            case 'checkbox':
                fieldHtml = `<div class="form-check"><input type="checkbox" class="form-check-input" disabled><label class="form-check-label">Checkbox Option</label></div>`;
                break;
            case 'radio':
                fieldHtml = `<label>Radio Button</label><div class="form-check"><input type="radio" class="form-check-input" disabled><label class="form-check-label">Option 1</label></div>`;
                break;
            case 'date':
                fieldHtml = `<label>Date</label><input type="date" class="form-control" disabled>`;
                break;
            default:
                fieldHtml = `<label>Unknown Field</label><input type="text" class="form-control" disabled>`;
        }
        
        // Add the field to canvas
        const fieldElement = `
            <div class="form-field" data-type="${type}" data-id="${fieldId}">
                <button type="button" class="delete-btn" onclick="removeField(this)">×</button>
                ${fieldHtml}
            </div>
        `;
        
        $('#form-fields').append(fieldElement);
        updateFormData();
        
        console.log('Field added successfully. Total fields:', $('.form-field').length);
    }
    
    function removeField(button) {
        $(button).closest('.form-field').remove();
        updateFormData();
        
        // Add drop zone back if no fields
        if ($('.form-field').length === 0) {
            $('#form-fields').html('<div class="drop-zone">Click field types on the left to add them here</div>');
        }
    }
    
    function updateFormData() {
        const formData = { fields: [] };
        
        $('.form-field').each(function() {
            const field = $(this);
            const fieldData = {
                id: field.data('id'),
                type: field.data('type'),
                label: field.find('label').first().text() || 'Field',
                required: false,
                placeholder: field.find('input, textarea').attr('placeholder') || ''
            };
            formData.fields.push(fieldData);
        });
        
        $('#formdata').val(JSON.stringify(formData));
        console.log('Form data updated:', formData);
    }
    
    // Initialize form data
    $(document).ready(function() {
        updateFormData();
        console.log('Form builder initialized');
    });
    </script>
</body>
</html>