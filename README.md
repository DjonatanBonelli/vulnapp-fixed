# VulnApp - AppSec Technical Assessment

## About the Project

This application is a PHP-based system created exclusively for **evaluating technical knowledge in code analysis and vulnerability remediation**.

⚠️ **IMPORTANT WARNING:** This system contains intentionally introduced security flaws and vulnerabilities. **DO NOT** use, host, or expose it in production environments.

## Objective

The project serves as a practical environment for Application Security (AppSec) testing. The candidate must analyze the source code to:
1. **Identify vulnerabilities** (e.g., Injection, XSS, Path Traversal, authentication flaws, etc.).
2. **Classify severity** and explain the risks and impacts of each flaw.
3. **Demonstrate exploitation** (PoC - Proof of Concept) of the found vulnerabilities.
4. **Propose solutions and fix the code** by applying secure development best practices.

## Directory Structure

- `controllers/` - Application controllers (Authentication, Reports, User Profile).
- `models/` - Data representation and persistence models.
- `services/` - Auxiliary services (File upload, Batch processing).
- `utils/` - Utility classes and security functions (intentionally flawed).
- `config/` - Configuration files.
