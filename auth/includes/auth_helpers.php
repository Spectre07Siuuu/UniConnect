<?php

function ensureSessionStarted(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function generateCsrfToken(): string
{
    ensureSessionStarted();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool
{
    ensureSessionStarted();
    if (empty($_SESSION['csrf_token']) || $token === null) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function getCsrfTokenFromRequest(): ?string
{
    // Accept token from POST field or custom request header (for AJAX)
    if (!empty($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    // Normalize header names to lowercase for reliable lookup
    $headersLower = array_change_key_case($headers, CASE_LOWER);
    return $headersLower['x-csrf-token'] ?? null;
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
