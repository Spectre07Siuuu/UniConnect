<?php

function ensureSessionStarted(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function redirectTo(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function setFlashMessage(string $key, string $message): void
{
    ensureSessionStarted();
    $_SESSION[$key] = $message;
}

function setLoginError(string $message): void
{
    setFlashMessage('login_error', $message);
}

function setSignupSuccess(): void
{
    ensureSessionStarted();
    $_SESSION['signup_success'] = true;
}
