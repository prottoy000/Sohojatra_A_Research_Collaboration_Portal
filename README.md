# Sohojatra — Research Collaboration Portal

Sohojatra is a web-based Research Collaboration Portal developed as a **CSE370: Database Management Systems** course project at **BRAC University**.

The system provides a centralized platform for students and faculty to manage research projects, teams, tasks, resources, progress updates, meetings, and feedback.

## Project Overview

Research collaboration involves several interconnected activities, including project management, task assignment, resource sharing, progress tracking, and communication between students and faculty.

Sohojatra integrates these activities into a single system. It uses a relational database to maintain connections between users, research projects, teams, tasks, resources, meetings, and progress records.

The system supports three primary roles:

* **Student** — Participate in research projects, manage assigned tasks, access resources, submit progress updates, and view meetings and feedback.
* **Faculty** — Create and manage research projects, form research teams, assign tasks, manage resources, schedule meetings, review progress, and provide feedback.
* **Admin** — Manage users, roles, projects, and overall system activity.

## Key Features

### Project Management

* Create and manage research projects
* View available and active research projects
* Manage project teams
* Connect project activities through relational database relationships

### Task Management

* Create and assign research tasks
* Track task status and deadlines
* Identify overdue, due-today, and upcoming tasks
* Update task information

### Resource Management

* Add and manage research-related resources
* Associate resources with specific research projects
* Provide project members with centralized access to resources

### Progress Tracking

* Submit weekly research progress
* Maintain a project-based progress timeline
* Allow faculty to review student progress
* Provide feedback related to submitted progress

### Meeting Management

* Schedule research meetings
* View upcoming and previous meetings
* Associate meetings with relevant students and faculty

### Authentication and Authorization

* User authentication
* Password hashing and verification
* Role-based access control
* Session-based authorization
* Restricted access to role-specific functionality

## Technology Stack

| Component                | Technology                  |
| ------------------------ | --------------------------- |
| Backend                  | PHP                         |
| Database                 | MySQL / MariaDB             |
| Frontend                 | HTML, CSS, JavaScript       |
| Local Development Server | XAMPP                       |
| Database Management      | phpMyAdmin                  |
| Development Assistance   | Google Antigravity, ChatGPT |

## Database Design

Sohojatra follows a relational database structure designed to represent the different components of a research collaboration system.

Major entities and relationships include:

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
* Project-Team relationships
* Project-Resource relationships
* Project-Progress relationships

The relational structure allows project-related activities to remain connected and enables information to be retrieved based on users, projects, teams, and roles.

## System Workflow

```text
Faculty Creates Project
        |
        v
Student Joins Project
        |
        v
Team Formation
        |
        v
Task Assignment and Resources
        |
        v
Weekly Progress Submission
        |
        v
Research Meetings
        |
        v
Faculty Review and Feedback
        |
        v
Progress Tracking
```

## Security Measures

The application includes several basic security practices:

* Password hashing using PHP password functions
* Prepared SQL statements to reduce SQL injection risks
* Output escaping to reduce XSS risks
* Session regeneration after authentication
* Role-based access control
* Server-side validation
* Protected role-specific pages

## Running the Project Locally

### Prerequisites

* XAMPP
* PHP
* MySQL or MariaDB
* A modern web browser

### Installation

1. Clone the repository:

```bash
git clone https://github.com/your-username/sohojatra-research-collaboration-portal.git
```

2. Move the project folder into the XAMPP `htdocs` directory:

```text
C:\xampp\htdocs\
```

3. Start **Apache** and **MySQL** from the XAMPP Control Panel.

4. Open **phpMyAdmin**.

5. Create the required database.

6. Import the project's SQL database file.

7. Configure the database connection according to your local MySQL setup.

8. Open the application in a browser:

```text
http://localhost/research_collaboration_portal/
```

## Development Process

The project was developed collaboratively with a focus on database design, backend implementation, frontend development, authentication, authorization, testing, debugging, and integration of the research workflow.

AI-assisted development tools were also used during the development process:

* **Google Antigravity** — assisted with implementation, debugging, UI/UX development, and code modifications.
* **ChatGPT** — assisted with project planning, technical guidance, debugging, feature design, and code understanding.

AI tools were used as development assistants throughout the project, while the project requirements, database structure, feature decisions, testing, and final implementation were reviewed and refined by the team.

## Learning Outcomes

This project provided practical experience with:

* Relational database design
* SQL and database relationships
* PHP backend development
* CRUD operations
* Authentication and authorization
* Session management
* Form handling and validation
* Frontend development
* Database security
* Testing and debugging
* Integrating a complete database-driven web application

## Course Information

**Course:** CSE370 — Database Management Systems
**Institution:** BRAC University
**Project:** Sohojatra — Research Collaboration Portal

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.
