<?php
// Redirect to working simple version for demonstration
header('Location: index_simple.php');
exit;

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
