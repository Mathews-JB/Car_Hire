<?php
include_once 'includes/db.php';
include_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (($pwd_error = validate_password($password)) !== true) {
        $error = $pwd_error;
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Verification token
            $token = bin2hex(random_bytes(32));
            
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, is_verified, verification_token, account_status) VALUES (?, ?, ?, ?, 'customer', 0, ?, 'pending')");
            if ($stmt->execute([$name, $email, $phone, $hashed_password, $token])) {
                // Send verification email
                include_once 'includes/mailer.php';
                $verify_link = APP_URL . "verify-email.php?token=" . $token;
                $mailer = new CarHireMailer();
                $subject = "Verify Your Account - " . SITE_NAME;
                $message = "
                    <h2>Welcome to " . SITE_NAME . "!</h2>
                    <p>Dear {$name},</p>
                    <p>Thank you for registering. Please click the button below to verify your email address and activate your account:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$verify_link}' style='background: #2563eb; color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: 700; display: inline-block;'>Verify My Email</a>
                    </div>
                    <p>Or copy this link into your browser:</p>
                    <p>{$verify_link}</p>
                    <p>Best regards,<br>The " . SITE_NAME . " Team</p>
                ";
                
                $mailer->send($email, $subject, $message, null, SITE_NAME);
                $success = 'Account created! Please check your email (' . $email . ') to verify your account before logging in.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Car Hire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .auth-container {
            max-width: 450px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }
        .auth-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-feedback {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .error { background: #f8d7da; color: #721c24; }
        .success { background: #d4edda; color: #155724; }

        
        /* Validation Feedback Styles */
        .input-wrapper {
            position: relative;
        }
        .validation-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            display: none; /* Hidden by default */
        }
        .validation-icon.valid {
            display: block;
            color: #10b981; /* Green */
        }
        .validation-icon.invalid {
            display: block;
            color: #ef4444; /* Red */
        }
        .validation-message {
            font-size: 0.75rem;
            margin-top: 5px;
            display: none;
        }
        .validation-message.error {
            display: block;
            color: #ef4444;
        }
        .validation-message.success {
            display: block;
            color: #10b981;
        }
        .password-requirements {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.6);
            margin-top: 5px;
            margin-bottom: 15px;
            padding-left: 5px;
        }
        .req-item {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 2px;
        }
        .req-item i {
            font-size: 0.6rem;
        }
        .req-item.met { color: #10b981; }
        .req-item.met i { content: "\f00c"; } /* Check */
        
        body { 
            background: url('public/images/cars/camry.jpg') center/cover no-repeat fixed !important;
        }
        /* Loading Spinner */
        .btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: calc(50% - 10px);
            left: calc(50% - 10px);
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <?php include_once 'includes/mobile_header.php'; ?>

    <div class="auth-bg">
        <div class="auth-card">
            <div style="text-align: center; margin-bottom: 35px;">
                <a href="index.php" class="logo" style="font-size: 2.22rem; display: block; margin-bottom: 5px;">Car Hire</a>
                <span style="color: var(--accent-color); font-weight: 700; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 2px;">Start Your Adventure</span>
            </div>

            <h2 style="margin-bottom: 25px; font-weight: 800; font-size: 1.6rem; color: white;">Create Account</h2>
            
            <?php if($error): ?>
                <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #fda4af; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 0.8rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #6ee7b7; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 0.8rem;">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 4px;">Full Name</label>
                    <input type="text" name="name" required placeholder="John Doe" style="width: 100%;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 4px;">Email</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" id="emailInput" required placeholder="john@domain.com" style="width: 100%;">
                        <i class="fas fa-check-circle validation-icon valid" id="emailValid"></i>
                        <i class="fas fa-times-circle validation-icon invalid" id="emailInvalid"></i>
                    </div>
                    <div class="validation-message" id="emailFeedback"></div>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 4px;">Phone Number</label>
                    <input type="text" name="phone" required placeholder="0970000000" style="width: 100%;">
                </div>
                
                <div class="auth-grid-2" style="display: grid; gap: 15px; margin-bottom: 10px;">
                    <div class="form-group">
                        <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 4px;">Pass</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="passwordInput" required placeholder="••••" style="width: 100%;">
                            <i class="fas fa-check-circle validation-icon valid" id="passValid"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="color: rgba(255,255,255,0.7); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 4px;">Confirm</label>
                        <div class="input-wrapper">
                            <input type="password" name="confirm_password" id="confirmPassInput" required placeholder="••••" style="width: 100%;">
                            <i class="fas fa-check-circle validation-icon valid" id="matchValid"></i>
                            <i class="fas fa-times-circle validation-icon invalid" id="matchInvalid"></i>
                        </div>
                    </div>
                </div>
                
                <div class="password-requirements">
                    <div class="req-item" id="req-len"><i class="fas fa-circle"></i> 8+ Characters</div>
                    <div class="req-item" id="req-upper"><i class="fas fa-circle"></i> Uppercase Letter</div>
                    <div class="req-item" id="req-num"><i class="fas fa-circle"></i> Number</div>
                    <div class="req-item" id="req-spec"><i class="fas fa-circle"></i> Special Char</div>
                </div>

                <button type="submit" id="submitBtn" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1rem; background: var(--accent-vibrant); border: none;">Register Now</button>
            </form>
            
            <p style="text-align: center; margin-top: 30px; font-size: 0.9rem; color: rgba(255,255,255,0.5);">
                Member? <a href="login.php" style="color: var(--white); font-weight: 700; border-bottom: 1px solid var(--accent-color);">Sign In</a>
            </p>
        </div>
    </div>

    <script>
        const emailInput = document.getElementById('emailInput');
        const emailValid = document.getElementById('emailValid');
        const emailInvalid = document.getElementById('emailInvalid');
        const emailFeedback = document.getElementById('emailFeedback');
        
        const passwordInput = document.getElementById('passwordInput');
        const confirmPassInput = document.getElementById('confirmPassInput');
        const passValid = document.getElementById('passValid');
        const matchValid = document.getElementById('matchValid');
        const matchInvalid = document.getElementById('matchInvalid');
        const submitBtn = document.getElementById('submitBtn');

        let isEmailValid = false;
        let isPassValid = false;
        let isMatchValid = false;
        let emailTimeout = null;

        // Email Auto Detection
        emailInput.addEventListener('keyup', function() {
            clearTimeout(emailTimeout);
            const email = this.value;
            
            // Reset UI
            emailValid.style.display = 'none';
            emailInvalid.style.display = 'none';
            emailFeedback.className = 'validation-message';
            emailFeedback.innerText = '';
            
            if (email.length === 0) return;

            // Regex Check
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                emailInvalid.style.display = 'block';
                emailFeedback.innerText = 'Invalid email format';
                emailFeedback.classList.add('error');
                isEmailValid = false;
                return;
            }

            // AJAX Check
            emailFeedback.innerText = 'Checking availability...';
            emailFeedback.style.display = 'block';
            emailFeedback.style.color = '#fff';

            emailTimeout = setTimeout(() => {
                const formData = new FormData();
                formData.append('email', email);

                fetch('check_email.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'available') {
                        emailValid.style.display = 'block';
                        emailInvalid.style.display = 'none';
                        emailFeedback.innerText = '';
                        isEmailValid = true;
                    } else {
                        emailValid.style.display = 'none';
                        emailInvalid.style.display = 'block';
                        emailFeedback.innerText = data.message;
                        emailFeedback.classList.add('error');
                        isEmailValid = false;
                    }
                })
                .catch(err => {
                    console.error('Error checking email', err);
                });
            }, 500); 
        });

        // Password Strength Detection
        passwordInput.addEventListener('keyup', function() {
            const val = this.value;
            const reqLen = document.getElementById('req-len');
            const reqUpper = document.getElementById('req-upper');
            const reqNum = document.getElementById('req-num');
            const reqSpec = document.getElementById('req-spec');

            const hasLen = val.length >= 8;
            const hasUpper = /[A-Z]/.test(val);
            const hasNum = /[0-9]/.test(val);
            const hasSpec = /[^A-Za-z0-9]/.test(val);

            // Update UI
            toggleReq(reqLen, hasLen);
            toggleReq(reqUpper, hasUpper);
            toggleReq(reqNum, hasNum);
            toggleReq(reqSpec, hasSpec);

            if (hasLen && hasUpper && hasNum && hasSpec) {
                passValid.style.display = 'block';
                isPassValid = true;
            } else {
                passValid.style.display = 'none';
                isPassValid = false;
            }
            
            checkMatch();
        });

        confirmPassInput.addEventListener('keyup', checkMatch);

        function checkMatch() {
            if (confirmPassInput.value.length === 0) {
                matchValid.style.display = 'none';
                matchInvalid.style.display = 'none';
                return;
            }

            if (confirmPassInput.value === passwordInput.value && isPassValid) {
                matchValid.style.display = 'block';
                matchInvalid.style.display = 'none';
                isMatchValid = true;
            } else {
                matchValid.style.display = 'none';
                matchInvalid.style.display = 'block';
                isMatchValid = false;
            }
        }

        function toggleReq(el, met) {
            if (met) {
                el.classList.add('met');
                el.querySelector('i').className = 'fas fa-check';
            } else {
                el.classList.remove('met');
                el.querySelector('i').className = 'fas fa-circle';
            }
        }

        // Password Toggle Logic
        const togglePass = document.getElementById('togglePass');
        const toggleConfirmPass = document.getElementById('toggleConfirmPass');

        togglePass.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        toggleConfirmPass.addEventListener('click', function() {
            const type = confirmPassInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Prevent submission if invalid
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            if (!isEmailValid || !isPassValid || !isMatchValid) {
                e.preventDefault();
                alert('Please fix the errors before submitting.');
            } else {
                submitBtn.classList.add('btn-loading');
                submitBtn.innerHTML = 'Creating Account...';
            }
        });
    </script>
</body>
</html>
