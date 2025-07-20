<?php
/**
 * Template Name: Dashboard
 */
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

$first_name = $current_user->first_name;
$last_name = $current_user->last_name;
$email = $current_user->user_email;
$username = $current_user->user_login;

global $wpdb;
$table = $wpdb->prefix . 'userinformation';
$extra = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id), ARRAY_A);


$dob = $extra['dob'] ?? '';
$address = $extra['address'] ?? '';
$region = $extra['region'] ?? '';
$phone = $extra['phone'] ?? '';
$street_address = $extra['street_address'] ?? '';
$zip_code = $extra['zip_code'] ?? '';


$profile_img = get_user_meta($user_id, 'profile_picture', true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_first = sanitize_text_field($_POST['first_name']);
    $new_last = sanitize_text_field($_POST['last_name']);
    $new_dob = sanitize_text_field($_POST['dob']);
    $new_email = sanitize_email($_POST['email']);
    $new_phone = sanitize_text_field($_POST['phone']);
    $new_address = sanitize_text_field($_POST['address']);
    $new_region = sanitize_text_field($_POST['region']);
    $new_street = sanitize_text_field($_POST['street_address']);
    $new_zip = sanitize_text_field($_POST['zip_code']);

    wp_update_user([
        'ID' => $user_id,
        'user_email' => $new_email,
        'first_name' => $new_first,
        'last_name' => $new_last
    ]);

    $data = [
        'dob' => $new_dob,
        'phone' => $new_phone,
        'address' => $new_address,
        'region' => $new_region,
        'street_address' => $new_street,
        'zip_code' => $new_zip
    ];

    if ($extra) {
        $wpdb->update($table, $data, ['user_id' => $user_id]);
    } else {
        $data['user_id'] = $user_id;
        $wpdb->insert($table, $data);
    }

    echo '<div style="color:green;padding:15px 0;">Profile updated successfully.</div>';
}
?>



<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <style>
    body {
        font-family: Arial;
        background: #2d2d2d;
        color: white;
        padding: 40px;
    }

    .container {
        max-width: 700px;
        margin: auto;
        background: #3a3a3a;
        padding: 30px;
        border-radius: 8px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
    }

    input,
    select {
        width: 100%;
        padding: 10px;
        border-radius: 4px;
        border: none;
        font-size: 16px;
    }

    input[type="submit"] {
        background: #e74c3c;
        color: white;
        cursor: pointer;
        font-weight: bold;
    }

    input[type="submit"]:hover {
        background: #c0392b;
    }

    img.profile-pic {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 15px;
        background-color: #666;
    }
    </style>
    <?php wp_enqueue_script('jquery'); ?>
</head>

<body>
    <div class="container">
        <h2>Welcome, <?= esc_html($first_name ?: $username) ?> 👋</h2>
        <form method="POST" enctype="multipart/form-data">
            <img id="profileImage" src="<?= esc_url($profile_img ?: '') ?>" class="profile-pic" alt="Profile Picture">
            <div class="form-group">
                <label for="profile_image">Change Profile Picture</label>
                <input type="file" id="fileInput" name="profile_image" accept="image/*">
            </div>

            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?= esc_attr($first_name) ?>">
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?= esc_attr($last_name) ?>">
            </div>

            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="dob" value="<?= esc_attr($dob) ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= esc_attr($email) ?>">
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= esc_attr($phone) ?>">
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" value="<?= esc_attr($address) ?>">
            </div>

            <div class="form-group">
                <label>Region</label>
                <input type="text" name="region" value="<?= esc_attr($region) ?>">
            </div>

            <div class="form-group">
                <label>Street Address</label>
                <input type="text" name="street_address" value="<?= esc_attr($street_address) ?>">
            </div>

            <div class="form-group">
                <label>ZIP Code</label>
                <input type="text" name="zip_code" value="<?= esc_attr($zip_code) ?>">
            </div>

            <input type="submit" name="update_profile" value="Save Profile">
        </form>
    </div>

    <script>
    document.getElementById('fileInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file || !file.type.startsWith('image/')) return;

        const formData = new FormData();
        formData.append('action', 'upload_profile_image');
        formData.append('profile_image', file);
        formData.append('_wpnonce', '<?php echo wp_create_nonce('profile_image_nonce'); ?>');

        const profileImg = document.getElementById('profileImage');
        profileImg.style.opacity = 0.5;

        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                profileImg.style.opacity = 1;
                if (data.success && data.url) {
                    profileImg.src = data.url + '?t=' + new Date().getTime();
                } else {
                    alert(data.message || 'Upload failed.');
                }
            })
            .catch(err => {
                profileImg.style.opacity = 1;
                alert('Upload failed. Check console.');
                console.error(err);
            });
    });
    </script>



</body>

</html>