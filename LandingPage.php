<?php
session_start();
include 'configuration.php';

$error = '';
$success = '';

if (isset($_GET['registration_success']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $show_success_modal = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = trim($_POST['loginEmail']);
        $password = trim($_POST['loginPassword']);

        if (!empty($email) && !empty($password)) {
            // Check for admin credentials first
            if ($email == 'admin@gmail.com' && $password == '123') {
                $_SESSION['admin_id'] = 1;
                $_SESSION['logged_in'] = true;
                header('Location: AdminDashboard.php');
                exit;
            }

            // Check tenant credentials
            $stmt = $conn->prepare("SELECT * FROM tenants WHERE email = ? OR username = ? LIMIT 1");
            $stmt->bind_param('ss', $email, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password_hash'])) {
                    $_SESSION['tenant_id'] = $row['tenant_id'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['first_name'] = $row['first_name'];
                    $_SESSION['last_name'] = $row['last_name'];
                    header('Location: TenantDashboard.php');
                    exit;
                } else {
                    $error = 'Invalid password.';
                    $show_error_modal = true;
                }
            } else {
                $error = 'Invalid credentials.';
                $show_error_modal = true;
            }
            $stmt->close();
        } else {
            $error = 'Please fill in all fields.';
            $show_error_modal = true;
        }
    } elseif (isset($_POST['register'])) {
        $last_name = trim($_POST['lastName']);
        $first_name = trim($_POST['firstName']);
        $middle_name = trim($_POST['middleName'] ?? '');
        $province = trim($_POST['province']);
        $municipality = trim($_POST['municipality']);
        $barangay = trim($_POST['barangay']);
        $purok_street = trim($_POST['houseStreet'] ?? '');
        $contact = trim($_POST['contact']);
        $email = trim($_POST['email']);
        $sex = trim($_POST['sex']);
        $occupation = trim($_POST['occupation']);
        $extension_name = trim($_POST['extensionName'] ?? '');
        $emergency_last_name = trim($_POST['emergencyLastName']);
        $emergency_first_name = trim($_POST['emergencyFirstName']);
        $emergency_middle_name = trim($_POST['emergencyMiddleName'] ?? '');
        $emergency_extension_name = trim($_POST['emergencyExtensionName'] ?? '');
        $emergency_province = trim($_POST['emergencyProvince']);
        $emergency_municipality = trim($_POST['emergencyCity']);
        $emergency_barangay = trim($_POST['emergencyBarangay']);
        $emergency_purok_street = trim($_POST['emergencyStreet']);
        $emergency_contact = trim($_POST['emergencyContactNumber']);
        $relationship = trim($_POST['relationship']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirmPassword']);
        $agreed_terms = isset($_POST['agreeCheckbox']) ? 1 : 0;

        if (empty($last_name) || empty($first_name) || empty($province) || empty($municipality) || empty($barangay) || empty($contact) || empty($email) || empty($sex) || empty($occupation) || empty($emergency_last_name) || empty($emergency_first_name) || empty($emergency_province) || empty($emergency_municipality) || empty($emergency_barangay) || empty($emergency_contact) || empty($relationship) || empty($username) || empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all required fields.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (!$agreed_terms) {
            $error = 'You must agree to the terms and conditions.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one number.';
        } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $error = 'Password must contain at least one special character.';
        } else {
            $check_stmt = $conn->prepare("SELECT tenant_id FROM tenants WHERE email = ? OR username = ?");
            $check_stmt->bind_param("ss", $email, $username);
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows > 0) {
                $error = 'Email or username already exists.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO tenants (last_name, first_name, middle_name, extension_name, province, municipality, barangay, purok_street, contact, email, sex, occupation, emergency_last_name, emergency_first_name, emergency_middle_name, emergency_extension_name, emergency_province, emergency_municipality, emergency_barangay, emergency_purok_street, emergency_contact, relationship, username, password_hash, agreed_terms) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssssssssssssssssssi", $last_name, $first_name, $middle_name, $extension_name, $province, $municipality, $barangay, $purok_street, $contact, $email, $sex, $occupation, $emergency_last_name, $emergency_first_name, $emergency_middle_name, $emergency_extension_name, $emergency_province, $emergency_municipality, $emergency_barangay, $emergency_purok_street, $emergency_contact, $relationship, $username, $password_hash, $agreed_terms);
                if ($stmt->execute()) {
                    header('Location: LandingPage.php?registration_success=1');
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
    }
}

$toast_message = "";
$contact_success = false;
$contact_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact'])) {
    $name    = trim($_POST['contact_name'] ?? '');
    $email   = trim($_POST['contact_email'] ?? '');
    $message = trim($_POST['contact_message'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message) && !empty($contact)) {
        if (!preg_match('/^[0-9]{10}$/', $contact)) {
            $contact_error = "Invalid contact number. Please enter exactly 10 digits.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $contact_error = "Invalid email address format.";
        } else {
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message, contact_number, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("ssss", $name, $email, $message, $contact);
                if ($stmt->execute()) {
                    $contact_success = true;
                } else {
                    $contact_error = "Error sending message. Please try again.";
                }
                $stmt->close();
            } else {
                $contact_error = "Database error. Please try again.";
            }
        }
    } else {
        $contact_error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AmourStay Dormitel | Professional Living</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.29.1/dist/feather.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-M9kHT0n6l5d+B0c2tVcklA5G5+VYhbU7qW0U1xkEN4YPPv+I85y+G4hJ51N0jO+SbWjVp/4BQy5q3jk9V+T5EA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        :root {
            --primary-orange: #E67E22;
            --burnt-orange:   #D35400;
            --soft-orange:    #FFF8F2;
            --dark-gray:      #2D3436;
            --bg-dark:        #1a1513;
            --accent-orange:  #FF7F50;
            --text-light:     #f4f4f4;
            --text-gray:      #b0b0b0;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html  { scroll-behavior: smooth; }
        body  {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark-gray);
            background-color: rgba(255,165,80,0.08);
            overflow-x: hidden;
        }

        /* ══ NAVBAR ══ */
        .navbar {
            padding: 1.2rem 0;
            transition: all 0.4s ease;
            background: transparent;
        }
        .navbar.scrolled {
            padding: 0.8rem 0;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .navbar-brand {
            color: #fff !important;
            transition: color 0.4s ease;
            font-size: 1.1rem;
        }
        .navbar.scrolled .navbar-brand { color: var(--dark-gray) !important; }

        /* Hamburger icon — always black */
        .navbar-toggler { border: none !important; outline: none !important; box-shadow: none !important; padding: 4px 8px; }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0,0,0,1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Desktop nav links */
        .nav-link {
            position: relative;
            font-weight: 500;
            color: #fff !important;
            transition: color 0.3s;
            padding-bottom: 4px;
        }
        .nav-link::after {
            content: "";
            position: absolute;
            left: 50%; bottom: -4px;
            width: 0; height: 2px;
            background: var(--primary-orange);
            transition: all 0.3s ease;
            transform: translateX(-50%);
            border-radius: 2px;
        }
        .nav-link:hover::after, .nav-link.active::after { width: 100%; }
        .nav-link:hover { color: var(--primary-orange) !important; }
        .navbar.scrolled .nav-link       { color: var(--dark-gray) !important; }
        .navbar.scrolled .nav-link:hover { color: var(--primary-orange) !important; }

        /* Auth buttons */
        .btn-signup {
            background: var(--primary-orange); color: white !important;
            border-radius: 50px; border: none; font-weight: 500;
            padding: 8px 20px; transition: background 0.3s;
        }
        .btn-signup:hover { background: var(--burnt-orange); }
        .btn-signin {
            color: white !important; border: 1.5px solid white;
            border-radius: 50px; font-weight: 500;
            padding: 8px 22px; transition: all 0.3s; background: transparent;
        }
        .navbar.scrolled .btn-signin { color: var(--primary-orange) !important; border-color: var(--primary-orange); }
        .btn-signin:hover { background: rgba(255,255,255,0.15); }
        .navbar.scrolled .btn-signin:hover { background: rgba(230,126,34,0.08); }

        /* Mobile collapsed menu */
        @media (max-width: 991px) {
            .navbar-collapse {
                background: #111 !important;
                padding: 1rem 1.5rem 1.5rem;
                border-radius: 0 0 1rem 1rem;
                margin-top: 0.5rem;
                box-shadow: 0 12px 30px rgba(0,0,0,0.4);
            }
            .navbar-collapse .nav-link {
                color: #fff !important;
                padding: 10px 0;
                border-bottom: 1px solid rgba(255,255,255,0.08);
            }
            .navbar-collapse .nav-link:last-child { border-bottom: none; }
            .navbar-collapse .nav-link:hover { color: var(--primary-orange) !important; }
            .navbar-collapse .nav-link::after { display: none; }
            .navbar-collapse .d-flex {
                flex-direction: column;
                gap: 0.6rem !important;
                margin-top: 1rem;
            }
            .navbar-collapse .btn-signin {
                color: #fff !important; border-color: #fff;
                width: 100%; text-align: center;
            }
            .navbar-collapse .btn-signin:hover { background: rgba(255,255,255,0.1); }
            .navbar-collapse .btn-signup { width: 100%; text-align: center; }
        }
        @media (max-width: 576px) {
            .navbar-brand { font-size: 0.95rem; }
            .navbar-brand img { height: 28px; }
        }

        /* ══ MODAL ══ */
        .modal-backdrop { backdrop-filter: blur(5px); background-color: rgba(0,0,0,0.5); }
        .modal-content  { border-radius: 1rem; overflow: hidden; }
        .modal-header   { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color: white; }
        .modal-body     { background-color: #fff; padding-top: 1rem; padding-bottom: 1rem; }
        .modal-footer   { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .form-control:focus { box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25); border-color: #667eea; }
        .is-invalid     { border-color: #dc3545; box-shadow: 0 0 0 0.2rem rgba(220,53,69,0.25); }
        .btn-gradient {
            background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            border: none; color: #fff; transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg,#5a6fe0 0%,#6b4196 100%);
            box-shadow: 0 6px 15px rgba(102,126,234,0.4);
            transform: translateY(-1px);
        }
        .btn-primary { background-color: var(--primary-orange); border-color: var(--primary-orange); }
        .btn-primary:hover,.btn-primary:focus,.btn-primary:active {
            background-color: var(--burnt-orange) !important;
            border-color: var(--burnt-orange) !important;
        }
        .form-check-input:checked { background-color: var(--primary-orange); border-color: var(--primary-orange); }

        /* ══ STEP INDICATOR ══ */
        .step-indicator .step { text-align: center; }
        .step-circle {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; margin: 0 auto;
            background-color: #6c757d; color: white; transition: all 0.3s ease;
        }
        .step.active .step-circle {
            background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            box-shadow: 0 0 0 4px rgba(12,11,66,0.2); transform: scale(1.1);
        }
        .step.completed .step-circle { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); }
        .step-connector { width: 60px; height: 2px; background-color: #dee2e6; margin: 0 6px; }
        .step-connector.active { background-color: var(--primary-orange); }
        .form-control-sm-radius { border-radius: 0.5rem; }
        @media (max-width: 480px) {
            .step-connector { width: 28px; }
            .step-circle    { width: 32px; height: 32px; font-size: 0.8rem; }
        }

        /* ══ SECTION HEADERS ══ */
        .section-header { text-align: center; margin-bottom: 3rem; }
        .bubble-label {
            background: var(--soft-orange); color: var(--primary-orange);
            padding: 6px 18px; border-radius: 100px; font-weight: 700;
            text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px;
            display: inline-block; margin-bottom: 1rem;
        }

        /* ══ HERO ══ */
        .hero {
            min-height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),
                        url('facilities/landingpage.jpg') center/cover no-repeat;
            display: flex; align-items: center;
            color: white; text-align: center;
            padding: 120px 16px 80px;
        }
        .hero h1 { font-size: clamp(1.8rem, 6vw, 4rem); line-height: 1.15; }
        .hero h3 { font-size: clamp(0.85rem, 2.5vw, 1.2rem); letter-spacing: 2px; }
        .hero p  { font-size: clamp(0.9rem, 2vw, 1.1rem); }
        .hero-divider { width: 70px; height: 3px; background: var(--primary-orange); margin: 16px auto; }
        .hero .btn-group-wrap { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }
        .btn-inquire, .btn-readmore { transition: transform 0.3s ease; }
        .btn-inquire:hover, .btn-readmore:hover { transform: translateY(-2px); }
        @media (max-width: 576px) {
            .hero { padding: 100px 16px 60px; }
            .btn-readmore, .btn-inquire {
                width: 100%; max-width: 280px;
                padding: 12px 20px !important;
            }
        }

        /* ══ SERVICES ══ */
        .service-card {
            border-radius: 18px; transition: all 0.3s ease;
            height: 100%;
        }
        .service-card:hover { transform: translateY(-6px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); }
        .service-icon {
            font-size: 1.9rem; padding: 12px; border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 1.5px solid rgba(211,84,0,.35);
            color: #d35400; background: rgba(211,84,0,.08);
            transition: .3s ease; margin-bottom: 12px;
        }
        .service-card:hover .service-icon { background: #d35400; color: #fff; transform: translateY(-3px) scale(1.05); }

        /* ══ FACILITIES ══ */
        .facility-card {
            position: relative; border-radius: 20px; overflow: hidden;
            aspect-ratio: 4/3; cursor: pointer;
        }
        .facility-card img {
            width: 100%; height: 100%; object-fit: cover; display: block;
            transition: transform 0.5s ease, filter 0.5s ease;
        }
        .facility-card::after {
            content: ""; position: absolute; inset: 0;
            background: rgba(0,0,0,0.35); opacity: 0; transition: opacity 0.4s ease;
        }
        .facility-overlay {
            position: absolute; inset: 0; z-index: 2;
            display: flex; flex-direction: column;
            justify-content: flex-end; align-items: flex-start;
            padding: 20px; color: #fff;
            opacity: 0; transition: opacity 0.4s ease, transform 0.4s ease;
            transform: translateY(10px); text-align: left;
        }
        .facility-card:hover img { transform: scale(1.12); filter: brightness(0.75); }
        .facility-card:hover::after { opacity: 1; }
        .facility-card:hover .facility-overlay { opacity: 1; transform: translateY(0); }
        .facility-overlay h5 { font-weight: 600; margin-bottom: 4px; }
        .facility-overlay p  { font-size: 0.9rem; opacity: 0.9; }

        /* ══ ABOUT ══ */
        .about-image-wrapper {
            position: relative; height: 520px;
            display: flex; align-items: center; justify-content: center;
        }
        .img-back {
            width: 360px; height: 460px; object-fit: cover;
            border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: absolute; left: 8%; z-index: 1;
        }
        .img-front {
            width: 380px; height: 270px; object-fit: cover;
            border-radius: 24px; border: 10px solid white;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            position: absolute; bottom: 40px; right: 3%; z-index: 2;
        }
        .about-card {
            position: relative; background: #f8f9fa; border-radius: 16px;
            padding: 2rem 1.5rem; text-align: center;
            box-shadow: 0 6px 20px rgba(0,0,0,.05);
            transition: transform .3s ease, box-shadow .3s ease;
            height: 100%;
        }
        .about-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,.1); }
        .about-icon {
            font-size: 1.4rem; padding: 10px; border-radius: 10px;
            background: rgba(211,84,0,.08); color: #d35400;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 12px; transition: all .3s ease;
        }
        .about-card:hover .about-icon { background: #d35400; color: #fff; transform: translateY(-2px) scale(1.05); }
        .color-orange { color: #d35400; }

        /* About image — tablet */
        @media (max-width: 991px) {
            .about-image-wrapper {
                height: auto; min-height: 320px;
                display: flex; flex-direction: column;
                align-items: center; padding-bottom: 1rem; margin-top: 2rem;
            }
            .img-back {
                position: relative; width: 85%; max-width: 400px;
                height: auto; left: auto;
            }
            .img-front {
                position: relative; width: 75%; max-width: 350px;
                height: auto; bottom: auto; right: auto;
                margin-top: -80px; margin-left: 40px; border-width: 6px;
            }
        }
        /* About image — mobile */
        @media (max-width: 576px) {
            .about-image-wrapper { min-height: unset; margin-top: 1.5rem; }
            .img-back  { width: 100%; max-width: 100%; }
            .img-front { width: 80%; margin-top: -60px; margin-left: 24px; border-width: 5px; }
        }

        /* ══ CONTACT ══ */
        .map-container {
            width: 100%; height: 300px;
            box-shadow: 0 6px 20px rgba(0,0,0,.05);
            border-radius: 20px; overflow: hidden;
        }
        .info-card {
            background: #f8f9fa; border-radius: 14px; padding: 0.85rem;
            display: flex; align-items: flex-start;
            box-shadow: 0 4px 15px rgba(0,0,0,.05);
            transition: transform .3s ease, box-shadow .3s ease;
            width: 100%; height: auto; min-height: 75px;
        }
        .info-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.08); }
        .info-icon {
            font-size: 1.4rem; padding: 7px; border-radius: 8px; flex-shrink: 0;
            background: rgba(211,84,0,.08); color: #d35400;
            display: inline-flex; align-items: center; justify-content: center;
            transition: all .3s ease;
        }
        .info-card:hover .info-icon { background: #d35400; color: #fff; transform: translateY(-1px) scale(1.05); }
        .info-card h6 { font-size: 0.8rem; margin-bottom: 2px !important; }
        .info-card p  { font-size: 0.72rem; margin: 0; line-height: 1.4; }
        .contact-form {
            background: #fff; border-radius: 24px;
            box-shadow: 0 10px 28px rgba(0,0,0,.08);
            padding: 2rem; width: 100%; height: auto;
        }
        .contact-header { color: #d35400; font-size: 1.4rem; text-align: left; }
        .contact-form input, .contact-form textarea {
            border-radius: 12px; background: rgba(248,249,250,.9);
            transition: all .3s ease; width: 100%;
        }
        .contact-form input:focus, .contact-form textarea:focus {
            outline: none; box-shadow: 0 0 0 2px rgba(211,84,0,.25);
        }
        .contact-btn { border-radius: 12px; background: var(--primary-orange); border: none; transition: all .3s ease; }
        .contact-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(211,84,0,.3); }

        @media (max-width: 767px) {
            .map-container { height: 240px; }
        }

        /* ══ FOOTER ══ */
        footer { background-color: var(--bg-dark); color: var(--text-light); padding: 60px 20px 20px; }
        .footer-container {
            max-width: 1200px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(3,1fr); gap: 40px;
        }
        .footer-brand .brand-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .footer-brand h2  { font-size: 1.4rem; margin: 0; letter-spacing: 1px; }
        .footer-brand p   { color: var(--text-gray); line-height: 1.6; margin-bottom: 25px; font-size: 0.9rem; }
        .social-links     { display: flex; gap: 14px; }
        .social-links a {
            display: flex; align-items: center; justify-content: center;
            width: 38px; height: 38px;
            background: rgba(255,255,255,0.05); color: var(--text-light);
            border-radius: 50%; text-decoration: none; transition: all 0.3s ease;
        }
        .social-links a:hover { background: var(--accent-orange); transform: translateY(-3px); }
        .footer-column h3 { font-size: 1.1rem; margin-bottom: 20px; position: relative; padding-bottom: 10px; }
        .footer-column h3::after { content: ''; position: absolute; left: 0; bottom: 0; width: 28px; height: 2px; background: var(--accent-orange); }
        .footer-column ul { list-style: none; padding: 0; margin: 0; }
        .footer-column ul li { margin-bottom: 10px; }
        .footer-column ul li a { transition: color 0.3s, padding-left 0.2s; }
        .footer-column ul li a:hover { color: #f59e0b !important; padding-left: 4px; }
        .contact-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; color: var(--text-gray); font-size: 0.9rem; }
        .contact-item i { color: var(--accent-orange); margin-top: 3px; flex-shrink: 0; }
        .footer-bottom {
            margin-top: 48px; padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center; color: var(--text-gray); font-size: 0.82rem;
        }
        @media (max-width: 992px) {
            .footer-container { grid-template-columns: repeat(2,1fr); gap: 32px; }
        }
        @media (max-width: 600px) {
            .footer-container { grid-template-columns: 1fr; gap: 28px; text-align: center; }
            .footer-brand .brand-header { justify-content: center; }
            .social-links { justify-content: center; }
            .footer-column h3::after { left: 50%; transform: translateX(-50%); }
            .contact-item { justify-content: center; }
        }

        /* ══ GENERAL RESPONSIVE UTILS ══ */
        @media (max-width: 767px) {
            .section-header { margin-bottom: 2rem; }
            .section-header h2 { font-size: 1.6rem; }
            .py-lg-5 { padding-top: 2rem !important; padding-bottom: 2rem !important; }
        }
        @media (max-width: 480px) {
            .service-card { padding: 1.8rem !important; }
        }
    </style>
</head>
<body>

    <!-- ── NAVBAR ── -->
    <nav class="navbar navbar-expand-lg fixed-top" id="navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <img src="pictures/amourlogo.png" alt="AmourStay Logo" height="40" class="me-2">
                AMOURSTAY
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"
                    aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="#facilities">Facilities</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact Us</a></li>
                </ul>
                <div class="d-flex gap-3">
                    <button class="btn btn-signin" data-bs-toggle="modal" data-bs-target="#loginModal">Sign In</button>
                    <button class="btn btn-signup" data-bs-toggle="modal" data-bs-target="#registerModal">Sign Up</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- ── HERO ── -->
    <section id="home" class="hero">
        <div class="container">
            <h3>WELCOME TO</h3>
            <div class="hero-divider"></div>
            <h1 class="display-3 fw-bold mb-4">AMOURSTAY DORMITEL</h1>
            <p class="lead mb-5 opacity-75">Experience comfortable living with modern amenities designed for students and <br>
                long-term tenants. Your safety, comfort, and affordability are our priority.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#about" class="btn-readmore btn btn-outline-light px-5 py-3 rounded-pill">Read More</a>
                <a href="#contact" class="btn btn-warning btn-inquire px-5 py-3 rounded-pill" style="background:var(--primary-orange); border:none; color:white;">Inquire Now</a>
            </div>
        </div>
    </section>

    <!-- ── SERVICES ── -->
    <section id="services" class="py-5 mt-5">
        <div class="container py-lg-5">
            <div class="section-header">
                <span class="bubble-label">Our Services</span>
                <h2 class="fw-bold">What We Offer</h2>
                <p class="text-muted mx-auto" style="max-width: 600px;">We provide reliable services to ensure your stay is comfortable, safe, and hassle-free.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="service-card shadow-sm p-5 bg-white text-center">
                        <i class="bi bi-shield-check service-icon"></i>
                        <h5 class="fw-bold">24/7 Security</h5>
                        <p class="text-muted small">CCTV systems operating 24/7 to monitor common areas and enhance resident safety.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card shadow-sm p-5 bg-white text-center">
                        <i class="bi bi-wifi service-icon"></i>
                        <h5 class="fw-bold">High-Speed WiFi</h5>
                        <p class="text-muted small">High-speed internet access available throughout the building for reliable connectivity.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card shadow-sm p-5 bg-white text-center">
                        <i class="bi bi-droplet service-icon"></i>
                        <h5 class="fw-bold">Water Supply</h5>
                        <p class="text-muted small">Reliable water supply supported by proper storage and filtration systems.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card shadow-sm p-5 bg-white text-center">
                        <i class="bi bi-tools service-icon"></i>
                        <h5 class="fw-bold">Maintenance</h5>
                        <p class="text-muted small">Regular maintenance services to keep rooms and facilities clean, and well-maintained.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card shadow-sm p-5 bg-white text-center">
                        <i class="bi bi-calendar-check service-icon"></i>
                        <h5 class="fw-bold">Flexible Terms</h5>
                        <p class="text-muted small">Monthly, quarterly, and annual rental options to fit your schedule.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card shadow-sm p-5 bg-white text-center">
                        <i class="bi bi-house-heart service-icon"></i>
                        <h5 class="fw-bold">Community Living</h5>
                        <p class="text-muted small">A welcoming shared living environment that encourages respect and community.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── FACILITIES ── -->
    <section id="facilities" class="py-5 bg-light">
        <div class="container py-lg-5">
            <div class="section-header">
                <span class="bubble-label">Our Facilities</span>
                <h2 class="fw-bold">Explore Our Spaces</h2>
                <p class="text-muted mx-auto" style="max-width: 600px;">Browse through our well-maintained facilities designed for comfort and convenience.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6"><div class="facility-card shadow-sm"><img src="facilities/soloroom.jpg" class="w-100 h-100" style="object-fit:cover;"><div class="facility-overlay"><h5 class="fw-bold">Solo Room</h5><p class="small">Private and cozy space</p></div></div></div>
                <div class="col-lg-4 col-md-6"><div class="facility-card shadow-sm"><img src="facilities/singleroom.jpg" class="w-100 h-100" style="object-fit:cover;"><div class="facility-overlay"><h5 class="fw-bold">Single Room</h5><p class="small">Comfortable for individuals</p></div></div></div>
                <div class="col-lg-4 col-md-6"><div class="facility-card shadow-sm"><img src="facilities/shared 2pax.jpg" class="w-100 h-100" style="object-fit:cover;"><div class="facility-overlay"><h5 class="fw-bold">Shared Room (2 Pax)</h5><p class="small">Ideal for roommates</p></div></div></div>
                <div class="col-lg-4 col-md-6"><div class="facility-card shadow-sm"><img src="facilities/sharedroom 3pax.jpg" class="w-100 h-100" style="object-fit:cover;"><div class="facility-overlay"><h5 class="fw-bold">Shared Room (3 Pax)</h5><p class="small">Budget-friendly sharing</p></div></div></div>
                <div class="col-lg-4 col-md-6"><div class="facility-card shadow-sm"><img src="facilities/sharedroom4pax.jpg" class="w-100 h-100" style="object-fit:cover;"><div class="facility-overlay"><h5 class="fw-bold">Shared Room (4 Pax)</h5><p class="small">Spacious shared living</p></div></div></div>
                <div class="col-lg-4 col-md-6"><div class="facility-card shadow-sm"><img src="facilities/kitchen.jpg" class="w-100 h-100" style="object-fit:cover;"><div class="facility-overlay"><h5 class="fw-bold">Shared Kitchen</h5><p class="small">Clean and shared cooking</p></div></div></div>
                <div class="col-lg-4 col-md-6"><div class="facility-card shadow-sm"><img src="facilities/restroom.jpg" class="w-100 h-100" style="object-fit:cover;"><div class="facility-overlay"><h5 class="fw-bold">Shared Bathroom</h5><p class="small">Hygienic and maintained</p></div></div></div>
                <div class="col-lg-4 col-md-6"><div class="facility-card shadow-sm"><img src="facilities/parking.jpg" class="w-100 h-100" style="object-fit:cover;"><div class="facility-overlay"><h5 class="fw-bold">Parking</h5><p class="small">Secure vehicle space</p></div></div></div>
                <div class="col-lg-4 col-md-6"><div class="facility-card shadow-sm"><img src="facilities/balcony.jpg" class="w-100 h-100" style="object-fit:cover;"><div class="facility-overlay"><h5 class="fw-bold">Open Balcony</h5><p class="small">Relaxing open space</p></div></div></div>
            </div>
        </div>
    </section>

    <!-- ── ABOUT ── -->
    <section id="about" class="py-5 bg-white">
        <div class="container py-lg-5">
            <div class="section-header">
                <span class="bubble-label">About Us</span>
                <h2 class="fw-bold">Your Home Away From Home</h2>
                <p class="text-muted mx-auto" style="max-width: 600px;">Dedicated to providing the best living experience for the modern tenant.</p>
            </div>
            <div class="row align-items-center g-5 mt-2">
                <div class="col-lg-6">
                    <h3 class="fw-bold">Committed to Providing Quality <br> Living for Students</h3>
                    <p class="text-muted my-4">AmourStay Dormitel was founded with a simple mission: to provide safe, comfortable, and affordable living spaces for students and young professionals. We understand the challenges of finding a reliable place to stay, and we're here to make that journey easier for you.</p>
                    <p class="text-muted">Our dormitel combines the convenience of a hotel with the warmth of a home. <br> With well-maintained facilities, friendly staff, and a community-oriented environment, we ensure that your stay with us is not just comfortable but also memorable.</p>
                </div>
                <div class="col-lg-6">
                    <div class="about-image-wrapper">
                        <img src="facilities/about us.jpg" class="img-back" alt="Back Image">
                        <img src="pictures/landing.jpg" class="img-front" alt="Front Image">
                    </div>
                </div>
            </div>
            <div class="row g-4 mt-2">
                <div class="row g-4">
                    <div class="col-md-6 col-lg-3 d-flex">
                        <div class="about-card w-100 d-flex flex-column align-items-center text-center p-4">
                            <i class="bi bi-shield-lock about-icon mb-3"></i>
                            <h6 class="fw-bold color-orange">Our Story</h6>
                            <p class="small text-muted mb-0 mt-auto">Founded to address the challenges students face in finding safe and affordable housing, AmourStay Dormitel has grown into a trusted home-away-from-home.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 d-flex">
                        <div class="about-card w-100 d-flex flex-column align-items-center text-center p-4">
                            <i class="bi bi-wallet2 about-icon mb-3"></i>
                            <h6 class="fw-bold color-orange">Affordable & Transparent Rates</h6>
                            <p class="small text-muted mb-0 mt-auto">We offer clear, competitive pricing for our rooms, ensuring comfortable and secure living is accessible for all residents.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 d-flex">
                        <div class="about-card w-100 d-flex flex-column align-items-center text-center p-4">
                            <i class="bi bi-people about-icon mb-3"></i>
                            <h6 class="fw-bold color-orange">Our Community</h6>
                            <p class="small text-muted mb-0 mt-auto">We encourage interaction, collaboration, and friendship among residents, creating a vibrant and welcoming dormitel community.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 d-flex">
                        <div class="about-card w-100 d-flex flex-column align-items-center text-center p-4">
                            <i class="bi bi-arrow-up-right-circle about-icon mb-3"></i>
                            <h6 class="fw-bold color-orange">Growth & Development</h6>
                            <p class="small text-muted mb-0 mt-auto">We aim to nurture not just living spaces but also personal growth, helping residents thrive academically and socially during their stay.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<!-- ── CONTACT ── -->
    <section id="contact" class="py-5 bg-light">
        <div class="container py-lg-5">
            <div class="section-header text-center mb-5">
                <span class="bubble-label">Contact Us</span>
                <h2 class="fw-bold">Get In Touch Today</h2>
                <p class="text-muted mx-auto" style="max-width: 600px;">Have questions or ready to inquire? We'd love to hear from you.</p>
            </div>
            <div class="row g-5">
                <!-- MAP -->
                <div class="col-lg-5 d-flex flex-column">
                    <div class="map-container rounded-4 overflow-hidden shadow-sm mb-3 flex-grow-1 d-flex flex-column">
                        <!-- Map View Toggle Buttons -->
                        <div class="d-flex gap-2 p-2 bg-white border-bottom">
                            <button style="background:#f97316;border:1px solid #f97316;color:#fff;border-radius:6px;font-size:0.8rem;padding:4px 10px;cursor:pointer;transition:all 0.2s;" 
                                onclick="switchMap('default')" id="btn-default">
                                <i class="bi bi-map me-1"></i>Map
                            </button>
                            <button style="background:transparent;border:1px solid #dee2e6;color:#666;border-radius:6px;font-size:0.8rem;padding:4px 10px;cursor:pointer;transition:all 0.2s;" 
                                onclick="switchMap('satellite')" id="btn-satellite">
                                <i class="bi bi-globe me-1"></i>Satellite
                            </button>
                            <button style="background:transparent;border:1px solid #dee2e6;color:#666;border-radius:6px;font-size:0.8rem;padding:4px 10px;cursor:pointer;transition:all 0.2s;" 
                                onclick="switchMap('street')" id="btn-street">
                                <i class="bi bi-camera me-1"></i>Street View
                            </button>
                        </div>

                        <div id="map-default" style="flex:1;">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3826.4!2d120.3876!3d17.5747!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sH94M%2B843%2C%20Quirino%20Blvd%2C%20Vigan%20City%2C%20Ilocos%20Sur%2C%20Philippines!5e0!3m2!1sen!2sph!4v1700000000000!5m2!1sen!2sph"
                                style="border:0;width:100%;height:100%;min-height:260px;" allowfullscreen="" loading="lazy"></iframe>
                        </div>

                        <div id="map-satellite" style="display:none;flex:1;">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3826.4!2d120.3876!3d17.5747!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sH94M%2B843%2C%20Quirino%20Blvd%2C%20Vigan%20City%2C%20Ilocos%20Sur%2C%20Philippines!5e1!3m2!1sen!2sph!4v1700000000000!5m2!1sen!2sph"
                                style="border:0;width:100%;height:100%;min-height:260px;" allowfullscreen="" loading="lazy"></iframe>
                        </div>

                        <div id="map-street" style="display:none;flex:1;">
                            <iframe src="https://www.google.com/maps/embed?pb=!4v1700000000000!6m8!1m7!1sCAoSLEFGMVFpcE5fUzVQX1VKWXpKNTVBd3hOT3hCOHZkLWZabVFmX3Fsc3pOejc4!2m2!1d17.5747!2d120.3876!3f0!4f0!5f0.7820865974627469"
                                style="border:0;width:100%;height:100%;min-height:260px;" allowfullscreen="" loading="lazy"></iframe>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-6"><div class="info-card d-flex align-items-start"><i class="bi bi-geo-alt info-icon me-3"></i><div><h6 class="fw-bold color-orange mb-1">Address</h6><p class="small text-muted mb-0">Shas Amour Dormitel, Tamag Vigan City, 2700</p></div></div></div>
                        <div class="col-6"><div class="info-card d-flex align-items-start"><i class="bi bi-telephone info-icon me-3"></i><div><h6 class="fw-bold color-orange mb-1">Phone</h6><p class="small text-muted mb-0">+63 976 602 7466</p></div></div></div>
                        <div class="col-6"><div class="info-card d-flex align-items-start"><i class="bi bi-envelope info-icon me-3"></i><div><h6 class="fw-bold color-orange mb-1">Email</h6><p class="small text-muted mb-0">shasamourdormitel@gmail.com</p></div></div></div>
                        <div class="col-6"><div class="info-card d-flex align-items-start"><i class="bi bi-clock info-icon me-3"></i><div><h6 class="fw-bold color-orange mb-1">Work Hours</h6><p class="small text-muted mb-0">Mon - Sun <br> 9:00 AM - 5:00 PM</p></div></div></div>
                    </div>
                </div>

                <!-- FORM -->
                <div class="col-lg-7">
                    <div class="contact-form bg-white p-5 rounded-4 shadow-sm">
                        <h5 class="fw-bold mb-4 contact-header">Send Us a Message</h5>
                        <?php if (!empty($contact_error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($contact_error); ?></div>
                        <?php endif; ?>
                        <form method="post" action="LandingPage.php">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Complete Name</label>
                                <input type="text" name="contact_name" class="form-control bg-light border-0 py-3" placeholder="Enter your full name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Email Address</label>
                                <input type="email" name="contact_email" class="form-control bg-light border-0 py-3" placeholder="Enter your email" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold small">Message / Inquiries</label>
                                <textarea name="contact_message" class="form-control bg-light border-0 py-3" rows="4" placeholder="How can we help you?" required></textarea>
                            </div>
                            <button type="submit" name="send_contact" class="btn btn-warning w-100 py-3 fw-bold text-white contact-btn">
                                <i class="bi bi-send me-2"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

<script>
function switchMap(type) {
    document.getElementById('map-default').style.display = 'none';
    document.getElementById('map-satellite').style.display = 'none';
    document.getElementById('map-street').style.display = 'none';

    const buttons = {
        default: document.getElementById('btn-default'),
        satellite: document.getElementById('btn-satellite'),
        street: document.getElementById('btn-street')
    };

    Object.values(buttons).forEach(btn => {
        btn.style.background = 'transparent';
        btn.style.borderColor = '#dee2e6';
        btn.style.color = '#666';
    });

    buttons[type].style.background = '#f97316';
    buttons[type].style.borderColor = '#f97316';
    buttons[type].style.color = '#fff';

    // When switching, restore flex:1 on the shown div
    document.getElementById('map-' + type).style.display = 'flex';
    document.getElementById('map-' + type).style.flex = '1';
}
</script>

    <!-- Contact Success Modal -->
    <div class="modal fade" id="contactSuccessModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Message Sent!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Thank You!</h5>
                    <p class="text-muted mb-0">Your message has been sent successfully. We will get back to you shortly.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── FOOTER ── -->
    <footer>
        <div class="footer-container">
            <div class="footer-brand">
                <div class="brand-header d-flex align-items-center gap-2">
                    <img src="pictures/amourlogo.png" alt="AmourStay Logo" class="logo-icon" style="width:40px; height:40px;">
                    <h2 class="mb-0"><b>AmourStay</b> Dormitel</h2>
                </div>
                <p>Your trusted home away from home. We provide safe, comfortable, and affordable living spaces for students and young professionals.</p>
                <div class="social-links">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-twitter"></i></a>
                </div>
            </div>
            <div class="d-flex justify-content-center">
                <div class="footer-column text-start">
                    <h3><b>Quick Links</b></h3>
                    <ul class="list-unstyled mb-0">
                        <li><a href="#home" class="text-decoration-none text-light">Home</a></li>
                        <li><a href="#services" class="text-decoration-none text-light">Services</a></li>
                        <li><a href="#facilities" class="text-decoration-none text-light">Facilities</a></li>
                        <li><a href="#about" class="text-decoration-none text-light">About Us</a></li>
                        <li><a href="#contact" class="text-decoration-none text-light">Contact Us</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-column">
                <h3><b>Contact Info</b></h3>
                <div class="contact-item d-flex align-items-center mb-2"><i class="bi bi-geo-alt-fill me-2"></i><span>Shas Amour Dormitel, Tamag, Vigan City, 2700</span></div>
                <div class="contact-item d-flex align-items-center mb-2"><i class="bi bi-telephone-fill me-2"></i><span>+63 976 602 7466</span></div>
                <div class="contact-item d-flex align-items-center mb-2"><i class="bi bi-envelope-fill me-2"></i><span>shasamourdormitel@gmail.com</span></div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 AmourStay Dormitel. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        feather.replace();
    </script>

    <!-- ── NAVBAR SCROLL STATE (FIXED) ── -->
    <script>
        (function () {
            const nav   = document.getElementById('navbar');
            const brand = nav.querySelector('.navbar-brand');

            function updateNav() {
                if (window.scrollY > 50) {
                    nav.classList.add('scrolled');
                } else {
                    nav.classList.remove('scrolled');
                }
            }

            // Run on scroll and on load
            window.addEventListener('scroll', updateNav, { passive: true });
            updateNav();
        })();
    </script>

    <!-- ── LOGIN MODAL ── -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="max-width: 500px; margin: 0 auto;">
                <div class="modal-header border-0 py-2 d-flex justify-content-center align-items-center">
                    <img src="pictures/amourlogo.png" alt="AMOURSTAY Logo" height="30" class="me-2">
                    <span class="fw-bold">AMOURSTAY</span>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <h4 class="text-center mb-4">Welcome Back! <br> Login to Your Account</h4>
                    <?php if (!empty($error) && isset($show_error_modal)): ?>
                        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form id="loginForm" method="post" action="">
                        <div class="mb-3">
                            <label for="loginEmail" class="form-label">Email or Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-feather="mail" style="width:16px;height:16px;"></i></span>
                                <input type="text" class="form-control form-control-sm-radius" id="loginEmail" name="loginEmail" placeholder="Enter your email or username" required value="<?php echo isset($_POST['loginEmail']) ? htmlspecialchars($_POST['loginEmail']) : ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-feather="lock" style="width:16px;height:16px;"></i></span>
                                <input type="password" class="form-control form-control-sm-radius" id="loginPassword" name="loginPassword" placeholder="Enter your password" required>
                            </div>
                        </div>
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe">
                                <label class="form-check-label" for="rememberMe">Remember Me</label>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="login" class="btn btn-gradient">Login</button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <p class="mb-0">Don't have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal">Signup Now</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- ── REGISTER MODAL ── -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 bg-dark text-white py-1 d-flex justify-content-center align-items-center">
                    <img src="pictures/amourlogo.png" alt="AMOURSTAY Logo" height="30" class="me-2">
                    <span class="fw-bold">AMOURSTAY</span>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div id="titleBlock" class="text-center mb-4">
                        <h3 class="fw-bold mb-2">Create New Account</h3>
                        <p class="mb-0 small text-muted">Already have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a></p>
                    </div>
                    <!-- Step Indicator -->
                    <div class="step-indicator mb-4">
                        <ul class="list-unstyled d-flex justify-content-center align-items-center mb-0">
                            <li class="step active"><div class="step-circle">1</div></li>
                            <li class="step-connector inactive"></li>
                            <li class="step"><div class="step-circle">2</div></li>
                            <li class="step-connector inactive"></li>
                            <li class="step"><div class="step-circle">3</div></li>
                            <li class="step-connector inactive"></li>
                            <li class="step"><div class="step-circle">4</div></li>
                        </ul>
                    </div>
                    <form id="registerForm" method="post" class="needs-validation" novalidate>
                        <!-- Step 1 -->
                        <div id="step1">
                            <h4 class="text-left mb-4">Step 1: Personal Information</h4>
                            <div class="mb-4">
                                <h5 class="fw-semibold text-muted mb-3">Complete Name</h5>
                                <div class="row g-3">
                                    <div class="col-md-3"><label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label><input type="text" class="form-control form-control-sm-radius" id="lastName" name="lastName" placeholder="*Enter Last Name" required><div class="invalid-feedback">Last name is required.</div></div>
                                    <div class="col-md-3"><label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label><input type="text" class="form-control form-control-sm-radius" id="firstName" name="firstName" placeholder="*Enter First Name" required><div class="invalid-feedback">First name is required.</div></div>
                                    <div class="col-md-3"><label for="middleName" class="form-label">Middle Name</label><input type="text" class="form-control form-control-sm-radius" id="middleName" name="middleName" placeholder="Enter Middle Name"></div>
                                    <div class="col-md-3"><label for="extensionName" class="form-label">Extension Name</label><input type="text" class="form-control form-control-sm-radius" id="extensionName" name="extensionName" placeholder="Extension: Jr, Sr, III"></div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <h5 class="fw-semibold text-muted mb-3">Complete Address</h5>
                                <div class="row g-3">
                                    <div class="col-md-3"><label for="province" class="form-label">Province <span class="text-danger">*</span></label><select class="form-select form-control-sm-radius" id="province" name="province" required><option value="" disabled selected>Select Province</option><option value="012900000">Ilocos Sur</option><option value="012800000">Ilocos Norte</option><option value="013300000">La Union</option><option value="015500000">Pangasinan</option></select><div class="invalid-feedback">Province is required.</div></div>
                                    <div class="col-md-3"><label for="municipality" class="form-label">Municipality <span class="text-danger">*</span></label><select class="form-select form-control-sm-radius" id="municipality" name="municipality" required><option value="" disabled selected>Select Municipality</option></select><div class="invalid-feedback">Municipality is required.</div></div>
                                    <div class="col-md-3"><label for="barangay" class="form-label">Barangay <span class="text-danger">*</span></label><select class="form-select form-control-sm-radius" id="barangay" name="barangay" required><option value="" disabled selected>Select Barangay</option></select><div class="invalid-feedback">Barangay is required.</div></div>
                                    <div class="col-md-3"><label for="houseStreet" class="form-label">House No., Purok, Street</label><input type="text" class="form-control form-control-sm-radius" id="houseStreet" name="houseStreet" placeholder="Enter House No., Purok, Street"></div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <h5 class="fw-semibold text-muted mb-3">Contact Info & Other Details</h5>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6"><label for="contactNumber" class="form-label">Contact Number <span class="text-danger">*</span></label><div class="input-group"><span class="input-group-text">+63</span><input type="tel" class="form-control form-control-sm-radius" id="contactNumber" name="contact" placeholder="9XXXXXXXXX" maxlength="11" inputmode="numeric" pattern="[0-9]{10,11}" required></div><div class="invalid-feedback">Contact number must be 10 or 11 digits.</div></div>
                                    <div class="col-md-6"><label for="email" class="form-label">Email Address <span class="text-danger">*</span></label><input type="email" class="form-control form-control-sm-radius" id="email" name="email" placeholder="Enter Email Address" required><div class="invalid-feedback">Please enter a valid email address.</div><div id="email-feedback" class="mt-1"></div></div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6"><label for="sex" class="form-label">Sex <span class="text-danger">*</span></label><select class="form-select form-control-sm-radius" id="sex" name="sex" required><option value="" disabled selected>Select Sex</option><option value="Male">Male</option><option value="Female">Female</option><option value="Prefer not to say">Prefer not to say</option></select><div class="invalid-feedback">Sex is required.</div></div>
                                    <div class="col-md-6"><label for="occupation" class="form-label">Occupation <span class="text-danger">*</span></label><select class="form-select form-control-sm-radius" id="occupation" name="occupation" required><option value="" disabled selected>Select Occupation</option><option value="Student">Student</option><option value="Employee">Employee</option><option value="Freelancer">Freelancer</option><option value="Unemployed">Unemployed</option></select><div class="invalid-feedback">Occupation is required.</div></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div id="step2" class="d-none">
                            <h4 class="text-left mb-4">Step 2: Emergency Contact Information</h4>
                            <div class="mb-4">
                                <h5 class="fw-semibold text-muted mb-3">Emergency Complete Name</h5>
                                <div class="row g-3">
                                    <div class="col-md-3"><label for="emergencyLastName" class="form-label">Last Name <span class="text-danger">*</span></label><input type="text" class="form-control form-control-sm-radius" id="emergencyLastName" name="emergencyLastName" placeholder="Enter Last Name" required><div class="invalid-feedback">Last name is required.</div></div>
                                    <div class="col-md-3"><label for="emergencyFirstName" class="form-label">First Name <span class="text-danger">*</span></label><input type="text" class="form-control form-control-sm-radius" id="emergencyFirstName" name="emergencyFirstName" placeholder="Enter First Name" required><div class="invalid-feedback">First name is required.</div></div>
                                    <div class="col-md-3"><label for="emergencyMiddleName" class="form-label">Middle Name</label><input type="text" class="form-control form-control-sm-radius" id="emergencyMiddleName" name="emergencyMiddleName" placeholder="Enter Middle Name"></div>
                                    <div class="col-md-3"><label for="emergencyExtensionName" class="form-label">Extension Name</label><input type="text" class="form-control form-control-sm-radius" id="emergencyExtensionName" name="emergencyExtensionName" placeholder="Extension: Jr, Sr, III"></div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <h5 class="fw-semibold text-muted mb-3">Emergency Contact Complete Address</h5>
                                <div class="row g-3">
                                    <div class="col-md-3"><label for="emergencyProvince" class="form-label">Province <span class="text-danger">*</span></label><select class="form-select form-control-sm-radius" id="emergencyProvince" name="emergencyProvince" required><option value="" disabled selected>Select Province</option><option value="012900000">Ilocos Sur</option><option value="012800000">Ilocos Norte</option><option value="013300000">La Union</option><option value="015500000">Pangasinan</option></select><div class="invalid-feedback">Province is required.</div></div>
                                    <div class="col-md-3"><label for="emergencyCity" class="form-label">Municipality <span class="text-danger">*</span></label><select class="form-select form-control-sm-radius" id="emergencyCity" name="emergencyCity" required><option value="" disabled selected>Select Municipality</option></select><div class="invalid-feedback">Municipality is required.</div></div>
                                    <div class="col-md-3"><label for="emergencyBarangay" class="form-label">Barangay <span class="text-danger">*</span></label><select class="form-select form-control-sm-radius" id="emergencyBarangay" name="emergencyBarangay" required><option value="" disabled selected>Select Barangay</option></select><div class="invalid-feedback">Barangay is required.</div></div>
                                    <div class="col-md-3"><label for="emergencyStreet" class="form-label">Street/House No.</label><input type="text" class="form-control form-control-sm-radius" id="emergencyStreet" name="emergencyStreet" placeholder="Enter Street/House No."></div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <h5 class="fw-semibold text-muted mb-3">Emergency Contact Details</h5>
                                <div class="row g-3">
                                    <div class="col-md-6"><label for="emergencyContactNumber" class="form-label">Contact Number <span class="text-danger">*</span></label><div class="input-group"><span class="input-group-text">+63</span><input type="tel" class="form-control form-control-sm-radius" id="emergencyContactNumber" name="emergencyContactNumber" placeholder="9XXXXXXXXX" maxlength="10" inputmode="numeric" required></div><div class="invalid-feedback">Must be exactly 10 numeric digits.</div></div>
                                    <div class="col-md-6"><label for="relationship" class="form-label">Relationship <span class="text-danger">*</span></label><select class="form-select form-control-sm-radius" id="relationship" name="relationship" required><option value="" disabled selected>Select Relationship</option><option value="Parent">Parent</option><option value="Sibling">Sibling</option><option value="Relative">Relative</option><option value="Guardian">Guardian</option></select><div class="invalid-feedback">Relationship is required.</div></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div id="step3" class="d-none">
                            <h4 class="text-left mb-4">Step 3: Account Details</h4>
                            <div class="mb-4">
                                <h5 class="fw-semibold text-muted mb-3">Account Information</h5>
                                <div class="row g-3">
                                    <div class="col-md-6"><label for="username" class="form-label">Username <span class="text-danger">*</span></label><input type="text" class="form-control form-control-sm-radius" id="username" name="username" placeholder="Enter Username" required><div class="invalid-feedback">Username is required.</div><div id="username-feedback" class="mt-1"></div></div>
                                    <div class="col-md-6"><label for="password" class="form-label">Password <span class="text-danger">*</span></label><input type="password" class="form-control form-control-sm-radius" id="password" name="password" placeholder="Enter Password" required><div class="invalid-feedback">Password is required.</div><div id="passwordFeedback" class="mt-2"><small class="form-text text-muted">At least 8 characters with uppercase, lowercase, number, and special character.</small><div id="passwordStrength" class="mt-1"></div></div></div>
                                    <div class="col-md-6"><label for="confirmPassword" class="form-label">Confirm Password <span class="text-danger">*</span></label><input type="password" class="form-control form-control-sm-radius" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required><div class="invalid-feedback">Confirm password is required.</div><div id="confirmPasswordFeedback" class="mt-2"><small id="confirmPasswordMessage" class="form-text text-muted"></small></div></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div id="step4" class="d-none">
                            <div class="px-4 py-3">
                                <h4 class="text-left mb-4">Step 4: Terms & Conditions</h4>
                                <h5 class="fw-semibold text-muted mb-3">Dormitory Rules & Regulations</h5>
                                <div class="border rounded-3 p-3 mb-4 bg-light" style="max-height: 260px; overflow-y: auto; font-size: 0.95rem;">
                                    <p class="fw-semibold mb-1">1. Payment of Fees</p><p>Tenants must pay their monthly rent on or before the due date. Late payments will incur a penalty fee.</p>
                                    <p class="fw-semibold mb-1">2. Room Assignment</p><p>Rooms are assigned based on availability. Requests for room changes must be approved by the management.</p>
                                    <p class="fw-semibold mb-1">3. Guest Policy</p><p>No guests are allowed to stay overnight without prior permission from the dormitory manager.</p>
                                    <p class="fw-semibold mb-1">4. Noise Regulation</p><p>Quiet hours are strictly observed from 10:00 PM to 6:00 AM. Violations may result in warnings or expulsion.</p>
                                    <p class="fw-semibold mb-1">5. Cleanliness</p><p>Tenants are responsible for keeping their rooms clean. Inspections may be conducted without prior notice.</p>
                                    <p class="fw-semibold mb-1">6. Damages</p><p>Any damage to dormitory property will be charged to the tenant responsible.</p>
                                    <p class="fw-semibold mb-1">7. Prohibited Items</p><p>Smoking, alcohol, illegal drugs, weapons, and cooking appliances are not allowed inside the dormitory premises.</p>
                                    <p class="fw-semibold mb-1">8. Eviction Policy</p><p>The management reserves the right to evict any tenant who violates the rules or engages in harmful conduct.</p>
                                </div>
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="termsCheck" name="agreeCheckbox" required>
                                    <label class="form-check-label" for="termsCheck">I have read and agree to the <strong>Terms & Conditions</strong></label>
                                    <div class="invalid-feedback">You must agree to the Terms & Conditions to continue.</div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0" id="modalFooter">
                            <button type="button" class="btn btn-primary" onclick="nextStep()">Next</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Validation Error Modal -->
    <div class="modal fade" id="validationErrorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Validation Error</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">Please fill out all required fields correctly to proceed.</div>
                <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button></div>
            </div>
        </div>
    </div>

    <!-- Registration Success Modal -->
 <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 360px;">
        <div class="modal-content border-0 shadow-sm rounded-3">
            <div class="modal-body p-4 text-center">
                <h6 class="fw-semibold mb-2">Registration Successful</h6>
                <p class="text-muted small mb-3">
                    Your account has been created successfully.<br>
                    You can now login to your account.
                </p>
                <button type="button" class="btn btn-sm btn-secondary px-4" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
    <!-- ── SCRIPTS ── -->

    <!-- Login form validation -->
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
            }
        });
    </script>

    <!-- Multi-step register -->
    <script>
        let currentStep = 1;

        function showStep(step) {
            [1,2,3,4].forEach(s => document.getElementById('step'+s).classList.add('d-none'));
            document.getElementById('titleBlock').classList.toggle('d-none', step !== 1);
            document.getElementById('step'+step).classList.remove('d-none');
            updateFooter(step);
            updateStepTracker(step);
        }

        function updateFooter(step) {
            const footer = document.getElementById('modalFooter');
            const back = '<button type="button" class="btn btn-secondary me-2" onclick="prevStep()">Back</button>';
            const next = '<button type="button" class="btn btn-primary" onclick="nextStep()">Next</button>';
            const submit = '<button type="submit" name="register" class="btn btn-success">Submit</button>';
            if      (step === 1) footer.innerHTML = next;
            else if (step === 4) footer.innerHTML = back + submit;
            else                 footer.innerHTML = back + next;
        }

        function updateStepTracker(step) {
            document.querySelectorAll('.step-connector').forEach((c, i) => {
                c.classList.toggle('active', i < step - 1);
                c.classList.toggle('inactive', i >= step - 1);
            });
            document.querySelectorAll('.step').forEach((s, i) => {
                s.classList.toggle('active', i + 1 === step);
                s.classList.toggle('completed', i + 1 < step);
            });
        }

        function validateStep(stepNum) {
            const fields = document.querySelectorAll('#step' + stepNum + ' [required]');
            let valid = true;
            fields.forEach(f => {
                if (!f.checkValidity()) {
                    valid = false;
                    f.classList.add('is-invalid');
                } else {
                    f.classList.remove('is-invalid');
                }
            });

            if (stepNum === 3) {
                const pw      = document.getElementById('password').value;
                const cpw     = document.getElementById('confirmPassword').value;
                const uField  = document.getElementById('username');
                const cpwFb   = document.getElementById('confirmPassword');

                // Password match check
                if (pw !== cpw) {
                    valid = false;
                    cpwFb.classList.add('is-invalid');
                    const fb = cpwFb.nextElementSibling;
                    if (fb && fb.classList.contains('invalid-feedback')) fb.textContent = 'Passwords do not match.';
                }

                // Password strength check
                const hasUpper   = /[A-Z]/.test(pw);
                const hasLower   = /[a-z]/.test(pw);
                const hasNum     = /[0-9]/.test(pw);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(pw);
                const longEnough = pw.length >= 8;
                if (!longEnough || !hasUpper || !hasLower || !hasNum || !hasSpecial) {
                    valid = false;
                    document.getElementById('password').classList.add('is-invalid');
                }

                // Username availability check
                const uAvail = uField.getAttribute('data-available');
                if (!uField.value.trim()) {
                    valid = false;
                    uField.classList.add('is-invalid');
                    const fb = uField.nextElementSibling;
                    if (fb && fb.classList.contains('invalid-feedback')) fb.textContent = 'Username is required.';
                } else if (uAvail === 'false') {
                    valid = false;
                    uField.classList.add('is-invalid');
                    const fb = uField.nextElementSibling;
                    if (fb && fb.classList.contains('invalid-feedback')) fb.textContent = 'Username is already taken.';
                } else if (uAvail === null || uAvail === '') {
                    // Still checking or not yet typed — allow if field has a value but warn
                    // We treat it as valid so the user isn't blocked if check_availability.php is slow
                    uField.classList.remove('is-invalid');
                }
            }

            if (!valid) new bootstrap.Modal(document.getElementById('validationErrorModal')).show();
            return valid;
        }

        function nextStep() {
            if (validateStep(currentStep)) { currentStep++; showStep(currentStep); }
        }
        function prevStep() {
            if (currentStep > 1) { currentStep--; showStep(currentStep); }
        }

        document.getElementById('registerModal').addEventListener('show.bs.modal', function() {
            currentStep = 1; showStep(1);
        });

        document.querySelectorAll('.modal').forEach(function(modal) {
    modal.addEventListener('shown.bs.modal', function () {
        feather.replace();
    });
});
    </script>

    <!-- PSGC API -->
    <script>
        function loadMunicipalities(provinceCode, munSelect, barSelect) {
            munSelect.innerHTML = '<option value="" disabled selected>Select Municipality</option>';
            barSelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            if (!provinceCode) return;
            fetch(`https://psgc.gitlab.io/api/provinces/${provinceCode}/municipalities/`)
                .then(r => r.json())
                .then(data => data.forEach(m => {
                    const o = new Option(m.name, m.code);
                    munSelect.appendChild(o);
                }));
        }
        function loadBarangays(munCode, barSelect) {
            barSelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            if (!munCode) return;
            fetch(`https://psgc.gitlab.io/api/municipalities/${munCode}/barangays/`)
                .then(r => r.json())
                .then(data => data.forEach(b => {
                    const o = new Option(b.name, b.code);
                    barSelect.appendChild(o);
                }));
        }

        document.getElementById('province').addEventListener('change', function() {
            loadMunicipalities(this.value, document.getElementById('municipality'), document.getElementById('barangay'));
        });
        document.getElementById('municipality').addEventListener('change', function() {
            loadBarangays(this.value, document.getElementById('barangay'));
        });
        document.getElementById('emergencyProvince').addEventListener('change', function() {
            loadMunicipalities(this.value, document.getElementById('emergencyCity'), document.getElementById('emergencyBarangay'));
        });
        document.getElementById('emergencyCity').addEventListener('change', function() {
            loadBarangays(this.value, document.getElementById('emergencyBarangay'));
        });
    </script>

    <!-- Password validation -->
    <script>
        function validatePassword() {
            const pw = document.getElementById('password').value;
            document.getElementById('passwordStrength').innerHTML = `
                <small class="${pw.length>=8?'text-success':'text-danger'}">✓ At least 8 characters</small><br>
                <small class="${/[A-Z]/.test(pw)?'text-success':'text-danger'}">✓ Uppercase letter</small><br>
                <small class="${/[a-z]/.test(pw)?'text-success':'text-danger'}">✓ Lowercase letter</small><br>
                <small class="${/\d/.test(pw)?'text-success':'text-danger'}">✓ Number</small><br>
                <small class="${/[!@#$%^&*(),.?":{}|<>]/.test(pw)?'text-success':'text-danger'}">✓ Special character</small>`;
        }
        function checkConfirm() {
            const pw = document.getElementById('password').value;
            const cpw = document.getElementById('confirmPassword').value;
            const msg = document.getElementById('confirmPasswordMessage');
            if (!cpw) { msg.textContent=''; msg.className='form-text text-muted'; return; }
            msg.textContent = pw===cpw ? 'Passwords match' : 'Passwords do not match';
            msg.className = pw===cpw ? 'form-text text-success' : 'form-text text-danger';
        }
        document.getElementById('password').addEventListener('input', () => { validatePassword(); checkConfirm(); });
        document.getElementById('confirmPassword').addEventListener('input', checkConfirm);
    </script>

    <!-- Email / Username availability -->
    <script>
        function debounce(fn, wait) {
            let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
        }

        function ucfirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        async function checkAvailability(type, value, fbEl, inputEl) {
            if (!value.trim()) {
                fbEl.innerHTML = '';
                inputEl.removeAttribute('data-available');
                return;
            }
            fbEl.innerHTML = '<small class="text-muted">Checking...</small>';
            inputEl.removeAttribute('data-available');
            try {
                const response = await fetch(`check_availability.php?type=${type}&value=${encodeURIComponent(value)}`);
                const data = await response.json();
                if (data.available) {
                    fbEl.innerHTML = `<small class="text-success">${ucfirst(type)} is available ✓</small>`;
                    inputEl.setAttribute('data-available', 'true');
                    inputEl.classList.remove('is-invalid');
                } else {
                    fbEl.innerHTML = `<small class="text-danger">${data.message}</small>`;
                    inputEl.setAttribute('data-available', 'false');
                    inputEl.classList.add('is-invalid');
                }
            } catch (error) {
                fbEl.innerHTML = '<small class="text-danger">Error checking availability. Please try again.</small>';
                inputEl.setAttribute('data-available', 'false');
                console.error('Availability check error:', error);
            }
        }

        const debouncedCheckEmail = debounce(function(value) {
            checkAvailability('email', value, document.getElementById('email-feedback'), document.getElementById('email'));
        }, 500);

        const debouncedCheckUsername = debounce(function(value) {
            checkAvailability('username', value, document.getElementById('username-feedback'), document.getElementById('username'));
        }, 500);

        document.getElementById('email').addEventListener('input', function() {
            debouncedCheckEmail(this.value);
        });
        document.getElementById('username').addEventListener('input', function() {
            debouncedCheckUsername(this.value);
        });
    </script>

    <!-- Auto-show modals on page load -->
    <script>
        <?php if (isset($show_success_modal) && $show_success_modal): ?>
        window.addEventListener('load', function() {
            setTimeout(function() {
                var successModalEl = document.getElementById('successModal');
                var sm = new bootstrap.Modal(successModalEl);
                sm.show();
                // When user closes success modal (either OK or X), open login modal
                successModalEl.addEventListener('hidden.bs.modal', function () {
                    new bootstrap.Modal(document.getElementById('loginModal')).show();
                }, { once: true });
                // Also auto-open login after 2 seconds
                setTimeout(function() {
                    if (document.getElementById('successModal').classList.contains('show')) {
                        sm.hide();
                    }
                }, 2000);
            }, 500);
        });
        <?php endif; ?>

        <?php if (isset($show_error_modal) && $show_error_modal): ?>
        window.addEventListener('load', function() {
            setTimeout(function() {
                new bootstrap.Modal(document.getElementById('loginModal')).show();
            }, 300);
        });
        <?php endif; ?>

        <?php if ($contact_success): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });
            setTimeout(function() {
                new bootstrap.Modal(document.getElementById('contactSuccessModal')).show();
            }, 500);
        });
        <?php endif; ?>

        <?php if (!empty($contact_error)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });
        });
        <?php endif; ?>
    </script>

</body>
</html>
