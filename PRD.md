# Product Requirements Document (PRD)
## DHMR (Detect Human Manipulation and Rape) - MVP Simulation

**Version:** 1.0 (10-Hour Sprint Edition)
**Objective:** Build a functional, web-based "Wizard of Oz" simulation to demonstrate the DHMR silent SOS system, AI risk scoring, and real-time dashboard monitoring for a live presentation.

---

## 1. Tech Stack
To minimize overhead and ensure rapid deployment within the 10-hour window, the stack is stripped down to raw essentials:
* **Backend:** Vanilla PHP 8+ (No frameworks, raw routing)
* **Database:** SQLite (for zero-config setup) or MySQL using PDO
* **Frontend (Simulator & Admin):** Vanilla JavaScript, HTML5
* **Styling:** Tailwind CSS (via CDN)
* **Mapping:** Leaflet.js with OpenStreetMap (No API keys required)

---

## 2. System Architecture

The MVP consists of three isolated components:

### A. The "Wearable" Simulator (Mobile Web View)
* **Purpose:** Acts as the physical hardware trigger.
* **UI:** A dark, minimalist interface simulating a locked or blank phone screen to demonstrate discreet activation.
* **Function:** Uses `navigator.geolocation` to grab real-time GPS coordinates and sends a silent POST payload to the backend.

### B. The Core API & Risk Engine (PHP Backend)
* **Purpose:** Receives data, assesses risk, and stores alerts.
* **Function:** A set of standalone PHP files handling database connections, data insertion, and a rule-based algorithm that calculates a 0-100 risk score based on time (WAT) and location.

### C. The Responder Dashboard (Admin Web View)
* **Purpose:** The presentation screen showing real-time emergency monitoring.
* **UI:** A split-screen layout with an active alert sidebar and a live map.
* **Function:** Polls the backend every 3 seconds for new alerts, drops red pins on the map, and allows admins to mark alerts as "resolved."

---

## 3. Database Schema

**Table 1: `users`**
* `id` (INT, Primary Key, Auto Increment)
* `name` (VARCHAR)
* `trusted_contact_phone` (VARCHAR)

**Table 2: `alerts`**
* `id` (INT, Primary Key, Auto Increment)
* `user_id` (INT, Foreign Key to users.id)
* `latitude` (DECIMAL 10,8)
* `longitude` (DECIMAL 11,8)
* `risk_score` (INT) - Range 0 to 100
* `status` (VARCHAR) - Default: 'active', can be 'resolved'
* `created_at` (TIMESTAMP) - Default: CURRENT_TIMESTAMP

---

## 4. API Endpoints

### Endpoint 1: Trigger Alert
* **Path:** `/api/trigger_alert.php`
* **Method:** `POST`
* **Payload:** `{"user_id": 1, "lat": 6.5244, "lng": 3.3792}`
* **Action:** 1. Runs the Risk Assessment Engine.
    2. Inserts a new row into the `alerts` table.
* **Response:** `{"success": true, "alert_id": X, "risk_score": Y}`

### Endpoint 2: Get Active Alerts
* **Path:** `/api/get_active_alerts.php`
* **Method:** `GET`
* **Action:** Fetches all alerts where `status = 'active'`.
* **Response:** `[{"id": 1, "lat": 6.5244, "lng": 3.3792, "risk_score": 85, "created_at": "..."}]`

### Endpoint 3: Resolve Alert
* **Path:** `/api/resolve_alert.php`
* **Method:** `POST`
* **Payload:** `{"alert_id": 1}`
* **Action:** Updates `status` to 'resolved' for the given ID.
* **Response:** `{"success": true}`

---

## 5. Risk Assessment Engine (Logic)

The engine calculates a score out of 100 based on the following hardcoded rules:
* **Base Score:** 20 points.
* **Time Factor (WAT):** If `created_at` is between 22:00 (10 PM) and 05:00 (5 AM) West Africa Time, add **40 points**.
* **Location Factor:** Define a hardcoded "Safe Zone" coordinate (e.g., the presentation venue or user's home). Use the Haversine formula to calculate the distance from the incoming coordinates. If distance > 5km, add **40 points**.

---

## 6. Execution Phases (For Gemini CLI)

Feed these phases sequentially to your CLI tool to build the app:

* **Phase 1: Database Setup:** "Generate a `database.php` file using PDO for SQLite (or MySQL) that creates the `users` and `alerts` tables if they don't exist, and inserts one dummy user."
* **Phase 2: Core API & Risk Engine:** "Generate `api/trigger_alert.php`. It must accept a JSON POST request with lat/lng, calculate the risk score based on the Time Factor (WAT) and Location Factor (Haversine formula from a set coordinate in Nigeria), and insert the record into the database."
* **Phase 3: The Simulator UI:** "Generate `simulator.html`. Use Tailwind CSS to create a pitch-black screen with one invisible but clickable div spanning the whole screen. On click, use `navigator.geolocation` to get the user's location, `fetch()` POST it to `api/trigger_alert.php`, and visually swap the screen to a fake lock screen image."
* **Phase 4: Admin API:** "Generate `api/get_active_alerts.php` and `api/resolve_alert.php` to handle fetching and resolving database records."
* **Phase 5: Admin Dashboard UI:** "Generate `admin.html`. Include Leaflet.js and Tailwind CSS. Create a sidebar for incoming alerts and a main div for the map. Write JS to `setInterval` every 3 seconds to fetch active alerts, plot them as red pins on the map, list them in the sidebar with a 'Resolve' button, and trigger a flashing red CSS animation on new alerts."