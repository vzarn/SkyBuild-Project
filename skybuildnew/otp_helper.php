<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generates a cryptographically secure 6-digit numeric OTP.
 * Stuffed with leading zeros if necessary.
 */
function generateOTP($expiryMinutes = 10) {
    // random_int is cryptographically secure, unlike rand() or mt_rand()
    $otp = str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);
    
    // Store hashed OTP in session alongside its expiration time
    $_SESSION['otp_hash'] = password_hash($otp, PASSWORD_DEFAULT);
    $_SESSION['otp_expires'] = time() + ($expiryMinutes * 60);
    $_SESSION['otp_attempts'] = 0; // Track attempts to prevent brute force
    
    return $otp; 
}

/**
 * Validates the user-submitted OTP against the session data.
 */
function verifyOTP($userOtp) {
    // 1. Check if an OTP was even requested
    if (!isset($_SESSION['otp_hash']) || !isset($_SESSION['otp_expires'])) {
        return ['status' => false, 'message' => 'No OTP request found. Please request a new one.'];
    }

    // 2. Check expiration time
    if (time() > $_SESSION['otp_expires']) {
        clearOTPSession();
        return ['status' => false, 'message' => 'OTP has expired. Please request a new one.'];
    }

    // 3. Prevent brute-force guessing (Limit to 3 attempts)
    $_SESSION['otp_attempts']++;
    if ($_SESSION['otp_attempts'] > 3) {
        clearOTPSession();
        return ['status' => false, 'message' => 'Too many failed attempts. Please request a new OTP.'];
    }

    // 4. Verify code format (must be 6 digits)
    if (!preg_match('/^[0-9]{6}$/', $userOtp)) {
        return ['status' => false, 'message' => 'Invalid OTP format. It must be 6 digits.'];
    }

    // 5. Verify the actual OTP code
    if (password_verify($userOtp, $_SESSION['otp_hash'])) {
        clearOTPSession(); // Burn the OTP immediately after successful use
        return ['status' => true, 'message' => 'Verification successful!'];
    }

    return ['status' => false, 'message' => 'Incorrect OTP code. Try again.'];
}

function clearOTPSession() {
    unset($_SESSION['otp_hash']);
    unset($_SESSION['otp_expires']);
    unset($_SESSION['otp_attempts']);
}
