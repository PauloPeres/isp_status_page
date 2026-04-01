# Login Flow Improvements — Multi-Perspective Audit

> Date: 2026-03-31
> Sources: UX/UI Designer, QA Tester, Security Engineer

## Summary

| Priority | Count | Key Themes |
|----------|-------|-----------|
| CRITICAL | 3 | Brute force protection, tokens in URL, refresh token in localStorage |
| HIGH | 6 | Forgot password, password toggle, 2FA UX, register flow, password policy, JWT refresh race |
| MEDIUM | 7 | Remember me, strength indicator, deprecated API, guard check, OAuth errors, throttle TTL, Enter key |
| LOW | 5 | Terms page, email verification, JSON.parse safety, vague errors, super-admin claim |

## CRITICAL

### 1. Security: No brute force protection on API login
- LoginThrottleService exists but is never called from AuthController::login()
- Unlimited password attempts possible
- Fix: Call isLocked/recordFailure in login method

### 2. Security: OAuth tokens passed in URL fragment
- Redirect: /app/oauth-callback#access_token=...&refresh_token=...
- Visible in browser history, Referer headers, extensions
- Fix: Use one-time auth code exchange instead

### 3. Security: Refresh token stored in localStorage (XSS vulnerable)
- All tokens in localStorage — any XSS steals 7-day refresh token
- Fix: Move refresh token to HttpOnly cookie

## HIGH

### 4. UX/QA: No "Forgot Password" flow
- No reset link, no endpoint, no flow
- Users locked out permanently
- Fix: Add forgot-password page + reset token email + reset endpoint

### 5. UX: No password visibility toggle
- Can't see what was typed, causes failed logins on mobile
- Fix: Add eye/eye-off icon toggle on password fields

### 6. UX: 2FA prompt shows as error (red) instead of info
- "Enter your 2FA code" displayed in danger color
- Fix: Use separate info message with neutral/blue styling, auto-focus input

### 7. QA: Register bypasses AuthService for token storage
- Writes directly to localStorage + window.location.href
- AuthService signals not updated
- Fix: Use AuthService.setTokens() + router.navigate()

### 8. Security: No password complexity beyond 8 chars
- "aaaaaaaa" is valid — no uppercase/number/special requirement
- Fix: Add server-side complexity rules + client-side strength indicator

### 9. QA: JWT interceptor refresh race condition
- Multiple 401s trigger multiple refresh attempts simultaneously
- Token rotation means only first succeeds, rest fail → logout
- Fix: Add refresh lock (shared Promise/BehaviorSubject)

## MEDIUM

### 10. UX: No "Remember me" option
### 11. UX: No password strength indicator on registration
### 12. QA: toPromise() deprecated (use firstValueFrom)
### 13. QA: Auth guard doesn't check token expiry
### 14. UX: OAuth failure not displayed on login page
### 15. Security: LoginThrottleService cache TTL not enforced
### 16. UX: Confirm password Enter key doesn't submit
### 17. QA: OAuth code duplicated in login + register

## LOW

### 18. UX: Terms of Service page status
### 19. Security: email_verified set to true without verification
### 20. QA: JSON.parse without try/catch in AuthService
### 21. UX: "Login failed" error too vague
### 22. Security: is_super_admin JWT claim trust
