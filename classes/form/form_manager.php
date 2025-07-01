<?php
// This file is part of Moodle - http://moodle.org/

namespace local_formbuilder\form;

defined('MOODLE_INTERNAL') || die();

class form_manager {
    
    /**
     * Get all forms from database
     */
    public static function get_all_forms() {
        global $DB;
        return $DB->get_records('local_formbuilder_forms', null, 'timemodified DESC');
    }
    
    /**
     * Get form by ID
     */
    public static function get_form($id) {
        global $DB;
        return $DB->get_record('local_formbuilder_forms', ['id' => $id]);
    }
    
    /**
     * Save form data
     */
    public static function save_form($data) {
        global $DB;
        
        $record = new \stdClass();
        $record->name = $data['name'];
        $record->description = $data['description'];
        $record->formdata = $data['formdata'];
        $record->settings = $data['settings'];
        $record->timemodified = time();
        
        if (isset($data['id']) && $data['id'] > 0) {
            // Update existing form
            $record->id = $data['id'];
            return $DB->update_record('local_formbuilder_forms', $record);
        } else {
            // Create new form
            $record->timecreated = time();
            $record->active = 1;
            return $DB->insert_record('local_formbuilder_forms', $record);
        }
    }
    
    /**
     * Delete form
     */
    public static function delete_form($id) {
        global $DB;
        return $DB->delete_records('local_formbuilder_forms', ['id' => $id]);
    }
    
    /**
     * Save form submission
     */
    public static function save_submission($formid, $data) {
        global $DB, $USER;
        
        $record = new \stdClass();
        $record->formid = $formid;
        $record->submissiondata = json_encode($data);
        $record->userid = $USER->id ?? 0;
        $record->timecreated = time();
        
        return $DB->insert_record('local_formbuilder_submissions', $record);
    }
    
    /**
     * Get form submissions  
     */
    public static function get_submissions($formid) {
        global $DB;
        return $DB->get_records('local_formbuilder_submissions', ['formid' => $formid], 'timecreated DESC');
    }
    
    /**
     * Generate form HTML from form data
     */
    public static function render_form_fields($formdata) {
        $data = json_decode($formdata, true);
        if (!$data || !isset($data['fields'])) {
            return '';
        }
        
        $html = '';
        foreach ($data['fields'] as $field) {
            $html .= self::render_field($field);
        }
        
        return $html;
    }
    
    /**
     * Render individual field
     */
    private static function render_field($field) {
        $required = isset($field['required']) && $field['required'] ? 'required' : '';
        $placeholder = isset($field['placeholder']) ? 'placeholder="' . htmlspecialchars($field['placeholder']) . '"' : '';
        $label = htmlspecialchars($field['label'] ?? 'Field');
        
        switch ($field['type']) {
            case 'text':
                return "<div class='form-group'><label>$label</label><input type='text' name='{$field['id']}' class='form-control' $placeholder $required></div>";
            
            case 'textarea':
                return "<div class='form-group'><label>$label</label><textarea name='{$field['id']}' class='form-control' rows='3' $placeholder $required></textarea></div>";
            
            case 'email':
                return "<div class='form-group'><label>$label</label><input type='email' name='{$field['id']}' class='form-control' $placeholder $required></div>";
            
            case 'number':
                return "<div class='form-group'><label>$label</label><input type='number' name='{$field['id']}' class='form-control' $placeholder $required></div>";
            
            case 'date':
                return "<div class='form-group'><label>$label</label><input type='date' name='{$field['id']}' class='form-control' $required></div>";
            
            case 'select':
                $options = isset($field['options']) ? $field['options'] : ['Option 1', 'Option 2'];
                $optionsHtml = '';
                foreach ($options as $option) {
                    $optionsHtml .= '<option value="' . htmlspecialchars($option) . '">' . htmlspecialchars($option) . '</option>';
                }
                return "<div class='form-group'><label>$label</label><select name='{$field['id']}' class='form-control' $required>$optionsHtml</select></div>";
            
            case 'checkbox':
                return "<div class='form-group'><div class='form-check'><input type='checkbox' name='{$field['id']}' class='form-check-input' $required><label class='form-check-label'>$label</label></div></div>";
            
            case 'radio':
                $options = isset($field['options']) ? $field['options'] : ['Option 1', 'Option 2'];
                $optionsHtml = '';
                foreach ($options as $option) {
                    $optionsHtml .= "<div class='form-check'><input type='radio' name='{$field['id']}' value='".htmlspecialchars($option)."' class='form-check-input' $required><label class='form-check-label'>".htmlspecialchars($option)."</label></div>";
                }
                return "<div class='form-group'><label>$label</label>$optionsHtml</div>";
            
            case 'file':
                return "<div class='form-group'><label>$label</label><input type='file' name='{$field['id']}' class='form-control' $required></div>";
            
            case 'heading':
                return "<h3>$label</h3>";
            
            case 'paragraph':
                return "<p>$label</p>";
            
            case 'grid':
                $columns = isset($field['columns']) ? $field['columns'] : ['Column 1', 'Column 2'];
                $rows = isset($field['rows']) ? intval($field['rows']) : 3;
                $tableHtml = "<table class='table table-bordered'><thead><tr>";
                foreach ($columns as $col) {
                    $tableHtml .= "<th>" . htmlspecialchars($col) . "</th>";
                }
                $tableHtml .= "</tr></thead><tbody>";
                for ($i = 0; $i < $rows; $i++) {
                    $tableHtml .= "<tr>";
                    foreach ($columns as $j => $col) {
                        $tableHtml .= "<td><input type='text' name='{$field['id']}_row{$i}_col{$j}' class='form-control'></td>";
                    }
                    $tableHtml .= "</tr>";
                }
                $tableHtml .= "</tbody></table>";
                return "<div class='form-group'><label>$label</label>$tableHtml</div>";
            
            case 'calculation':
                return "<div class='form-group'><label>$label</label><input type='number' name='{$field['id']}' class='form-control calculation-field' readonly></div>";
            
            case 'pagebreak':
                return "<hr class='page-break'>";
            
            default:
                return "<div class='form-group'><label>$label</label><input type='text' name='{$field['id']}' class='form-control' $placeholder $required></div>";
        }
    }
}