# CampusTutor — A Peer Tutoring System

CampusTutor is an intra-university peer tutoring platform that connects students with tutors verified through academic performance. Only students who earned an A or A+ in a course are eligible to tutor it, subject to final admin approval. The platform supports session booking, anonymous reviews, study groups, professor recommendations, and exam revision sessions — all through role-based dashboards for students, tutors, professors, and admins.

Built as a DBMS course project (CSE370) using PHP and MySQL.

## Features

- **Role-based dashboards** — separate views and permissions for students, tutors, professors, and admins
- **Tutor verification** — students become eligible tutors only after earning A/A+ in a course, pending admin approval
- **Session booking** — students can browse verified tutors and book sessions based on availability
- **Anonymous reviews** — one review per tutor per student
- **Study groups** — students can create or join groups with peers sharing similar academic weaknesses
- **Professor recommendations** — professors can recommend high-performing tutors
- **Admin panel** — manages tutor eligibility and organizes exam revision sessions

## Tech Stack

- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML/CSS
- **Local environment:** XAMPP (Apache + MySQL)


## Setup & Installation

1. **Install XAMPP** 

2. **Clone the repo** into your `htdocs` folder:
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs   # Mac
   # or C:\xampp\htdocs                        # Windows
   git clone https://github.com/shairaziz/CampusTutor-Peer-Tutoring-System.git
   ```

3. **Start Apache and MySQL** from the XAMPP control panel.

4. **Import the database:**
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Create a new database (e.g. `campusTutor`)
   - Go to the **Import** tab and select the `.sql` file from the `sql/` folder
   - Run the import

5. **Configure the database connection:**
   - Open `dbconnect.php`
   - Update the credentials to match your local setup:
     ```php
     $conn = mysqli_connect("localhost", "root", "", "campustutor");
     ```

6. **Run the project:**
   - Visit `http://localhost/CampusTutor-Peer-Tutoring-System/` in your browser

## Database Design

Schema diagrams, EER diagrams, and normalization documentation are available in the `Schema/`, `EER/`, and `Normalized Schema/` folders for reference.

## Notes

- This project was built for academic purposes as part of CSE370 
- Default XAMPP MySQL credentials (`root` with no password) are assumed unless configured otherwise.

## Contributors

- **Shaira Binte Aziz** — [GitHub](https://github.com/shairaziz)
- **Md. Redowan Ibne Azam**
- **Humaira Tasnim** 
