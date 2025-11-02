# eternatech-repair-tracker
A Hardware Repair Tracking System developed as a first-year, first-semester university project. Built with PHP using a classic mini-MVC architecture, and a vanilla HTML, CSS, &amp; JavaScript front end.
# EternaTech Repair Tracker ⚙️

![EternaTech](https://img.shields.io/badge/Project-EternaTech%20Repair%20Tracker-blue?style=for-the-badge&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iI2ZmZmZmZiI+PHBhdGggZD0iTTE5LjM1IDguMDNBOC45OSA4Ljk5IDAgMCAwIDEyIDUgOC45OSA4Ljk5IDAgMCAwIDQuNjUgOC4wMyA3IDcgMCAwIDAgNSAxOS45MmMuMzYuMDQuNzEuMDguMS4wOGgyLjE0Yy4wNSAwIC4xLS4wMi4xMy0uMDZsMS4zMy0xLjg5Yy4wOC0uMTEuMDgtLjI2IDAtLjM4bC0xLjMxLTEuODZjLS4wMy0uMDQtLjA4LS4wNi0uMTItLjA2SDUuMTFhNSA1IDAgMCAxIDQuOTEtMy45OSA1LjAwMiA1LjAwMiAwIDAgMSAzLjk4IDAgNSA1IDAgMCAxIDQuOTEgMy45OWgtMS44M2MtLjA1IDAtLjA5LjAyLS4xMi4wNkwxNS43MiAxNy43Yy0uMDguMTEtLjA4LjI2IDAgLjM4bDEuMzMgMS44OWMuMDQuMDQuMDguMDYuMTMuMDZoMi4xNEMxOC4yOSAyMCAxOC42NCAyMCAxOSA MTkuOTJhNyA3IDAgMCAwLTEuNjUtMTEuODlaTTggMTYuNWExLjUgMS41IDAgMSAwIDAgMyAxLjUgMS41IDAgMSAwIDAtM1ptOCAwYTEuNSAxLjUgMCAxIDAgMCAzIDEuNSAxLjUgMCAxIDAgMC0zaCIvPjwvc3ZnPg==)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)

A web-based application built with PHP for managing and tracking hardware repair jobs. This was developed as a group project for our first-year, first-semester studies.

## 📖 Overview

EternaTech Repair Tracker is a PHP-based web application designed to help small repair shops or technicians manage the entire lifecycle of a repair. It allows for tracking customers, the items they bring in, the status of the repairs, and managing user accounts for technicians.

## 🛠️ Features

* **Secure User Authentication:** Secure login and session management for administrators and technicians.
* **Dashboard:** A central hub to view key statistics (e.g., pending repairs, completed jobs).
* **Customer Management (CRM):** Add, view, update, and delete customer information.
* **Item Management:** Register new devices/items brought in for repair, linking them to a customer.
* **Repair Job Tracking:**
    * Create new repair jobs with details (issue description, assigned technician).
    * Update the status of a repair (e.g., "Pending," "In Progress," "Completed," "Cannot Be Repaired").
    * View a complete history of all repair jobs.
* **User Management (Admin):** Admins can create, update, and manage technician accounts.

## 🧑‍💻 User Roles & Functionality

The system is designed around three key roles to ensure a smooth and transparent repair process:

### 1. Client
* Report device issues by submitting a repair request.
* Provide necessary details about the device and the problem.
* Track the progress of the repair at each stage.
* Communicate with the technician or admin if needed.
* Confirm when the repair is completed and provide feedback.

### 2. Technician
* View all repair tasks assigned to them.
* Diagnose and repair the devices as per the request.
* Update the status of each task in the system (e.g., in progress, completed).
* Communicate with clients if additional information or clarification is needed.
* Ensure timely completion of assigned repair tasks.

### 3. Administrator
* Manage client and technician profiles (add, edit, or delete accounts).
* Assign repair tasks to technicians efficiently.
* Monitor the status of all repair requests.
* Handle escalations or issues that arise during the repair process.
* Ensure smooth workflow and communication between clients and technicians.
  
## 💻 Technologies Used

* **Backend:** **PHP**
* **Database:** **MySQL**
* **Frontend:** **HTML5**, **CSS3**, **JavaScript**
* **Local Server:** XAMPP 

## Screenshots

**Login Page**
<img width="1031" height="537" alt="Screenshot 2025-09-06 at 02 03 54" src="https://github.com/user-attachments/assets/88b9533e-3837-4081-9113-9ab4b4fbe043" />
**Admin Dashboard**
<img width="1443" height="913" alt="Screenshot 2025-10-30 at 18 09 08" src="https://github.com/user-attachments/assets/73f4cb03-3753-4db6-a9bb-21332becccd8" />
<img width="1470" height="956" alt="Screenshot 2025-09-06 at 00 19 54" src="https://github.com/user-attachments/assets/25af5b19-be88-433f-ac47-d785977bab39" />

**Bill Management**
<img width="1470" height="956" alt="Screenshot 2025-09-06 at 00 20 37" src="https://github.com/user-attachments/assets/f6a81ed7-a764-451b-9397-10f1ff410c0e" />

**User Management**
<img width="1470" height="956" alt="Screenshot 2025-10-30 at 18 09 24" src="https://github.com/user-attachments/assets/ac3ba971-7bcd-4e71-87e3-dc68dfcc3233" />
**Parts Management**
<img width="1470" height="956" alt="Screenshot 2025-10-30 at 18 10 30" src="https://github.com/user-attachments/assets/c6ccac11-282b-4ab5-b006-ba67b5734177" />
<img width="1470" height="956" alt="Screenshot 2025-10-30 at 18 10 36" src="https://github.com/user-attachments/assets/7bcf1306-4336-4d59-862e-5c2cc89fcf24" />
**Technician Dashboard**
<img width="1470" height="956" alt="Screenshot 2025-10-30 at 18 12 14" src="https://github.com/user-attachments/assets/7978b1a8-22a0-4d2a-927c-7056a67eef2c" />
**Customer Dashboard**
<img width="1470" height="956" alt="Screenshot 2025-10-30 at 18 12 59" src="https://github.com/user-attachments/assets/273b3804-1229-45ed-b188-b1b2e416fb2b" />
<img width="342" height="730" alt="Screenshot 2025-09-06 at 02 02 24" src="https://github.com/user-attachments/assets/e77488ee-7f86-4550-90b4-7a80abfbbe94" />
**Help & Support**
<img width="1470" height="956" alt="Screenshot 2025-09-06 at 00 39 28" src="https://github.com/user-attachments/assets/addab64f-c5c9-4d3c-b8e2-6270d3b437f8" />
**Send Msg**
<img width="1470" height="956" alt="Screenshot 2025-09-06 at 00 57 36" src="https://github.com/user-attachments/assets/1b1d9978-a08f-4ede-a066-a92b0ef6e941" />

## 🚀 Getting Started

To get a local copy up and running, follow these simple steps.

### Prerequisites

* A local web server environment like **XAMPP**
* A web browser.
* A database management tool like **phpMyAdmin** (usually included with XAMPP).

### Installation

1.  **Clone the repository:**
    ```sh
    git clone [https://github.com/bomaldesilva/eternatech-repair-tracker.git](https://github.com/bomaldesilva/eternatech-repair-tracker.git)
    ```
2.  **Move Files:**
    * Move the cloned project folder into the `htdocs` directory (for XAMPP) or `www` directory (for WAMP/MAMP).
    * Example path: `C:\xampp\htdocs\eternatech-repair-tracker`
3.  **Set up the Database:**
    * Start **Apache** and **MySQL** services from your XAMPP/WAMP control panel.
    * Open `phpMyAdmin` (usually at `http://localhost/phpmyadmin`).
    * Create a new database (e.g., `repair_shop`).
    * Import the `.sql` database file (e.g., `database.sql`) from the project folder into your new database.
4.  **Configure DB Connection:**
    * Open the project folder and find the database connection file (e.g., `config.php`).
    * Update the database name, username (usually "root"), and password (usually empty) to match your setup.
5.  **Run the Application:**
    * Open your web browser and navigate to the project's URL.
    * Example: `http://localhost/eternatech-repair-tracker`

## 👥 Our Team

This project was a collaborative effort by:

* **[Bomal De Silva]** 
* **[Sasindi De Silva]**
* **[Warangi De Silva]**
* **[M.A.D. Dewwandi]**
* **[Oshadha Dhananjaya]**
