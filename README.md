# Barangay Incident System
IT30C Advanced Database System – PHP + MySQL

## Setup (XAMPP/WAMP)
1. Copy this folder to `htdocs/barangay-incident-system`.
2. Start Apache + MySQL, open phpMyAdmin, **Import `database.sql`** (fresh install: DROPS and recreates the DB). To keep existing data, run `migration_v3_reporting.sql` once instead.
3. Check `config/db.php` (user/password).
4. Open http://localhost/barangay-incident-system/

## Default admin
`admin` / `Admin@123` – change it after first login.

## Demo flow
1. `index.php`: submit a report as a resident, then check the Track a Report box (opens automatically).
2. Log in as admin: the report shows as NEW under Unassigned. Assign an officer.
3. Log in as that officer (register the account first): see the case, add a follow-up, update status.

## Features
- Public **Report an Incident** page + report tracker (no login needed)
- Staff login (Admin / Officer), logout, bcrypt passwords, CSRF protection, session regeneration
- RBAC enforced server-side. Admin = oversight only (track all incidents, assign officers with an on-duty warning, history log, access control). Officers get only the accesses an admin ticks (view assigned, update status, add follow-ups, view all read-only)
- History log records every login, logout and change with user name, ID, role, time and IP
- Only admins can register accounts, so nobody can make themselves Admin

## Public page
- Hotlines are edited in `config/hotlines.php`.
- Each incident type maps to a Service; officers are tagged with the services they handle (Access Control page). The admin assign list recommends matching officers first.
- Staff log in at `login.php` (separate from the public page).
