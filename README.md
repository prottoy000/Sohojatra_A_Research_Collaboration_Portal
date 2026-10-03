# Sohojatra — Research Collaboration Portal

**Sohojatra** is a web-based Research Collaboration Portal developed as a **CSE370: Database Management Systems** course project at **BRAC University**.

The platform is designed to help **students and faculty organize and manage research collaboration** in one centralized system.

## 🎯 Project Objective

Research collaboration often involves managing projects, team members, tasks, resources, meetings, and progress updates across different platforms.

Sohojatra brings these activities together into a single platform to make research work more **organized, transparent, and easier to track**.

## ✨ Key Features

### 👨‍🎓 Student

* View and join research projects
* Manage assigned tasks
* Access research resources
* Submit weekly progress updates
* View upcoming and past meetings
* Receive faculty feedback
* Track research project activities

### 👨‍🏫 Faculty

* Create and manage research projects
* Manage research teams
* Assign and monitor tasks
* Add and manage research resources
* Schedule meetings
* Review student progress
* Provide feedback

### 🛡️ Admin

* Manage users and roles
* Monitor projects and platform activity
* Access administrative dashboards

### 🔐 Security & Access Control

* Role-based access control
* Password hashing and verification
* Prepared SQL statements
* Session-based authentication
* Input/output validation and escaping
* Protected access to role-specific pages

## 🛠️ Tech Stack

* **Backend:** PHP
* **Database:** MySQL / MariaDB
* **Frontend:** HTML, CSS, JavaScript
* **Local Server:** XAMPP
* **Development Assistance:** Google Antigravity, ChatGPT

## 🗄️ Database

The project uses a relational database to represent the different components of a research collaboration workflow.

Major entities include:

* User
* Student
* Faculty
* Admin
* Project
* Team
* Task
* Resource
* Meeting
* Progress
* Join Request
* Supporting relationship tables

The database connects research projects with their teams, tasks, resources, progress records, meetings, and feedback.

## 🔄 Research Workflow

```text
Faculty creates Research Project
            ↓
       Student joins
            ↓
       Team Formation
            ↓
     Tasks & Resources
            ↓
     Weekly Progress
            ↓
        Meetings
            ↓
    Faculty Feedback
            ↓
      Progress Tracking
```

## 🖥️ Running the Project Locally

### Prerequisites

Install:

* XAMPP
* A web browser
* MySQL/MariaDB through XAMPP

### Setup

1. Clone or download this repository.

2. Copy the project folder into:

```text
C:\xampp\htdocs\
```

3. Start **Apache** and **MySQL** from the XAMPP Control Panel.

4. Open **phpMyAdmin** and create the required database.

5. Import the project's SQL database file.

6. Configure the database connection in the project's database configuration file if required.

7. Open the project in your browser:

```text
http://localhost/research_collaboration_portal/
```

## 🤖 Use of AI

AI tools were used as **development assistants** during the project.

* **Google Antigravity:** Assisted with implementation, debugging, UI/UX improvements, and development workflows.
* **ChatGPT:** Assisted with planning, debugging, code understanding, feature design, and development guidance.

The project requirements, database structure, feature decisions, testing, and overall development were carried out and refined by the team.

## 📚 Learning Outcomes

Through this project, we gained practical experience in:

* Relational database design
* SQL and database relationships
* PHP backend development
* Authentication and authorization
* CRUD operations
* Frontend development
* Form handling and validation
* Session management
* Database security
* Testing and debugging
* Integrating a complete web application

## 📌 Course

**CSE370 — Database Management Systems**
**BRAC University**

---

⭐ Feel free to explore the repository and the implementation.
