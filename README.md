# Mars Haven

Mars Haven is a role based PHP and MySQL web app that simulates monitoring a Mars habitat during solar storm conditions.

The project was built as a full-stack application: simple architecture, clear dashboards, and realistic logging flows for storm, radiation, power, and emergency events.

## What it does???

- Lets admins log new solar storm events.
- Auto-generates related radiation and power records from storm intensity.
- Shows live  updates for astronauts and users through periodic dashboard refresh.
- Tracks emergency events when thresholds are crossed.

## Roles and dashboards

### Admin
- Logs storm intensity and description.
- Triggers derived radiation status 
- Triggers power state 
- Can see recently logged storm records.

### Astronaut
- Sees latest radiation monitoring details.
- Sees recent power system logs.
- Gets system health output and warning messages.
- Dashboard auto refreshes every 7 seconds.

### User
- Sees latest storm, radiation, and power snapshots.
- Dashboard auto-refreshes every 15 seconds.

## Tech stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP 
- Database: MySQL
- Local environment: XAMPP or visit: [This site](https://marshaven.byethost8.com)


## How to run locally (XAMPP)

1. Clone or copy this project into your XAMPP htdocs folder.
2. Start Apache and MySQL from XAMPP.
3. Open phpMyAdmin and import `database/schema.sql`.
4. Confirm DB connection in `config/db.php`.
5. Open in browser:`http://localhost/mars-haven/login.php`

## Demo login credentials

You can log in using either username or mail on the login page.

- Admin
Username: `admin`
Email: `admin@marshaven.local`
Password: `admin123`
- Astronaut
Username: `astronaut`
Email: `astro@marshaven.local`
Password: `astro123`
- User
Username: `user`
Email: `user@marshaven.local`
Password: `user123`

- These are demo credentials for project/testing use.

## note

This project is built as a practical full-stack learning exercise combining backend logic, database modeling, and role-based UI behavior in a single application.