<?php
session_start();
require_once 'database.php';

// Load forms from database
try {
    $db = new FormBuilderDB();
    $forms = $db->getAllForms();
} catch (Exception $e) {
    // Fallback to sample data if database is not available
    $forms = [
        (object)[
            'id' => 1,
            'name' => 'Contact Form',
            'description' => 'Basic contact form for inquiries',
            'timecreated' => time() - 172800,
            'timemodified' => time() - 86400,
            'active' => 1
        ],
        (object)[
            'id' => 2,
            'name' => 'Survey Form', 
            'description' => 'Customer satisfaction survey with grid fields',
            'timecreated' => time() - 259200,
            'timemodified' => time() - 172800,
            'active' => 1
        ],
        (object)[
            'id' => 3,
            'name' => 'Registration Form',
            'description' => 'Event registration with calculations',
            'timecreated' => time() - 345600,
            'timemodified' => time() - 259200,
            'active' => 1
        ]
    ];
}

$successMessage = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $successMessage = '<div class="alert alert-success">Form saved successfully!</div>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Builder - Moodle Plugin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="local-formbuilder-list">
            <?php echo $successMessage; ?>
            
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa fa-clipboard-list"></i> Form Builder</h2>
                <a href="builder.php" class="btn btn-primary"><i class="fa fa-plus"></i> Create Form</a>
            </div>

            <div class="forms-grid row">
                <?php foreach ($forms as $form): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card form-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($form->name); ?></h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="builder.php?id=<?php echo $form->id; ?>"><i class="fa fa-edit"></i> Edit</a></li>
                                        <li><a class="dropdown-item" href="demo.html"><i class="fa fa-eye"></i> View Demo</a></li>
                                        <li><a class="dropdown-item" href="#"><i class="fa fa-inbox"></i> Submissions</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="return confirm('Are you sure?')"><i class="fa fa-trash"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($form->description)): ?>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($form->description); ?></p>
                                <?php endif; ?>
                                <div class="form-meta small text-muted">
                                    <div>Created: <?php echo date('Y-m-d H:i:s', $form->timecreated); ?></div>
                                    <div>Modified: <?php echo date('Y-m-d H:i:s', $form->timemodified); ?></div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <span class="badge <?php echo $form->active ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $form->active ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <style>
    .local-formbuilder-list { padding: 20px; }
    .page-header { border-bottom: 1px solid #e9ecef; padding-bottom: 15px; margin-bottom: 30px; }
    .form-card { transition: transform 0.2s, box-shadow 0.2s; }
    .form-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .forms-grid .card { border-radius: 8px; }
    .form-meta div { margin-bottom: 2px; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

echo $OUTPUT->header();

// Display page header
echo '<div class="local-formbuilder-list">';
echo '<div class="page-header d-flex justify-content-between align-items-center mb-4">';
echo '<h2>' . get_string('formbuilder', 'local_formbuilder') . '</h2>';
if ($cancreate) {
    echo '<a href="builder.php" class="btn btn-primary"><i class="fa fa-plus"></i> ' . get_string('createform', 'local_formbuilder') . '</a>';
}
echo '</div>';

if (empty($forms)) {
    echo '<div class="empty-state text-center py-5">';
    echo '<div class="empty-state-content">';
    echo '<i class="fa fa-clipboard-list fa-3x text-muted mb-3"></i>';
    echo '<h3>' . get_string('noforms', 'local_formbuilder') . '</h3>';
    if ($cancreate) {
        echo '<p>Get started by creating your first form.</p>';
        echo '<a href="builder.php" class="btn btn-primary btn-lg"><i class="fa fa-plus"></i> ' . get_string('createform', 'local_formbuilder') . '</a>';
    }
    echo '</div>';
    echo '</div>';
} else {
    echo '<div class="forms-grid row">';
    foreach ($forms as $form) {
        echo '<div class="col-md-6 col-lg-4 mb-4">';
        echo '<div class="card form-card h-100">';
        echo '<div class="card-header d-flex justify-content-between align-items-start">';
        echo '<h5 class="card-title mb-0">' . format_text($form->name) . '</h5>';
        echo '<div class="dropdown">';
        echo '<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">';
        echo '<i class="fa fa-ellipsis-v"></i>';
        echo '</button>';
        echo '<ul class="dropdown-menu">';
        echo '<li><a class="dropdown-item" href="builder.php?id=' . $form->id . '"><i class="fa fa-edit"></i> Edit</a></li>';
        echo '<li><a class="dropdown-item" href="view.php?id=' . $form->id . '"><i class="fa fa-eye"></i> View</a></li>';
        echo '<li><a class="dropdown-item" href="manage.php?id=' . $form->id . '&action=submissions"><i class="fa fa-inbox"></i> Submissions</a></li>';
        if ($canmanage) {
            echo '<li><hr class="dropdown-divider"></li>';
            echo '<li><a class="dropdown-item text-danger" href="manage.php?id=' . $form->id . '&action=delete" onclick="return confirm(\'Are you sure?\')"><i class="fa fa-trash"></i> Delete</a></li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</div>';
        echo '<div class="card-body">';
        if (!empty($form->description)) {
            echo '<p class="card-text text-muted">' . format_text($form->description) . '</p>';
        }
        echo '<div class="form-meta small text-muted">';
        echo '<div>Created: ' . userdate($form->timecreated) . '</div>';
        echo '<div>Modified: ' . userdate($form->timemodified) . '</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="card-footer">';
        echo '<span class="badge ' . ($form->active ? 'bg-success' : 'bg-secondary') . '">';
        echo $form->active ? 'Active' : 'Inactive';
        echo '</span>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}

echo '</div>';

// Add CSS
echo '<style>
.local-formbuilder-list { padding: 20px; }
.page-header { border-bottom: 1px solid #e9ecef; padding-bottom: 15px; margin-bottom: 30px; }
.form-card { transition: transform 0.2s, box-shadow 0.2s; }
.form-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.empty-state-content { max-width: 400px; margin: 0 auto; }
.forms-grid .card { border-radius: 8px; }
.form-meta div { margin-bottom: 2px; }
</style>';

// Add FontAwesome
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">';

echo $OUTPUT->footer();
