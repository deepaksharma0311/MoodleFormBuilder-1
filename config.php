<?php
// Minimal Moodle configuration for demonstration purposes

// Basic configuration
$CFG = new stdClass();
$CFG->dbtype    = 'sqlite3';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'demo';
$CFG->dbuser    = '';
$CFG->dbpass    = '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array();

$CFG->wwwroot   = 'http://localhost:5000';
$CFG->dataroot  = '/tmp/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 0777;
$CFG->libdir = './lib';

// Session configuration
$CFG->cookiename = 'MoodleSession';
$CFG->cookiesecure = false;
$CFG->sessiontimeout = 7200;

// Security
define('MOODLE_INTERNAL', true);

// Global variables
global $DB, $CFG, $PAGE, $OUTPUT, $USER, $SESSION;

// Initialize basic objects
$DB = new stdClass();
$PAGE = new stdClass();
$OUTPUT = new stdClass();
$USER = new stdClass();
$SESSION = new stdClass();

// User object
$USER->id = 1;
$USER->username = 'demo';
$USER->firstname = 'Demo';
$USER->lastname = 'User';
$USER->email = 'demo@example.com';

// Session object
if (!isset($_SESSION)) {
    session_start();
}
foreach ($_SESSION as $key => $value) {
    $SESSION->$key = $value;
}

// Database object implementation
class database_object {
    public function get_record($table, $conditions = null, $fields = '*', $strictness = IGNORE_MISSING) {
        // Return sample form data for demonstration
        if ($table === 'local_formbuilder_forms') {
            $form = new stdClass();
            $form->id = 1;
            $form->name = 'Sample Contact Form';
            $form->description = 'A demonstration contact form with multiple field types';
            $form->formdata = json_encode([
                'fields' => [
                    ['id' => 'field_1', 'type' => 'text', 'label' => 'Full Name', 'required' => true],
                    ['id' => 'field_2', 'type' => 'email', 'label' => 'Email Address', 'required' => true],
                    ['id' => 'field_3', 'type' => 'textarea', 'label' => 'Message', 'required' => false],
                    ['id' => 'field_4', 'type' => 'select', 'label' => 'Subject', 'options' => ['General Inquiry', 'Support', 'Feedback']]
                ]
            ]);
            $form->settings = json_encode(['notifyowner' => true, 'notifysubmitter' => true]);
            $form->userid = 1;
            $form->timecreated = time() - 86400;
            $form->timemodified = time() - 3600;
            $form->active = 1;
            return $form;
        }
        return false;
    }

    public function get_records($table, $conditions = null, $sort = '', $fields = '*', $limitfrom = 0, $limitnum = 0) {
        // Return sample forms for demonstration
        if ($table === 'local_formbuilder_forms') {
            $forms = [];
            
            $form1 = new stdClass();
            $form1->id = 1;
            $form1->name = 'Contact Form';
            $form1->description = 'Basic contact form for inquiries';
            $form1->userid = 1;
            $form1->timecreated = time() - 172800;
            $form1->timemodified = time() - 86400;
            $form1->active = 1;
            $forms[1] = $form1;
            
            $form2 = new stdClass();
            $form2->id = 2;
            $form2->name = 'Survey Form';
            $form2->description = 'Customer satisfaction survey with grid fields';
            $form2->userid = 1;
            $form2->timecreated = time() - 259200;
            $form2->timemodified = time() - 172800;
            $form2->active = 1;
            $forms[2] = $form2;
            
            $form3 = new stdClass();
            $form3->id = 3;
            $form3->name = 'Registration Form';
            $form3->description = 'Event registration with calculations';
            $form3->userid = 1;
            $form3->timecreated = time() - 345600;
            $form3->timemodified = time() - 259200;
            $form3->active = 1;
            $forms[3] = $form3;
            
            return $forms;
        }
        
        if ($table === 'local_formbuilder_submissions') {
            return [];
        }
        
        return [];
    }

    public function insert_record($table, $data, $returnid = true, $bulk = false) {
        return rand(100, 999);
    }

    public function update_record($table, $data, $bulk = false) {
        return true;
    }

    public function delete_records($table, $conditions = null) {
        return true;
    }
}

$DB = new database_object();

// Mock functions
function required_param($paramname, $type) {
    return isset($_GET[$paramname]) ? $_GET[$paramname] : (isset($_POST[$paramname]) ? $_POST[$paramname] : '');
}

function optional_param($paramname, $default, $type) {
    return isset($_GET[$paramname]) ? $_GET[$paramname] : (isset($_POST[$paramname]) ? $_POST[$paramname] : $default);
}

function get_string($identifier, $component = 'moodle', $a = null) {
    $strings = array(
        'formbuilder' => 'Form Builder',
        'createform' => 'Create Form',
        'formname' => 'Form Name',
        'formdescription' => 'Form Description',
        'saveform' => 'Save Form',
        'submitform' => 'Submit Form',
        'formsubmitted' => 'Form Submitted Successfully',
        'thankyou' => 'Thank You',
        'emailnotifications' => 'Email Notifications',
        'notifyowner' => 'Notify Owner',
        'notifysubmitter' => 'Notify Submitter',
        'redirecturl' => 'Redirect URL',
        'custommessage' => 'Custom Message',
        'multipageform' => 'Multi-page Form',
        'page' => 'Page',
        'of' => 'of',
        'previouspage' => 'Previous',
        'nextpage' => 'Next'
    );
    return isset($strings[$identifier]) ? $strings[$identifier] : $identifier;
}

function format_text($text, $format = FORMAT_HTML, $options = null) {
    return htmlspecialchars($text);
}

function has_capability($capability, $context, $user = null) {
    return true;
}

function require_login($courseorid = null, $autologinguest = true, $cm = null, $setwantsurltome = true, $preventredirect = false) {
    return true;
}

function require_capability($capability, $context, $userid = null, $doanything = true, $errormessage = 'nopermissions', $stringfile = '') {
    return true;
}

function sesskey() {
    return md5(session_id() . time());
}

function redirect($url, $message = '', $delay = null, $messagetype = null) {
    if (is_object($url)) {
        $url = $url->out();
    }
    header("Location: $url");
    exit;
}

function userdate($date, $format = '', $timezone = 99, $fixday = true, $fixhour = true) {
    return date('Y-m-d H:i:s', $date);
}

function fullname($user, $override = false) {
    return $user->firstname . ' ' . $user->lastname;
}

function getremoteaddr($default = '0.0.0.0') {
    return $_SERVER['REMOTE_ADDR'] ?? $default;
}

function email_to_user($user, $from, $subject, $messagetext, $messagehtml = '', $attachment = '', $attachname = '', $usetrueaddress = true, $replyto = '', $replytoname = '', $wordwrapwidth = 79) {
    return true;
}

// Context classes
class context_system {
    public static function instance() {
        return new self();
    }
}

class moodle_url {
    private $url;
    private $params;
    
    public function __construct($url, $params = null) {
        $this->url = $url;
        $this->params = $params ?: array();
    }
    
    public function out($escaped = true, $params = null) {
        $url = $this->url;
        if (!empty($this->params)) {
            $url .= '?' . http_build_query($this->params);
        }
        return $url;
    }
}

// Constants
define('PARAM_INT', 'int');
define('PARAM_TEXT', 'text');
define('PARAM_RAW', 'raw');
define('PARAM_URL', 'url');
define('PARAM_EMAIL', 'email');
define('PARAM_FLOAT', 'float');
define('PARAM_ALPHA', 'alpha');
define('PARAM_ALPHANUMEXT', 'alphanumext');
define('MUST_EXIST', 1);
define('IGNORE_MISSING', 0);
define('FORMAT_HTML', 1);
define('CONTEXT_SYSTEM', 10);

// PAGE object implementation
class page_object {
    public $requires;
    
    public function __construct() {
        $this->requires = new stdClass();
        $this->requires->jquery = function() {};
        $this->requires->jquery_plugin = function($plugin) {};
        $this->requires->js_call_amd = function($module, $function) {};
        $this->requires->js_init_code = function($code) {};
    }
    
    public function set_context($context) {}
    public function set_url($url, $params = null) {}
    public function set_title($title) {}
    public function set_heading($heading) {}
    public function set_pagelayout($layout) {}
    
    public function get_renderer($component) {
        return new stdClass();
    }
}

$PAGE = new page_object();

// OUTPUT object implementation
class output_object {
    public function header() {
        return '<!DOCTYPE html><html><head><title>Form Builder Demo</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body><div class="container mt-4">';
    }

    public function footer() {
        return '</div></body></html>';
    }

    public function notification($message, $type = null) {
        $class = $type === 'notifysuccess' ? 'alert-success' : 'alert-info';
        return '<div class="alert ' . $class . '">' . $message . '</div>';
    }

    public function box($content, $classes = null) {
        return '<div class="' . $classes . '">' . $content . '</div>';
    }

    public function heading($text, $level = 2, $classes = null) {
        return '<h' . $level . ' class="' . $classes . '">' . $text . '</h' . $level . '>';
    }

    public function continue_button($url) {
        return '<a href="' . $url->out() . '" class="btn btn-primary">Continue</a>';
    }

    public function single_button($url, $label, $method = 'post', $options = null) {
        return '<a href="' . $url->out() . '" class="btn btn-secondary">' . $label . '</a>';
    }
}

$OUTPUT = new output_object();

// Include the formslib
require_once($CFG->libdir . '/formslib.php');

// HTML writer
class html_writer {
    public static function link($url, $text, $attributes = null) {
        $attrs = '';
        if ($attributes) {
            foreach ($attributes as $key => $value) {
                $attrs .= ' ' . $key . '="' . $value . '"';
            }
        }
        return '<a href="' . $url . '"' . $attrs . '>' . $text . '</a>';
    }
    
    public static function table($table) {
        $html = '<table class="' . $table->attributes['class'] . '">';
        
        // Header
        if (!empty($table->head)) {
            $html .= '<thead><tr>';
            foreach ($table->head as $header) {
                $html .= '<th>' . $header . '</th>';
            }
            $html .= '</tr></thead>';
        }
        
        // Body
        if (!empty($table->data)) {
            $html .= '<tbody>';
            foreach ($table->data as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . $cell . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody>';
        }
        
        $html .= '</table>';
        return $html;
    }
    
    public static function tag($tag, $content, $attributes = null) {
        return '<' . $tag . '>' . $content . '</' . $tag . '>';
    }
}

// HTML table class
class html_table {
    public $head = array();
    public $data = array();
    public $attributes = array();
}

// Core user class
class core_user {
    public static function get_noreply_user() {
        $user = new stdClass();
        $user->email = 'noreply@example.com';
        $user->firstname = 'No';
        $user->lastname = 'Reply';
        return $user;
    }
}