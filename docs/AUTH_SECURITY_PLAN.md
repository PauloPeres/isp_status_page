# Authentication & Security Improvement Plan

## Critical Security Fixes
- TASK-AUTH-001: Fix mass assignment on User entity (is_super_admin, role, etc.) - COMPLETED
- TASK-AUTH-002: Add OAuth state parameter (CSRF protection) - COMPLETED
- TASK-AUTH-003: Remove hardcoded security salt, use env-only
- TASK-AUTH-004: Remove hardcoded DB credentials from docker-compose

## High Priority Security
- TASK-AUTH-005: Brute force protection on login (rate limiting) - COMPLETED
- TASK-AUTH-006: Session regeneration after login - COMPLETED
- TASK-AUTH-007: Secure cookie flags (httpOnly, secure, sameSite) - COMPLETED
- TASK-AUTH-008: Security headers middleware - COMPLETED
- TASK-AUTH-009: Fix open redirect in login - COMPLETED
- TASK-AUTH-010: Require current password in profile edit - COMPLETED

## High Priority CX
- TASK-AUTH-011: Remove default credentials from login page - COMPLETED
- TASK-AUTH-012: Add "Remember me" checkbox - COMPLETED
- TASK-AUTH-013: Add "Resend verification email" - COMPLETED
- TASK-AUTH-014: Add Terms of Service checkbox to registration - COMPLETED
- TASK-AUTH-015: Show password requirements on registration - COMPLETED
- TASK-AUTH-016: Per-field validation errors on registration - COMPLETED

## Two-Factor Authentication
- TASK-AUTH-MFA: Two-Factor Authentication (2FA/TOTP) - COMPLETED

## Medium Priority
- TASK-AUTH-017: Enforce email verification before access - COMPLETED
- TASK-AUTH-018: Security audit logging table
- TASK-AUTH-019: Fix OAuth account linking (require password)
- TASK-AUTH-020: Remove sensitive tokens from logs - COMPLETED
- TASK-AUTH-021: Standardize auth page languages to English with __d()
