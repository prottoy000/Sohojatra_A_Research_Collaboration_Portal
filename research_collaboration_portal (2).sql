-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 09:13 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `research_collaboration_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `Admin_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`Admin_ID`) VALUES
(1004),
(1006),
(2007),
(55465);

--
-- Triggers `admin`
--
DELIMITER $$
CREATE TRIGGER `enforce_disjoint_admin_insert` BEFORE INSERT ON `admin` FOR EACH ROW BEGIN
    IF EXISTS (SELECT 1 FROM student WHERE Student_ID = NEW.Admin_ID) OR 
       EXISTS (SELECT 1 FROM faculty WHERE Faculty_ID = NEW.Admin_ID) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Disjoint Constraint: User is already a Student or Faculty.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `enforce_disjoint_admin_update` BEFORE UPDATE ON `admin` FOR EACH ROW BEGIN
    IF EXISTS (SELECT 1 FROM student WHERE Student_ID = NEW.Admin_ID) OR 
       EXISTS (SELECT 1 FROM faculty WHERE Faculty_ID = NEW.Admin_ID) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Disjoint Constraint: User is already a Student or Faculty.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `attends`
--

CREATE TABLE `attends` (
  `Student_ID` int(11) NOT NULL,
  `Faculty_ID` int(11) NOT NULL,
  `Meeting_ID` int(11) NOT NULL,
  `Feedback` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attends`
--

INSERT INTO `attends` (`Student_ID`, `Faculty_ID`, `Meeting_ID`, `Feedback`) VALUES
(1001, 2001, 401, 'Very helpful meeting'),
(1002, 2001, 401, 'Good discussion'),
(24301065, 2454565, 408, 'good going guys');

-- --------------------------------------------------------

--
-- Table structure for table `contains`
--

CREATE TABLE `contains` (
  `Task_ID` int(11) NOT NULL,
  `Project_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contains`
--

INSERT INTO `contains` (`Task_ID`, `Project_ID`) VALUES
(101, 1),
(102, 1),
(103, 2),
(104, 2),
(105, 3),
(106, 3),
(107, 4),
(108, 4);

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `Faculty_ID` int(11) NOT NULL,
  `Designation` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`Faculty_ID`, `Designation`) VALUES
(2001, 'Professor'),
(24301, ''),
(2454565, '');

--
-- Triggers `faculty`
--
DELIMITER $$
CREATE TRIGGER `enforce_disjoint_faculty_insert` BEFORE INSERT ON `faculty` FOR EACH ROW BEGIN
    IF EXISTS (SELECT 1 FROM admin WHERE Admin_ID = NEW.Faculty_ID) OR 
       EXISTS (SELECT 1 FROM student WHERE Student_ID = NEW.Faculty_ID) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Disjoint Constraint: User is already an Admin or Student.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `enforce_disjoint_faculty_update` BEFORE UPDATE ON `faculty` FOR EACH ROW BEGIN
    IF EXISTS (SELECT 1 FROM admin WHERE Admin_ID = NEW.Faculty_ID) OR 
       EXISTS (SELECT 1 FROM student WHERE Student_ID = NEW.Faculty_ID) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Disjoint Constraint: User is already an Admin or Student.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `has`
--

CREATE TABLE `has` (
  `Resource_ID` int(11) NOT NULL,
  `Project_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `has`
--

INSERT INTO `has` (`Resource_ID`, `Project_ID`) VALUES
(201, 1),
(202, 2),
(203, 3),
(204, 4),
(205, 1);

-- --------------------------------------------------------

--
-- Table structure for table `joins`
--

CREATE TABLE `joins` (
  `User_ID` int(11) NOT NULL,
  `Team_ID` int(11) NOT NULL,
  `Role` varchar(50) NOT NULL,
  `Date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `joins`
--

INSERT INTO `joins` (`User_ID`, `Team_ID`, `Role`, `Date`) VALUES
(1001, 301, 'Leader', '2026-08-01'),
(1002, 301, 'Member', '2026-08-01'),
(1003, 302, 'Leader', '2026-08-03'),
(1005, 302, 'Member', '2026-08-03'),
(1007, 303, 'Leader', '2026-08-05'),
(1008, 303, 'Member', '2026-08-05'),
(1009, 304, 'Leader', '2026-08-07'),
(1010, 304, 'Member', '2026-08-07'),
(24301065, 308, 'Leader', '2026-09-01'),
(24301065, 309, 'Leader', '2026-09-01');

-- --------------------------------------------------------

--
-- Table structure for table `join_request`
--

CREATE TABLE `join_request` (
  `Request_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Team_ID` int(11) NOT NULL,
  `Request_Date` date NOT NULL,
  `Status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `join_request`
--

INSERT INTO `join_request` (`Request_ID`, `User_ID`, `Team_ID`, `Request_Date`, `Status`) VALUES
(1, 1001, 301, '2026-08-10', 'Accepted'),
(2, 1002, 301, '2026-08-11', 'Accepted'),
(3, 1003, 302, '2026-08-12', 'Pending'),
(4, 1005, 302, '2026-08-13', 'Rejected'),
(5, 1007, 303, '2026-08-14', 'Pending'),
(6, 1009, 303, '2026-08-15', 'Accepted'),
(7, 1010, 301, '2026-08-16', 'Pending'),
(0, 10063, 303, '2026-08-31', 'Pending'),
(0, 24301065, 301, '2026-09-01', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `meeting`
--

CREATE TABLE `meeting` (
  `Meeting_ID` int(11) NOT NULL,
  `Time` time NOT NULL,
  `Date` date NOT NULL,
  `Location` varchar(150) DEFAULT NULL,
  `Link` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meeting`
--

INSERT INTO `meeting` (`Meeting_ID`, `Time`, `Date`, `Location`, `Link`) VALUES
(401, '10:00:00', '2026-08-20', 'Room 501', 'https://meet.example.com/meeting1'),
(402, '14:30:00', '2026-08-22', 'Room 302', 'https://meet.example.com/meeting2'),
(403, '11:00:00', '2026-08-25', 'Lab 3', 'https://meet.example.com/meeting3'),
(404, '15:00:00', '2026-08-28', 'Room 405', 'https://meet.example.com/meeting4'),
(405, '00:40:00', '2026-09-01', '401', 'https://meet.google.com/abc-defg-hij'),
(406, '00:40:00', '2026-09-01', '401', 'https://meet.google.com/abc-defg-hij'),
(407, '00:40:00', '2026-09-01', '401', 'https://meet.google.com/abc-defg-hij'),
(408, '00:40:00', '2026-09-01', '401', 'https://meet.google.com/abc-defg-hij');

-- --------------------------------------------------------

--
-- Table structure for table `phone`
--

CREATE TABLE `phone` (
  `User_ID` int(11) NOT NULL,
  `Phone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phone`
--

INSERT INTO `phone` (`User_ID`, `Phone`) VALUES
(1001, '01711000001'),
(1002, '01711000002'),
(1003, '01711000003'),
(1004, '01711000004'),
(1005, '01711000005'),
(1006, '01811000001');

-- --------------------------------------------------------

--
-- Table structure for table `progress`
--

CREATE TABLE `progress` (
  `Week_No` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Submission_Date` date NOT NULL,
  `Progress_Update` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `progress`
--

INSERT INTO `progress` (`Week_No`, `User_ID`, `Submission_Date`, `Progress_Update`) VALUES
(1, 1001, '2026-08-08', ''),
(1, 1002, '2026-08-09', ''),
(1, 1003, '2026-08-10', ''),
(1, 1007, '2026-08-11', ''),
(1, 1009, '2026-08-12', ''),
(1, 24301065, '2026-09-01', 'Did eer for load shedding\r\n'),
(2, 1001, '2026-08-15', ''),
(2, 1002, '2026-08-16', ''),
(5, 10063, '2026-09-01', 'Did eer');

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

CREATE TABLE `project` (
  `Project_ID` int(11) NOT NULL,
  `Title` varchar(150) NOT NULL,
  `Student_ID` int(11) DEFAULT NULL,
  `Domain` varchar(50) DEFAULT NULL,
  `Admin_ID` int(11) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Status` varchar(30) NOT NULL,
  `Creation_Date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project`
--

INSERT INTO `project` (`Project_ID`, `Title`, `Student_ID`, `Domain`, `Admin_ID`, `Description`, `Status`, `Creation_Date`) VALUES
(1, 'Smart Blood Donation Network', 1001, 'Healthcare', 1004, 'A platform for connecting blood donors with patients and hospitals.', 'Active', '2026-08-01'),
(2, 'Research Collaboration Portal', 1001, 'Education', 1006, 'A platform for students and faculty to collaborate on research projects.', 'Active', '2026-08-03'),
(3, 'Campus Resource Management', 1007, 'Management', 1004, 'A system for managing university resources and student access.', 'Planning', '2026-08-05'),
(4, 'Student Task Management System', 1009, 'Productivity', 1006, 'A system for assigning and tracking student project tasks.', 'Active', '2026-08-07'),
(6, 'Rain Prediction System', 1001, 'Artificial Intellligence', 1004, 'It will help to predict Rain', 'Ongoing', '2026-08-18'),
(7, 'Load Shedding Prediction', 24301065, 'ML', 55465, 'Will predict load shedding', 'Planning', '2026-09-01');

-- --------------------------------------------------------

--
-- Table structure for table `resource`
--

CREATE TABLE `resource` (
  `Resource_ID` int(11) NOT NULL,
  `Title` varchar(150) NOT NULL,
  `Student_ID` int(11) DEFAULT NULL,
  `Link` varchar(500) NOT NULL,
  `Type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resource`
--

INSERT INTO `resource` (`Resource_ID`, `Title`, `Student_ID`, `Link`, `Type`) VALUES
(201, 'DBMS Documentation', 1001, 'https://example.com/dbms', 'Document'),
(202, 'Research Paper Collection', 1003, 'https://example.com/research', 'PDF'),
(203, 'UI Design Reference', 1007, 'https://example.com/ui', 'Website'),
(204, 'Project Guidelines', 1009, 'https://example.com/guidelines', 'Document'),
(205, 'SQL Tutorial', 1002, 'https://example.com/sql', 'Tutorial'),
(206, 'Research Paper on load shedding', 24301065, 'https://www.researchgate.net/publication/3268391_Load_Shedding_A_New_Proposal', 'Research Paper');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `Student_ID` int(11) NOT NULL,
  `Project_ID` int(11) NOT NULL,
  `Batch` varchar(20) NOT NULL,
  `Join_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`Student_ID`, `Project_ID`, `Batch`, `Join_ID`) VALUES
(1001, 1, '2025', 501),
(1002, 1, '2025', 502),
(1003, 2, '2024', 503),
(1005, 2, '2024', 504),
(1007, 3, '2025', 505),
(1008, 3, '2025', 506),
(1009, 4, '2024', 507),
(1010, 4, '2024', 508),
(2531, 0, '', 0),
(10063, 0, '', 0),
(24301065, 0, '', 0);

--
-- Triggers `student`
--
DELIMITER $$
CREATE TRIGGER `enforce_disjoint_student_insert` BEFORE INSERT ON `student` FOR EACH ROW BEGIN
    IF EXISTS (SELECT 1 FROM admin WHERE Admin_ID = NEW.Student_ID) OR 
       EXISTS (SELECT 1 FROM faculty WHERE Faculty_ID = NEW.Student_ID) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Disjoint Constraint: User is already an Admin or Faculty.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `enforce_disjoint_student_update` BEFORE UPDATE ON `student` FOR EACH ROW BEGIN
    IF EXISTS (SELECT 1 FROM admin WHERE Admin_ID = NEW.Student_ID) OR 
       EXISTS (SELECT 1 FROM faculty WHERE Faculty_ID = NEW.Student_ID) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Disjoint Constraint: User is already an Admin or Faculty.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `Task_ID` int(11) NOT NULL,
  `Status` varchar(30) NOT NULL,
  `Priority` varchar(20) NOT NULL,
  `Title` varchar(150) NOT NULL,
  `Student_ID` int(11) DEFAULT NULL,
  `Deadline` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`Task_ID`, `Status`, `Priority`, `Title`, `Student_ID`, `Deadline`) VALUES
(101, 'Pending', 'High', 'Design database schema', 1001, '2026-08-25'),
(102, 'In Progress', 'Medium', 'Develop login system', 1002, '2026-08-27'),
(103, 'Completed', 'High', 'Prepare research proposal', 1003, '2026-08-20'),
(104, 'Pending', 'Low', 'Collect research papers', 1005, '2026-08-28'),
(105, 'In Progress', 'Medium', 'Design UI prototype', 1007, '2026-08-30'),
(106, 'Pending', 'High', 'Implement resource module', 1008, '2026-09-01'),
(107, 'Completed', 'Medium', 'Create project documentation', 1009, '2026-08-22'),
(108, 'Pending', 'High', 'Test task management module', 1010, '2026-09-02'),
(109, 'Completed', 'Medium', 'Draw EER for project load shedding', 24301065, '2026-09-16');

-- --------------------------------------------------------

--
-- Table structure for table `team`
--

CREATE TABLE `team` (
  `Team_ID` int(11) NOT NULL,
  `Creation_Date` date NOT NULL,
  `Faculty_ID` int(11) DEFAULT NULL,
  `Team_Name` varchar(50) NOT NULL,
  `Project_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team`
--

INSERT INTO `team` (`Team_ID`, `Creation_Date`, `Faculty_ID`, `Team_Name`, `Project_ID`) VALUES
(301, '2026-08-01', 2001, 'BloodConnect Team', 1),
(302, '2026-08-03', 2001, 'ResearchHub Team', 2),
(303, '2026-08-05', 2001, 'CampusTech Team', 3),
(304, '2026-08-07', 2001, 'TaskForce Team', 4),
(305, '0000-00-00', 24301, '', 1),
(307, '2026-09-01', 24301, 'Alpha', 7),
(308, '2026-09-01', 24301, 'Alpha', 7),
(309, '2026-09-01', 2454565, 'Beta Coders', 6),
(310, '0000-00-00', 2454565, '', 3);

-- --------------------------------------------------------

--
-- Table structure for table `tracks`
--

CREATE TABLE `tracks` (
  `Project_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Week_No` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tracks`
--

INSERT INTO `tracks` (`Project_ID`, `User_ID`, `Week_No`) VALUES
(1, 1001, 1),
(1, 1002, 1),
(1, 1002, 2),
(2, 1003, 1),
(2, 1003, 2),
(2, 1005, 1),
(3, 1007, 1),
(3, 1007, 2),
(4, 1009, 1),
(4, 1010, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `User_ID` int(11) NOT NULL,
  `First_Name` varchar(50) NOT NULL,
  `Last_Name` varchar(50) NOT NULL,
  `Department` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_ID`, `First_Name`, `Last_Name`, `Department`, `Email`, `Password`) VALUES
(1001, 'Rahim', 'Ahmed', 'CSE', 'rahim.ahmed@example.com', 'Rahim@123'),
(1002, 'Nusrat', 'Jahan', 'CSE', 'nusrat.jahan@example.com', 'Nusrat@123'),
(1003, 'Tanvir', 'Hasan', 'EEE', 'tanvir.hasan@example.com', 'Tanvir@123'),
(1004, 'Sadia', 'Islam', 'BBA', 'sadia.islam@example.com', 'Sadia@123'),
(1005, 'Fahim', 'Rahman', 'CSE', 'fahim.rahman@example.com', 'Fahim@123'),
(1006, 'Mehedi', 'Hasan', 'CSE', 'mehedi.hasan@example.com', 'Mehedi@123'),
(1007, 'Tasmia', 'Akter', 'EEE', 'tasmia.akter@example.com', 'Tasmia@123'),
(1008, 'Arif', 'Hossain', 'CSE', 'arif.hossain@example.com', 'Arif@123'),
(1009, 'Mim', 'Chowdhury', 'BBA', 'mim.chowdhury@example.com', 'Mim@123'),
(1010, 'Sakib', 'Mahmud', 'CSE', 'sakib.mahmud@example.com', 'Sakib@123'),
(2001, 'Nabila', 'Rahman', 'CSE', 'nabila@bracu.ac.bd', '123456'),
(2007, 'Rahim', 'Haque', 'EEE ', 'rahim@y.com', '$2y$10$./SEIGUCXZfVj.KaWX544uqE8.3j6BnNQc/ntpuoFXLV4kndkeX5u'),
(2531, 'Priyota', 'Banik', 'CSE', 'priyota.banik@g.bracu.ac.bd', 'priyota123'),
(10063, 'Jeet', 'Biswas', 'CSE', 'prottoy@x.com', '$2y$10$VAGT2QTx4zqSicKjvLoz1.KVtquowXfSPBWUeyDFOAuctCQOokFrm'),
(24301, 'Ridwanul ', 'Haque', 'MNS', 'ridwan@v.c', '$2y$10$jiM/MuZXGHKHoZDiSFEUq.VeoQ.8MOpHri8tx7hSVKyi.abt0U2eO'),
(55465, 'Rahib', 'Sultanul', 'CSE', 'rahib@example.com', '$2y$10$6aTeCaQ2LlkCmu4/2zguwuq.DnVnczkEpd3vbwL48XQWbRQ.S7c1u'),
(2454565, 'Aariq', 'Mahmud', 'CSE', 'aariq@example.com', '$2y$10$bgE9spjnmgMK3kQ7SsMqiepRHXAnKdULWbL74CP9.kH3zYqURbxr2'),
(24301065, 'Prottoy ', 'Jeet', 'CSE', 'prottoy@example.com', '$2y$10$Wlu0HZIKIMwzrNUl0818k.WRIz2ua9neaQghFQ5pn8mxA.gS2BT.S');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`Admin_ID`);

--
-- Indexes for table `attends`
--
ALTER TABLE `attends`
  ADD PRIMARY KEY (`Student_ID`,`Faculty_ID`,`Meeting_ID`),
  ADD KEY `FK_Faculty` (`Faculty_ID`),
  ADD KEY `FK_Meeting` (`Meeting_ID`);

--
-- Indexes for table `contains`
--
ALTER TABLE `contains`
  ADD KEY `FK_Contains_Task` (`Task_ID`),
  ADD KEY `FK_Contains_Project` (`Project_ID`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`Faculty_ID`);

--
-- Indexes for table `has`
--
ALTER TABLE `has`
  ADD PRIMARY KEY (`Resource_ID`,`Project_ID`),
  ADD KEY `FK_Has_Project` (`Project_ID`);

--
-- Indexes for table `joins`
--
ALTER TABLE `joins`
  ADD PRIMARY KEY (`User_ID`,`Team_ID`),
  ADD KEY `FK_Team_Join` (`Team_ID`);

--
-- Indexes for table `join_request`
--
ALTER TABLE `join_request`
  ADD PRIMARY KEY (`User_ID`,`Team_ID`),
  ADD KEY `FK_Team_Request` (`Team_ID`);

--
-- Indexes for table `meeting`
--
ALTER TABLE `meeting`
  ADD PRIMARY KEY (`Meeting_ID`);

--
-- Indexes for table `phone`
--
ALTER TABLE `phone`
  ADD PRIMARY KEY (`User_ID`);

--
-- Indexes for table `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`Week_No`,`User_ID`),
  ADD KEY `FK_Progress_User` (`User_ID`);

--
-- Indexes for table `project`
--
ALTER TABLE `project`
  ADD PRIMARY KEY (`Project_ID`),
  ADD KEY `FK_Admin_Project` (`Admin_ID`),
  ADD KEY `FK_Student_Project` (`Student_ID`);

--
-- Indexes for table `resource`
--
ALTER TABLE `resource`
  ADD PRIMARY KEY (`Resource_ID`),
  ADD KEY `FK_Student_Resource` (`Student_ID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`Student_ID`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`Task_ID`),
  ADD KEY `FK_Student_Tasks` (`Student_ID`);

--
-- Indexes for table `team`
--
ALTER TABLE `team`
  ADD PRIMARY KEY (`Team_ID`),
  ADD KEY `FK_Faculty_Team` (`Faculty_ID`),
  ADD KEY `FK_Project_Team` (`Project_ID`);

--
-- Indexes for table `tracks`
--
ALTER TABLE `tracks`
  ADD PRIMARY KEY (`Project_ID`,`User_ID`,`Week_No`),
  ADD KEY `FK_Tracks_Student` (`User_ID`),
  ADD KEY `FK_Tracks_Week` (`Week_No`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `meeting`
--
ALTER TABLE `meeting`
  MODIFY `Meeting_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=409;

--
-- AUTO_INCREMENT for table `project`
--
ALTER TABLE `project`
  MODIFY `Project_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `resource`
--
ALTER TABLE `resource`
  MODIFY `Resource_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=208;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `Task_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `team`
--
ALTER TABLE `team`
  MODIFY `Team_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=311;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24301066;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `FK_Admin_User` FOREIGN KEY (`Admin_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attends`
--
ALTER TABLE `attends`
  ADD CONSTRAINT `FK_Faculty` FOREIGN KEY (`Faculty_ID`) REFERENCES `faculty` (`Faculty_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Meeting` FOREIGN KEY (`Meeting_ID`) REFERENCES `meeting` (`Meeting_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Student` FOREIGN KEY (`Student_ID`) REFERENCES `student` (`Student_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `contains`
--
ALTER TABLE `contains`
  ADD CONSTRAINT `FK_Contains_Project` FOREIGN KEY (`Project_ID`) REFERENCES `project` (`Project_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Contains_Task` FOREIGN KEY (`Task_ID`) REFERENCES `tasks` (`Task_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `FK_Faculty_User` FOREIGN KEY (`Faculty_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `has`
--
ALTER TABLE `has`
  ADD CONSTRAINT `FK_Has_Project` FOREIGN KEY (`Project_ID`) REFERENCES `project` (`Project_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Has_Resource` FOREIGN KEY (`Resource_ID`) REFERENCES `resource` (`Resource_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `joins`
--
ALTER TABLE `joins`
  ADD CONSTRAINT `FK_Student_Join` FOREIGN KEY (`User_ID`) REFERENCES `student` (`Student_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Team_Join` FOREIGN KEY (`Team_ID`) REFERENCES `team` (`Team_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `join_request`
--
ALTER TABLE `join_request`
  ADD CONSTRAINT `FK_Team_Request` FOREIGN KEY (`Team_ID`) REFERENCES `team` (`Team_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_User_Request` FOREIGN KEY (`User_ID`) REFERENCES `student` (`Student_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `phone`
--
ALTER TABLE `phone`
  ADD CONSTRAINT `FK_phone_User` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `progress`
--
ALTER TABLE `progress`
  ADD CONSTRAINT `FK_Progress_User` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `project`
--
ALTER TABLE `project`
  ADD CONSTRAINT `FK_Admin_Project` FOREIGN KEY (`Admin_ID`) REFERENCES `admin` (`Admin_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Student_Project` FOREIGN KEY (`Student_ID`) REFERENCES `student` (`Student_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `resource`
--
ALTER TABLE `resource`
  ADD CONSTRAINT `FK_Student_Resource` FOREIGN KEY (`Student_ID`) REFERENCES `student` (`Student_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `FK_Student_User` FOREIGN KEY (`Student_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `FK_Student_Tasks` FOREIGN KEY (`Student_ID`) REFERENCES `student` (`Student_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `team`
--
ALTER TABLE `team`
  ADD CONSTRAINT `FK_Faculty_Team` FOREIGN KEY (`Faculty_ID`) REFERENCES `faculty` (`Faculty_ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Project_Team` FOREIGN KEY (`Project_ID`) REFERENCES `project` (`Project_ID`) ON UPDATE CASCADE;

--
-- Constraints for table `tracks`
--
ALTER TABLE `tracks`
  ADD CONSTRAINT `FK_Tracks_Project` FOREIGN KEY (`Project_ID`) REFERENCES `project` (`Project_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Tracks_Student` FOREIGN KEY (`User_ID`) REFERENCES `student` (`Student_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Tracks_Week` FOREIGN KEY (`Week_No`) REFERENCES `progress` (`Week_No`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
