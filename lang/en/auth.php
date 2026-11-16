<?php

return [
    // framework keys
    'failed' => 'These credentials do not match our records, or the account is unavailable.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many attempts. Try again in :seconds seconds.',

    // screens
    'login_title' => 'Welcome back',
    'login_sub' => 'Sign in to manage your home services.',
    'register_title' => 'Create your account',
    'register_sub' => 'Join as a customer or a home-service professional.',
    'email' => 'Email',
    'password_label' => 'Password',
    'password_confirm' => 'Confirm password',
    'full_name' => 'Full name',
    'phone' => 'Phone',
    'phone_hint' => 'Digits only, with an optional + prefix.',
    'remember_me' => 'Keep me signed in',
    'show' => 'Show',
    'hide' => 'Hide',
    'sign_in' => 'Sign in',
    'create_account' => 'Create account',
    'no_account' => 'No account?',
    'have_account' => 'Already have an account?',
    'account_type' => 'Account type',
    'as_customer' => 'Customer',
    'as_worker' => 'Professional',

    // password reset & account recovery
    'forgot_title' => 'Reset your password',
    'forgot_sub' => 'Enter your account email and we will send a reset link.',
    'forgot_link' => 'Forgot password?',
    'send_reset_link' => 'Email password reset link',
    'reset_link_sent' => 'If that email matches an account, a reset link is on its way.',
    'reset_title' => 'Choose a new password',
    'reset_sub' => 'Set a new password for your account.',
    'new_password' => 'New password',
    'reset_button' => 'Reset password',
    'reset_success' => 'Your password has been reset. Please sign in.',
    'back_to_login' => 'Back to sign in',

    // Email verification
    'verify_email' => [
        'heading' => 'Confirm your email address',
        'body' => 'We have sent a verification link to :email. Open it to confirm your address — you can keep browsing in the meantime, but booking, payments, accepting a quotation, leaving a review and reporting an issue need a confirmed email.',
        'resend' => 'Resend the verification email',
        'resend_hint' => 'Not received it? Check your spam folder, or request a new link.',
        'logout' => 'Sign out',
        'done' => 'Your email address is confirmed. Thank you!',
        'resent' => 'A fresh verification link is on its way.',
        'mail_subject' => 'Confirm your SALE7LI email address',
        'mail_intro' => 'Please confirm your email address to finish setting up your SALE7LI account.',
        'mail_action' => 'Confirm email address',
        'mail_outro' => 'If you did not create a SALE7LI account, no further action is required.',
    ],

    // Suspended account
    'suspended_title' => 'Your account is on hold',
    'suspended_body' => 'This account has been suspended, so you cannot sign in right now. If you think this is a mistake, contact our support team and we will look into it.',
    'suspended_contact' => 'Contact support at :email',
    'suspended_back' => 'Back to sign in',
];
