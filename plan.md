# Development Prompt — Mini Banking Cybersecurity Web Application

You are an experienced full-stack PHP/MySQL developer and application security engineer.

I am developing an **academic cybersecurity project**: a fictional mini banking website that will eventually be used to demonstrate three web application vulnerabilities in a controlled environment:

1. **Brute Force** — login functionality
2. **SQL Injection** — transaction search functionality
3. **CSRF** — profile/account settings functionality

The application will use:

* **PHP**
* **MySQL**
* **HTML**
* **CSS**
* **JavaScript**
* **Hostinger** for final deployment

The website must be a **normal, functional banking website first**. Do NOT create a separate "Security Lab", "Attack Simulator", "Security Testing Dashboard", fake attack buttons, or artificial vulnerability screens.

The vulnerabilities will later be introduced into the existing website functions themselves for controlled academic testing.

---

# PROJECT CONCEPT

Create a fictional bank called:

**Aurelia Bank**

The website should look and behave like a small legitimate online banking platform.

The application should contain:

### Customer functionality

* Login
* Logout
* Customer dashboard
* Account information
* Account balance
* Recent transactions
* Transaction history
* Transaction search/filtering
* Transaction details
* Profile
* Account settings

### Administrator functionality

* Admin login
* Admin dashboard
* View customers
* View customer accounts
* View transactions
* Basic customer management

Do not implement unnecessary banking functionality such as real payments, real money transfers, external banking APIs, real payment gateways, or real financial data.

All data must be fictional/test data.

---

# IMPORTANT DEVELOPMENT PRINCIPLE

Build the project in phases.

Do NOT attempt to generate the entire application in one response.

Start with **Phase 1 only**.

After completing each phase, explain:

* What was created
* Which files were created/modified
* Database changes
* How the code works
* How I can test it
* Any assumptions made
* Any remaining tasks

Then wait for me to tell you to continue to the next phase.

---

# PHASE STRUCTURE

Use this development roadmap:

## Phase 1 — Project Foundation

Set up:

* Project directory structure
* PHP application structure
* MySQL database structure
* Database connection
* Configuration system
* Common header/footer
* CSS foundation
* JavaScript foundation
* Basic routing/navigation approach
* Error handling approach
* Session foundation
* `.htaccess` if appropriate for the hosting environment
* README/documentation

Create a clean and maintainable architecture suitable for Hostinger shared hosting.

Do not introduce vulnerabilities yet.

---

## Phase 2 — Authentication

Implement the actual:

* Login page
* Login processing
* Logout
* PHP sessions
* Password hashing
* Authentication middleware/checks
* Customer/admin role separation
* Login validation
* Appropriate error messages

The login system must be designed so that the **Brute Force demonstration can later target this real login functionality**.

Do not create a separate brute-force testing page.

---

## Phase 3 — Customer Dashboard

Implement:

* Customer dashboard
* Account summary
* Available balance
* Account number display
* Recent transactions
* Navigation
* Customer-specific data loading

Use database-driven data.

Customers must only be able to access their own account information.

---

## Phase 4 — Transaction System

Implement:

* Transaction history
* Transaction search
* Transaction filtering
* Pagination
* Transaction details
* Date filtering where appropriate
* Transaction type filtering
* Database queries

The transaction search functionality should later become the controlled **SQL Injection demonstration point**.

For safety, SQL Injection testing should be limited to the transaction-search functionality and fictional database data.

Do not create a special SQL Injection interface.

---

## Phase 5 — Profile & Account Settings

Implement:

* View profile
* Edit profile
* Account settings
* Server-side validation
* Client-side validation
* Update processing
* Success/error messages

This functionality should later become the controlled **CSRF demonstration point**.

Do not use financial transactions as the CSRF target.

---

## Phase 6 — Administrator Area

Implement:

* Admin dashboard
* Customer list
* Customer details
* Account information
* Transaction overview
* Basic management functionality

Keep the administrator area simple and appropriate for a mini banking application.

---

# PHASE 7 — Controlled Vulnerability Implementation

Only after the normal website is complete, introduce controlled vulnerabilities into the existing functionality.

### Vulnerability 1 — Brute Force

Target:

`login.php`

or the equivalent login endpoint.

The vulnerable version should demonstrate insufficient login-attempt protection.

Use only fictional test accounts.

Later create the hardened version using appropriate protections such as:

* Rate limiting
* Login attempt tracking
* Temporary lockout/delay
* Appropriate authentication controls

---

### Vulnerability 2 — SQL Injection

Target:

Transaction search.

The vulnerable version should demonstrate unsafe handling of user-controlled input in a database query.

Use only fictional transaction data.

Later remediate it using:

* Prepared statements
* Parameterized queries
* Proper input handling
* Appropriate database permissions

Do not create SQL Injection functionality involving money transfers or destructive database operations.

---

### Vulnerability 3 — CSRF

Target:

Profile/account settings update.

The vulnerable version should demonstrate an authenticated state-changing request without appropriate CSRF protection.

Use a harmless profile/settings change as the demonstration.

Later remediate it using:

* CSRF tokens
* Server-side token validation
* Appropriate SameSite cookie configuration
* Other relevant session protections

Do not use financial transfers or password changes as the CSRF demonstration target.

---

# PHASE 8 — Security Testing

After the vulnerabilities have been implemented, document how to test each vulnerability against the actual website.

Testing must be performed only against this fictional application and authorized test environment.

For each vulnerability provide:

1. Vulnerable endpoint/function
2. Preconditions
3. Test setup
4. Testing procedure
5. Expected vulnerable behavior
6. Evidence that should be collected
7. Security impact
8. Remediation
9. Retest procedure
10. Expected secure behavior after remediation

The testing should use harmless fictional data.

---

# PHASE 9 — Security Hardening

Create hardened versions of the same application functionality.

Do not replace the vulnerable functionality with a separate security demonstration.

Instead:

**Normal Function → Vulnerable Version → Test → Remediation → Hardened Version → Retest**

Document the differences between vulnerable and hardened implementations.

---

# PHASE 10 — Final Testing

Perform functional testing and security testing.

Verify:

* Authentication
* Authorization
* Sessions
* Customer data isolation
* Admin access
* Transaction search
* Profile updates
* Input validation
* SQL queries
* CSRF protection
* Brute-force protections
* Error handling

Make sure the security fixes do not break normal application functionality.

---

# PHASE 11 — Hostinger Deployment

Prepare the final hardened version for Hostinger.

Include:

* Production configuration
* Database configuration
* Environment/configuration considerations
* File permissions
* PHP configuration considerations
* MySQL setup
* Deployment instructions
* Security checklist
* Final testing checklist

Never place real credentials inside the source code or repository.

---

# DATABASE REQUIREMENTS

Design a normalized MySQL database appropriate for the application.

At minimum consider tables such as:

* users
* accounts
* transactions

You may add additional tables if they are genuinely useful, such as:

* login_attempts
* audit_logs

However, do not over-engineer the project.

Use:

* Primary keys
* Foreign keys where appropriate
* Appropriate indexes
* Appropriate data types
* Timestamps
* Constraints where useful

Passwords must never be stored in plaintext.

Use PHP's password hashing functionality.

---

# UI/UX REQUIREMENTS

The website should look like a realistic modern banking application.

Design characteristics:

* Professional
* Clean
* Responsive
* Accessible
* Desktop-friendly
* Mobile-friendly
* Clear navigation
* Consistent typography
* Clear forms
* Useful validation messages
* Dashboard cards
* Tables for transactions
* Appropriate empty states
* Appropriate success/error states

Do not make the website look like a cybersecurity training simulator.

The user should perceive it as a normal fictional banking website.

---

# SECURITY REQUIREMENTS DURING NORMAL DEVELOPMENT

Even though vulnerabilities will eventually be demonstrated, the initial application should be developed normally and cleanly.

Use:

* Password hashing
* Prepared statements where appropriate
* Server-side validation
* Output escaping
* Session security
* Authorization checks
* Secure cookie configuration where appropriate
* Least-privilege database access
* No hardcoded secrets
* No real personal or financial information

When vulnerabilities are later introduced, clearly isolate the vulnerable code and document it so that it can be reverted.

---

# CODE QUALITY REQUIREMENTS

Write production-quality, readable code.

Follow:

* Clear naming conventions
* Separation of concerns
* Reusable functions
* Secure coding practices
* Consistent formatting
* Meaningful comments
* Minimal duplication

Do not introduce frameworks unless there is a strong reason. Prefer plain PHP/MySQL because this project is specifically intended to demonstrate how the underlying web application works.

---

# FILE STRUCTURE

Before writing Phase 1 code, propose a practical directory structure.

For example, you may use a structure similar to:

```text
aurelia-bank/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
├── includes/
├── admin/
├── customer/
├── database/
├── auth/
├── index.php
├── login.php
├── logout.php
└── README.md
```

You may modify this structure if you believe another organization is better for PHP on Hostinger.

Explain why you selected the structure.

---

# IMPORTANT RESPONSE RULES

For this first response, ONLY work on:

## PHASE 1 — PROJECT FOUNDATION

Do not implement the vulnerabilities yet.

Do not implement the complete banking system yet.

Do not skip ahead to authentication.

First:

1. Analyze the requirements.
2. Propose the architecture.
3. Propose the directory structure.
4. Design the initial MySQL schema.
5. Create the foundational files.
6. Create the database connection.
7. Create the base layout.
8. Create the initial CSS/JavaScript structure.
9. Explain how to run Phase 1 locally.
10. Explain how to verify that Phase 1 works.
11. List what will be implemented in Phase 2.

Make all code complete and directly usable.

When providing files, clearly identify each filename and provide its complete contents.

Do not leave important files as pseudocode or placeholders unless they genuinely depend on a later phase.

After Phase 1 is complete, STOP and wait for my instruction before proceeding to Phase 2.
