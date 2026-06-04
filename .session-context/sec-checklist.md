# Security Checklist — SEC-01..25 (from prompt-traceOn.md §12)
# Run this before any deploy. All 25 must pass.

SEC-01  Document root → /public only (not project root)
SEC-02  .env outside /public, not committed to git
SEC-03  HTTPS + session.cookie_secure=1 in production
SEC-04  display_errors=Off, log_errors=On in production
SEC-05  PDO prepared statements everywhere — zero raw queries
SEC-06  CSRF token in all mutations — session-scoped, rotated on login/logout
SEC-07  Bcrypt cost 12 sourced from $_ENV['BCRYPT_COST']
SEC-08  session_regenerate_id(true) after login confirmed
SEC-09  login_attempts table rate limiting active for login flow
SEC-10  IP-based cooldown for join request (reuses login_attempts pattern, 30s)
SEC-11  Avatar: MIME check (mime_content_type) + getimagesize() double-check
SEC-12  Avatar: bin2hex(random_bytes(16)) filename — NOT uniqid()
SEC-13  /public/uploads/avatars: PHP execution disabled (php_flag engine off)
SEC-14  htmlspecialchars() on all user output in Views
SEC-15  Division-by-zero guard in progress calc via ProgressCalculator
SEC-16  Transactions verified for: create workspace, delete workspace, kick, approve, revoke access, regenerate code
SEC-17  UNIQUE(workspace_id, user_id) in workspace_members confirmed in schema
SEC-18  UNIQUE(card_id, user_id) in card_access confirmed in schema
SEC-19  /api/workspace/share requires Owner/Admin auth
SEC-20  Cross-workspace card/todo validation verified across all endpoints
SEC-21  Security headers present on every response: X-Frame-Options:DENY, X-Content-Type-Options:nosniff, Referrer-Policy:strict-origin-when-cross-origin, Content-Security-Policy
SEC-22  Membership NEVER cached in session — always DB fetch per request confirmed
SEC-23  Owner cannot be kicked (backend enforced); Owner cannot change own role
SEC-24  Workspace delete: server-side name confirmation check (not just client-side)
SEC-25  Mobile responsive verified at 320px, 640px, 1024px, 1280px, 1440px

---
## CSP Header (exact value)
```
Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self'; form-action 'self'; frame-ancestors 'none'
```

Note: 'unsafe-inline' for style-src is required because CSS custom properties are applied inline by browsers.
Iconify CDN is loaded via https://cdn.jsdelivr.net — add to script-src if using CDN:
`script-src 'self' https://cdn.jsdelivr.net`
