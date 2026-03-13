<?php
$page_title = 'About — TransactiWar';

/**
 * Team member data.
 * Update this array with your group members' details.
 * All output is escaped with e() to prevent XSS.
 */
$team_members = [
    ['name' => 'Member 1 Name', 'roll' => 'CS00X0000'],
    ['name' => 'Member 2 Name', 'roll' => 'CS00X0000'],
    ['name' => 'Member 3 Name', 'roll' => 'CS00X0000'],
    ['name' => 'Member 4 Name', 'roll' => 'CS00X0000'],
    ['name' => 'Member 5 Name', 'roll' => 'CS00X0000'],
];

include __DIR__ . '/../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="tw-card text-center mb-4">
            <h3 class="mb-1"><i class="bi bi-shield-lock-fill me-2"></i>TransactiWar</h3>
            <p class="text-muted mb-0" style="font-size: 0.9rem;">
                Battle for Security, Compete for Supremacy
            </p>
        </div>

        <div class="tw-card">
            <div class="tw-card-header">
                <i class="bi bi-info-circle me-1"></i>About
            </div>
            <p style="font-size: 0.875rem; line-height: 1.6;">
                TransactiWar is a secure web application built for
                <strong>CS6903 Network Security — Phase 1</strong> at IIT Hyderabad.
                It implements user registration, profile management, and money transfers
                with custom-built security at every layer — no external security frameworks used.
            </p>
        </div>

        <div class="tw-card">
            <div class="tw-card-header">
                <i class="bi bi-people me-1"></i>Team Members
            </div>
            <div class="table-responsive">
                <table class="tw-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Roll Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($team_members as $i => $member): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($member['name']) ?></td>
                                <td><code><?= e($member['roll']) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tw-card">
            <div class="tw-card-header">
                <i class="bi bi-stack me-1"></i>Tech Stack
            </div>
            <div class="row g-2 text-center" style="font-size: 0.8rem;">
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded" style="background: var(--tw-card-bg);">
                        <i class="bi bi-filetype-php d-block mb-1" style="font-size: 1.3rem; color: var(--tw-primary);"></i>
                        PHP 8.2
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded" style="background: var(--tw-card-bg);">
                        <i class="bi bi-database d-block mb-1" style="font-size: 1.3rem; color: var(--tw-primary);"></i>
                        MySQL 8.0
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded" style="background: var(--tw-card-bg);">
                        <i class="bi bi-bootstrap d-block mb-1" style="font-size: 1.3rem; color: var(--tw-primary);"></i>
                        Bootstrap 5.3
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded" style="background: var(--tw-card-bg);">
                        <i class="bi bi-box-seam d-block mb-1" style="font-size: 1.3rem; color: var(--tw-primary);"></i>
                        Docker
                    </div>
                </div>
            </div>
        </div>

        <?php if (is_logged_in()): ?>
            <div class="text-center">
                <a href="/dashboard" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        <?php else: ?>
            <div class="text-center">
                <a href="/login" class="btn btn-primary btn-sm me-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Log In
                </a>
                <a href="/register" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-person-plus me-1"></i>Register
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
