<?php
session_start();

// Sample forms for demonstration
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
                <a href="builder_simple.php" class="btn btn-primary"><i class="fa fa-plus"></i> Create Form</a>
            </div>

            <?php if (empty($forms)): ?>
                <div class="empty-state text-center py-5">
                    <div class="empty-state-content">
                        <i class="fa fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h3>No Forms Found</h3>
                        <p>Get started by creating your first form.</p>
                        <a href="builder_simple.php" class="btn btn-primary btn-lg"><i class="fa fa-plus"></i> Create Form</a>
                    </div>
                </div>
            <?php else: ?>
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
                                            <li><a class="dropdown-item" href="builder_simple.php?id=<?php echo $form->id; ?>"><i class="fa fa-edit"></i> Edit</a></li>
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
            <?php endif; ?>
        </div>
    </div>

    <style>
    .local-formbuilder-list { padding: 20px; }
    .page-header { border-bottom: 1px solid #e9ecef; padding-bottom: 15px; margin-bottom: 30px; }
    .form-card { transition: transform 0.2s, box-shadow 0.2s; }
    .form-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .empty-state-content { max-width: 400px; margin: 0 auto; }
    .forms-grid .card { border-radius: 8px; }
    .form-meta div { margin-bottom: 2px; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>