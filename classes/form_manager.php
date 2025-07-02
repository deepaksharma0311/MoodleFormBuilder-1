<?php
// This file is part of Moodle - http://moodle.org/

namespace local_formbuilder;

defined('MOODLE_INTERNAL') || die();

class form_manager {

    /**
     * Get all forms the user can access
     */
    public static function get_user_forms($userid = null, $courseid = 0) {
        global $DB, $USER;
        
        if ($userid === null) {
            $userid = $USER->id;
        }
        
        $params = ['userid' => $userid, 'courseid' => $courseid];
        return $DB->get_records('local_formbuilder_forms', $params, 'timemodified DESC');
    }

    /**
     * Get a specific form by ID
     */
    public static function get_form($id) {
        global $DB;
        return $DB->get_record('local_formbuilder_forms', ['id' => $id]);
    }

    /**
     * Create a new form
     */
    public static function create_form($data) {
        global $DB, $USER;
        
        $record = new \stdClass();
        $record->name = $data->name;
        $record->description = $data->description ?? '';
        $record->formdata = $data->formdata ?? json_encode(['fields' => []]);
        $record->settings = $data->settings ?? json_encode([]);
        $record->active = $data->active ?? 1;
        $record->courseid = $data->courseid ?? 0;
        $record->userid = $USER->id;
        $record->timecreated = time();
        $record->timemodified = time();
        
        return $DB->insert_record('local_formbuilder_forms', $record);
    }

    /**
     * Update an existing form
     */
    public static function update_form($id, $data) {
        global $DB;
        
        $record = new \stdClass();
        $record->id = $id;
        $record->name = $data->name;
        $record->description = $data->description ?? '';
        $record->formdata = $data->formdata ?? json_encode(['fields' => []]);
        $record->settings = $data->settings ?? json_encode([]);
        $record->active = $data->active ?? 1;
        $record->timemodified = time();
        
        return $DB->update_record('local_formbuilder_forms', $record);
    }

    /**
     * Delete a form
     */
    public static function delete_form($id) {
        global $DB;
        
        // Delete all submissions first
        $DB->delete_records('local_formbuilder_submissions', ['formid' => $id]);
        
        // Delete the form
        return $DB->delete_records('local_formbuilder_forms', ['id' => $id]);
    }

    /**
     * Save form submission
     */
    public static function save_submission($formid, $data, $userid = null) {
        global $DB, $USER;
        
        $record = new \stdClass();
        $record->formid = $formid;
        $record->userid = $userid ?? $USER->id ?? 0;
        $record->submissiondata = json_encode($data);
        $record->ipaddress = getremoteaddr();
        $record->timecreated = time();
        
        return $DB->insert_record('local_formbuilder_submissions', $record);
    }

    /**
     * Get submissions for a form
     */
    public static function get_submissions($formid, $limit = 0) {
        global $DB;
        
        $sql = "SELECT s.*, u.firstname, u.lastname, u.email 
                FROM {local_formbuilder_submissions} s 
                LEFT JOIN {user} u ON s.userid = u.id 
                WHERE s.formid = ? 
                ORDER BY s.timecreated DESC";
        
        return $DB->get_records_sql($sql, [$formid], 0, $limit);
    }

    /**
     * Render form fields as HTML
     */
    public static function render_form($form, $readonly = false) {
        $formdata = json_decode($form->formdata, true);
        if (!$formdata || !isset($formdata['fields'])) {
            return '<p>No fields configured for this form.</p>';
        }

        $html = '';
        foreach ($formdata['fields'] as $field) {
            $html .= self::render_field($field, $readonly);
        }

        return $html;
    }

    /**
     * Render individual field
     */
    private static function render_field($field, $readonly = false) {
        $fieldid = $field['id'] ?? 'field_' . uniqid();
        $label = $field['label'] ?? 'Field';
        $required = ($field['required'] ?? false) && !$readonly ? 'required' : '';
        $placeholder = $field['placeholder'] ?? '';
        $helptext = $field['helptext'] ?? '';
        $disabled = $readonly ? 'disabled' : '';

        $html = '<div class="form-group mb-3">';

        switch ($field['type']) {
            case 'text':
                $html .= "<label for=\"{$fieldid}\" class=\"form-label\">{$label}" . ($required ? ' <span class="text-danger">*</span>' : '') . "</label>";
                $html .= "<input type=\"text\" id=\"{$fieldid}\" name=\"{$fieldid}\" class=\"form-control\" placeholder=\"{$placeholder}\" {$required} {$disabled}>";
                break;

            case 'textarea':
                $html .= "<label for=\"{$fieldid}\" class=\"form-label\">{$label}" . ($required ? ' <span class="text-danger">*</span>' : '') . "</label>";
                $html .= "<textarea id=\"{$fieldid}\" name=\"{$fieldid}\" class=\"form-control\" rows=\"4\" placeholder=\"{$placeholder}\" {$required} {$disabled}></textarea>";
                break;

            case 'email':
                $html .= "<label for=\"{$fieldid}\" class=\"form-label\">{$label}" . ($required ? ' <span class="text-danger">*</span>' : '') . "</label>";
                $html .= "<input type=\"email\" id=\"{$fieldid}\" name=\"{$fieldid}\" class=\"form-control\" placeholder=\"{$placeholder}\" {$required} {$disabled}>";
                break;

            case 'number':
                $html .= "<label for=\"{$fieldid}\" class=\"form-label\">{$label}" . ($required ? ' <span class="text-danger">*</span>' : '') . "</label>";
                $html .= "<input type=\"number\" id=\"{$fieldid}\" name=\"{$fieldid}\" class=\"form-control\" placeholder=\"{$placeholder}\" {$required} {$disabled}>";
                break;

            case 'date':
                $html .= "<label for=\"{$fieldid}\" class=\"form-label\">{$label}" . ($required ? ' <span class="text-danger">*</span>' : '') . "</label>";
                $html .= "<input type=\"date\" id=\"{$fieldid}\" name=\"{$fieldid}\" class=\"form-control\" {$required} {$disabled}>";
                break;

            case 'select':
                $html .= "<label for=\"{$fieldid}\" class=\"form-label\">{$label}" . ($required ? ' <span class="text-danger">*</span>' : '') . "</label>";
                $html .= "<select id=\"{$fieldid}\" name=\"{$fieldid}\" class=\"form-control\" {$required} {$disabled}>";
                $html .= "<option value=\"\">Please select...</option>";
                if (isset($field['options'])) {
                    foreach ($field['options'] as $option) {
                        $html .= "<option value=\"" . htmlspecialchars($option) . "\">" . htmlspecialchars($option) . "</option>";
                    }
                }
                $html .= "</select>";
                break;

            case 'checkbox':
                $html .= "<div class=\"form-check\">";
                $html .= "<input type=\"checkbox\" id=\"{$fieldid}\" name=\"{$fieldid}\" class=\"form-check-input\" value=\"1\" {$required} {$disabled}>";
                $html .= "<label for=\"{$fieldid}\" class=\"form-check-label\">{$label}" . ($required ? ' <span class="text-danger">*</span>' : '') . "</label>";
                $html .= "</div>";
                break;

            case 'radio':
                $html .= "<fieldset><legend class=\"form-label\">{$label}" . ($required ? ' <span class="text-danger">*</span>' : '') . "</legend>";
                if (isset($field['options'])) {
                    foreach ($field['options'] as $index => $option) {
                        $optionid = "{$fieldid}_option_{$index}";
                        $html .= "<div class=\"form-check\">";
                        $html .= "<input type=\"radio\" id=\"{$optionid}\" name=\"{$fieldid}\" class=\"form-check-input\" value=\"" . htmlspecialchars($option) . "\" {$required} {$disabled}>";
                        $html .= "<label for=\"{$optionid}\" class=\"form-check-label\">" . htmlspecialchars($option) . "</label>";
                        $html .= "</div>";
                    }
                }
                $html .= "</fieldset>";
                break;

            case 'file':
                $html .= "<label for=\"{$fieldid}\" class=\"form-label\">{$label}" . ($required ? ' <span class="text-danger">*</span>' : '') . "</label>";
                $html .= "<input type=\"file\" id=\"{$fieldid}\" name=\"{$fieldid}\" class=\"form-control\" {$required} {$disabled}>";
                break;

            case 'heading':
                $html .= "<h3>{$label}</h3>";
                break;

            case 'paragraph':
                $html .= "<p>{$label}</p>";
                break;

            case 'pagebreak':
                $html .= "<hr class=\"page-break my-4\">";
                break;

            default:
                $html .= "<p>Unknown field type: {$field['type']}</p>";
        }

        if ($helptext && $field['type'] !== 'heading' && $field['type'] !== 'paragraph' && $field['type'] !== 'pagebreak') {
            $html .= "<div class=\"form-text text-muted\">{$helptext}</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Validate form data
     */
    public static function validate_form_data($form, $data) {
        $errors = [];
        $formdata = json_decode($form->formdata, true);
        
        if (!$formdata || !isset($formdata['fields'])) {
            return $errors;
        }

        foreach ($formdata['fields'] as $field) {
            $fieldid = $field['id'];
            $value = $data[$fieldid] ?? '';
            
            // Check required fields
            if (($field['required'] ?? false) && empty($value)) {
                $errors[$fieldid] = get_string('error_fieldrequired', 'local_formbuilder');
            }
            
            // Validate email fields
            if ($field['type'] === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$fieldid] = 'Please enter a valid email address';
            }
            
            // Validate number fields
            if ($field['type'] === 'number' && !empty($value) && !is_numeric($value)) {
                $errors[$fieldid] = 'Please enter a valid number';
            }
        }

        return $errors;
    }
}