<?php
$pageTitle = 'Company Profile';
$currentPage = 'profile';
$portalType = 'company';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
require_role(ROLE_COMPANY);

$userId = current_user_id();
$company = get_company_by_user_id($mysqli, $userId);

if (!$company) {
    die('Company profile not found.');
}

$action = $_GET['action'] ?? 'edit';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['_action'] ?? '') !== 'upload_logo')) {
    require_valid_csrf();

    $companyName = trim($_POST['company_name'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validate
    if (!$companyName || !$district || !$province) {
        set_flash('error', 'Company name, district, and province are required.');
    } else {
        $stmt = $mysqli->prepare(
            'UPDATE companies 
             SET company_name = ?, industry = ?, address = ?, district = ?, province = ?, 
                 website = ?, phone = ?, description = ?, updated_at = NOW()
             WHERE id = ?'
        );

        $stmt->bind_param(
            'ssssssssi',
            $companyName, $industry, $address, $district, $province,
            $website, $phone, $description, $company['id']
        );

        if ($stmt->execute()) {
            $stmt->close();
            set_flash('success', 'Company profile updated successfully.');
            redirect(app_url('company/profile.php'));
        } else {
            set_flash('error', 'Failed to update profile.');
        }
    }
}

// Handle logo upload
if (isset($_POST['_action']) && $_POST['_action'] === 'upload_logo') {
    require_valid_csrf();

    if (empty($_FILES['logo']['name'])) {
        set_flash('error', 'Please select a logo file.');
    } else {
        $result = handle_file_upload(
            $_FILES['logo'],
            UPLOAD_LOGO_PATH,
            ALLOWED_LOGO_TYPES,
            MAX_LOGO_SIZE,
            'logo_' . $company['id']
        );

        if ($result['success']) {
            if ($company['logo']) {
                delete_uploaded_file(UPLOAD_LOGO_PATH, $company['logo']);
            }

            $stmt = $mysqli->prepare('UPDATE companies SET logo = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('si', $result['path'], $company['id']);
                $stmt->execute();
                $stmt->close();
                set_flash('success', 'Logo updated successfully.');
            } else {
                set_flash('error', 'Failed to save logo to profile.');
            }
        } else {
            set_flash('error', $result['error']);
        }

        redirect(app_url('company/profile.php'));
    }
}

// Handle logo delete
if (isset($_GET['delete_logo'])) {
    if ($company['logo']) {
        delete_uploaded_file(UPLOAD_LOGO_PATH, $company['logo']);
    }

    $stmt = $mysqli->prepare('UPDATE companies SET logo = NULL WHERE id = ?');
    $stmt->bind_param('i', $company['id']);
    $stmt->execute();
    $stmt->close();

    set_flash('success', 'Logo deleted.');
    redirect(app_url('company/profile.php'));
}

// Refresh company data
$company = get_company_by_user_id($mysqli, $userId);
?>

<?php require_once dirname(__DIR__) . '/includes/portal-layout.php'; ?>

<div>
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Company Profile</h2>
            <p class="text-muted">Manage your company information</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <?php if ($company['logo']): ?>
                        <img src="<?= e(app_url('uploads/logos/' . $company['logo'])) ?>" alt="Logo" style="max-width: 150px; max-height: 150px; object-fit: contain;" class="mb-3">
                    <?php else: ?>
                        <div class="bg-secondary-subtle d-inline-flex align-items-center justify-content-center mb-3" style="width: 150px; height: 150px;">
                            <i class="bi bi-building" style="font-size: 3rem;"></i>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="mt-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_action" value="upload_logo">
                        <div class="mb-2">
                            <input type="file" class="form-control form-control-sm" name="logo" accept="image/jpeg,image/png,image/webp,image/gif" required>
                            <small class="form-text text-muted">JPG, PNG, GIF (Max 2MB)</small>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Upload Logo</button>
                    </form>

                    <?php if ($company['logo']): ?>
                        <a href="?delete_logo=1" class="btn btn-sm btn-outline-danger w-100 mt-2" onclick="return confirm('Delete logo?')">Delete Logo</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <form method="POST" class="card border-0 shadow-sm">
                <?= csrf_field() ?>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Name *</label>
                            <input type="text" class="form-control" name="company_name" value="<?= e($company['company_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Industry</label>
                            <input type="text" class="form-control" name="industry" value="<?= e($company['industry'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" value="<?= e($company['address'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">District *</label>
                            <select class="form-select" name="district" required>
                                <option value="">Select District</option>
                                <?php foreach (DISTRICTS as $dist): ?>
                                    <option value="<?= e($dist) ?>" <?= ($company['district'] === $dist) ? 'selected' : '' ?>>
                                        <?= e($dist) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Province *</label>
                            <select class="form-select" name="province" required>
                                <option value="">Select Province</option>
                                <?php foreach (PROVINCES as $prov): ?>
                                    <option value="<?= e($prov) ?>" <?= ($company['province'] === $prov) ? 'selected' : '' ?>>
                                        <?= e($prov) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="url" class="form-control" name="website" value="<?= e($company['website'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" value="<?= e($company['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="5"><?= e($company['description'] ?? '') ?></textarea>
                        <small class="form-text text-muted">Tell students about your company</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/portal-layout-end.php'; ?>
