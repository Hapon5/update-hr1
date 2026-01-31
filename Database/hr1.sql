-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 26, 2025 at 08:50 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hr1`
--

-- --------------------------------------------------------

--
-- Table structure for table `appraisals`
--

CREATE TABLE `appraisals` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `rater_email` varchar(255) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `appraisal_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `appraisals`
--

INSERT INTO `appraisals` (`id`, `employee_id`, `rater_email`, `rating`, `comment`, `appraisal_date`) VALUES
(1, 1, 'admin@gmail.com', 4, 'good thinking', '2025-09-27 11:57:41'),
(2, 4, 'admin@gmail.com', 5, 'good maganda ka te', '2025-09-27 13:17:58'),
(3, 1, 'admin@gmail.com', 4, 'idol ko ito sa ml e', '2025-09-27 17:06:50'),
(4, 2, 'admin@gmail.com', 3, 'ayos po ng mukha ng picture niyo po joke lang haha', '2025-09-27 13:22:24');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `experience_years` int(11) NOT NULL,
  `age` int(11) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `resume_path` varchar(500) DEFAULT NULL,
  `status` enum('new','reviewed','shortlisted','interviewed','rejected','hired') DEFAULT 'new',
  `source` varchar(100) DEFAULT 'Direct Application',
  `skills` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`id`, `full_name`, `job_title`, `position`, `experience_years`, `age`, `contact_number`, `email`, `address`, `resume_path`, `status`, `source`, `skills`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'John Lloyd Morales ', 'Software Engineer', 'Full time', 3, 25, '09060805234', 'moralesjohnlloyd@gmail.com', 'Bago Bantay Quezon City', '../uploads/resumes/68d6fd5acd97a_1758920026.pdf', 'new', 'Direct Application', 'programmer', 'hindi ko alam yan', '2025-09-26 20:53:46', '2025-09-26 20:53:46'),
(3, 'Andy Ferrer', 'CaliCrane', 'IT Support', 1, 24, '09513330483', 'ferrerandy76@gmail.com', 'North Kaloocan Homes blk 22 Lot 11 Bagumbong Caloocan city', '../uploads/resumes/68d7957b73db8_1758958971.docx', 'new', 'Direct Application', 'Frontend Dev', '', '2025-09-27 07:42:51', '2025-09-27 07:42:51');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in hours',
  `instructor` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `photo_path` varchar(255) DEFAULT 'mark.jpg',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `position`, `photo_path`, `status`, `created_at`) VALUES
(1, 'Siegfried Mar Viloria', 'Team Leader/ Developer', 'profile/Viloria.jpeg', 'active', '2025-09-27 03:14:56'),
(2, 'John Lloyd Morales', 'System Analyst', 'profile/morales.jpeg', 'active', '2025-09-27 03:14:56'),
(3, 'Andy Ferrer', 'Document Specialist', 'profile/ferrer.jpeg', 'active', '2025-09-27 03:14:56'),
(4, 'Andrea Ilagan', 'Technical Support Analyst', 'profile/ilagan.jpeg', 'active', '2025-09-27 03:14:56'),
(5, 'Charlotte Achivida', 'Cyber Security Analyst', 'profile/achivida.jpeg', 'active', '2025-09-27 03:14:56');

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `id` int(11) NOT NULL,
  `candidate_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `interviewer` varchar(255) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `interviews`
--

INSERT INTO `interviews` (`id`, `candidate_name`, `email`, `position`, `interviewer`, `start_time`, `end_time`, `location`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Sarah Johnson', 'sarah.j@email.com', 'Full-Time', 'Siegfired mar villoria', '2025-09-27 21:24:00', '2025-09-29 22:27:00', 'cubao', 'scheduled', '', '2025-09-27 11:23:29', '2025-09-27 11:23:29');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_id` int(11) NOT NULL,
  `job_name` varchar(255) NOT NULL,
  `job_description` varchar(255) NOT NULL,
  `job_salary` varchar(255) NOT NULL,
  `job_featured` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`job_id`, `job_name`, `job_description`, `job_salary`, `job_featured`) VALUES
(1, 'Delivery Boy', 'Your job is deliver all the goods from the warehouse.', '15,000php to 20,000php', 1),
(2, 'Packager', 'Your job is to package all the goods', '15,000php to 20,000php', 0),
(3, 'HR Manager', 'Your job is observe all the staff and employees', '50,000php to 80,000php', 0);

-- --------------------------------------------------------

--
-- Table structure for table `job_postings`
--

CREATE TABLE `job_postings` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `requirements` text DEFAULT NULL,
  `contact` varchar(255) NOT NULL,
  `platform` varchar(255) NOT NULL,
  `date_posted` date NOT NULL,
  `status` enum('active','inactive','closed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `job_postings`
--

INSERT INTO `job_postings` (`id`, `title`, `position`, `location`, `requirements`, `contact`, `platform`, `date_posted`, `status`, `created_at`, `updated_at`) VALUES
(2, 'Software Engineer', 'Full-Time', 'New York, NY', 'BSc in CS, 3+ yrs in Python, Django, REST APIs', 'hiring@techcorp.com', 'Indeed', '2025-09-27', 'active', '2025-09-27 11:13:31', '2025-09-27 11:13:31'),
(3, 'Marketing Manager', 'Full-Time', 'Remote', 'BA in Marketing, SEO/SEM experience, HubSpot proficiency', 'hr@adspotmedia.co', 'LinkedIn', '2025-09-24', 'active', '2025-09-27 11:14:24', '2025-09-27 11:14:24'),
(4, 'Data Analyst', 'Contract', 'Austin, TX', 'SQL, Python, Tableau, 2+ yrs in analytics', 'linkedin.com/in/dataqueen', 'Company Website', '2025-09-02', 'active', '2025-09-27 11:15:11', '2025-09-27 11:15:11'),
(5, 'DEVELOPER', 'FRONTEND DEVELOPER', 'CALOOCAN CITY', 'BSIT GRADUATE', '09513330483', 'CALI CRANE', '2025-09-28', 'active', '2025-09-28 07:30:10', '2025-09-28 07:30:10');

-- --------------------------------------------------------

--
-- Table structure for table `learning_achievements`
--

CREATE TABLE `learning_achievements` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `achievement_type` varchar(100) NOT NULL,
  `achievement_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `earned_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logintbl`
--

CREATE TABLE `logintbl` (
  `LoginID` int(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Account_type` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logintbl`
--

INSERT INTO `logintbl` (`LoginID`, `Email`, `Password`, `Account_type`) VALUES
(3, 'user@gmail.com', '12345', 0),
(4, 'admin@gmail.com', '12345', 1);

-- --------------------------------------------------------

--
-- Table structure for table `recognitions`
--

CREATE TABLE `recognitions` (
  `id` int(11) NOT NULL,
  `from_employee_id` int(11) NOT NULL,
  `to_employee_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `recognition_date` timestamp NULL DEFAULT current_timestamp(),
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recognitions`
--

INSERT INTO `recognitions` (`id`, `from_employee_id`, `to_employee_id`, `category_id`, `title`, `message`, `recognition_date`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, 'Great Team Collaboration', 'Thank you for your excellent teamwork on the recent project. Your collaboration made all the difference!', '2024-01-15 10:30:00', 1, '2025-09-27 03:51:47', '2025-09-27 03:51:47'),
(2, 2, 3, 4, 'Outstanding Performance', 'Your attention to detail and dedication to quality is truly impressive. Keep up the excellent work!', '2024-01-16 14:20:00', 1, '2025-09-27 03:51:47', '2025-09-27 03:51:47'),
(3, 3, 1, 2, 'Innovative Solution', 'Your creative approach to solving the technical challenge was brilliant. Thank you for thinking outside the box!', '2024-01-17 09:15:00', 1, '2025-09-27 03:51:47', '2025-09-27 03:51:47'),
(4, 1, 1, 3, 'Leadership', 'Quality Leadership', '2025-09-27 07:26:17', 1, '2025-09-27 07:26:17', '2025-09-27 07:26:17');

-- --------------------------------------------------------

--
-- Table structure for table `recognition_categories`
--

CREATE TABLE `recognition_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-star',
  `color` varchar(20) DEFAULT '#d37a15',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recognition_categories`
--

INSERT INTO `recognition_categories` (`id`, `name`, `description`, `icon`, `color`, `is_active`, `created_at`) VALUES
(1, 'Teamwork', 'Recognizing collaborative efforts and team spirit', 'fas fa-users', '#28a745', 1, '2025-09-27 03:51:47'),
(2, 'Innovation', 'Acknowledging creative solutions and new ideas', 'fas fa-lightbulb', '#17a2b8', 1, '2025-09-27 03:51:47'),
(3, 'Leadership', 'Recognizing leadership qualities and guidance', 'fas fa-crown', '#ffc107', 1, '2025-09-27 03:51:47'),
(4, 'Excellence', 'Acknowledging outstanding performance and quality work', 'fas fa-trophy', '#dc3545', 1, '2025-09-27 03:51:47'),
(5, 'Support', 'Recognizing helpfulness and support to colleagues', 'fas fa-hands-helping', '#6f42c1', 1, '2025-09-27 03:51:47'),
(6, 'Achievement', 'Celebrating milestones and accomplishments', 'fas fa-medal', '#fd7e14', 1, '2025-09-27 03:51:47'),
(7, 'Customer Service', 'Recognizing excellent customer interactions', 'fas fa-smile', '#20c997', 1, '2025-09-27 03:51:47'),
(8, 'Problem Solving', 'Acknowledging effective problem-solving skills', 'fas fa-puzzle-piece', '#6c757d', 1, '2025-09-27 03:51:47');

-- --------------------------------------------------------

--
-- Table structure for table `recognition_likes`
--

CREATE TABLE `recognition_likes` (
  `id` int(11) NOT NULL,
  `recognition_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `safety_incidents`
--

CREATE TABLE `safety_incidents` (
  `id` int(11) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `incident_details` text NOT NULL,
  `incident_type` varchar(100) NOT NULL,
  `severity` enum('low','medium','high') NOT NULL,
  `location` varchar(255) NOT NULL,
  `reported_by` varchar(255) NOT NULL,
  `incident_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('reported','investigating','resolved','closed') DEFAULT 'reported',
  `resolution_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `safety_incidents`
--

INSERT INTO `safety_incidents` (`id`, `employee_name`, `incident_details`, `incident_type`, `severity`, `location`, `reported_by`, `incident_date`, `status`, `resolution_notes`, `created_at`, `updated_at`) VALUES
(1, 'John Smith', 'Minor cut on hand while using cutting tool. First aid applied immediately.', 'injury', 'low', 'Workshop Area A', 'admin@company.com', '2024-01-15 10:30:00', 'reported', NULL, '2025-09-27 05:19:35', '2025-09-27 05:19:35'),
(2, 'Sarah Johnson', 'Near miss incident with forklift in warehouse. No injuries occurred.', 'near_miss', 'medium', 'Warehouse Section B', 'admin@company.com', '2024-01-14 14:20:00', 'reported', NULL, '2025-09-27 05:19:35', '2025-09-27 05:19:35'),
(3, 'Mike Wilson', 'Equipment malfunction reported. Machine shut down safely.', 'equipment_failure', 'medium', 'Production Line 2', 'admin@company.com', '2024-01-13 09:15:00', 'reported', NULL, '2025-09-27 05:19:35', '2025-09-27 05:19:35'),
(4, 'Siegfried mar villoria', 'Near miss incident with forklift in warehouse. No injuries occurred.\r\n', 'injury', 'low', 'cubao ', 'admin@gmail.com', '2025-09-27 07:25:39', 'reported', NULL, '2025-09-27 07:25:39', '2025-09-27 07:25:39'),
(5, 'Siegfried mar villoria', 'Near miss incident with forklift in warehouse. No injuries occurred.\r\n', 'injury', 'low', 'cubao ', 'admin@gmail.com', '2025-09-27 07:27:28', 'reported', NULL, '2025-09-27 07:27:28', '2025-09-27 07:27:28');

-- --------------------------------------------------------

--
-- Table structure for table `safety_policies`
--

CREATE TABLE `safety_policies` (
  `id` int(11) NOT NULL,
  `policy_title` varchar(255) NOT NULL,
  `policy_content` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `safety_policies`
--

INSERT INTO `safety_policies` (`id`, `policy_title`, `policy_content`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Workplace Safety Guidelines', 'All employees must follow proper safety protocols including wearing appropriate PPE, reporting hazards immediately, and participating in safety training programs.', 'General Safety', 1, '2025-09-27 05:19:35', '2025-09-27 05:19:35'),
(2, 'Emergency Procedures', 'In case of emergency, follow the established evacuation procedures, report to designated assembly points, and assist others when safe to do so.', 'Emergency Response', 1, '2025-09-27 05:19:35', '2025-09-27 05:19:35'),
(3, 'Equipment Safety', 'All equipment must be inspected before use, operated by trained personnel only, and reported for maintenance when issues are identified.', 'Equipment Safety', 1, '2025-09-27 05:19:35', '2025-09-27 05:19:35'),
(4, 'Personal Protective Equipment', 'PPE must be worn in designated areas, properly maintained, and replaced when damaged or expired.', 'PPE Requirements', 1, '2025-09-27 05:19:35', '2025-09-27 05:19:35');

-- --------------------------------------------------------

--
-- Table structure for table `safety_training`
--

CREATE TABLE `safety_training` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `training_type` varchar(255) NOT NULL,
  `training_date` date NOT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','overdue') DEFAULT 'scheduled',
  `certificate_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appraisals`
--
ALTER TABLE `appraisals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_name` (`name`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`);

--
-- Indexes for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `learning_achievements`
--
ALTER TABLE `learning_achievements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `logintbl`
--
ALTER TABLE `logintbl`
  ADD PRIMARY KEY (`LoginID`);

--
-- Indexes for table `recognitions`
--
ALTER TABLE `recognitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_employee_id` (`from_employee_id`),
  ADD KEY `to_employee_id` (`to_employee_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `recognition_categories`
--
ALTER TABLE `recognition_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recognition_likes`
--
ALTER TABLE `recognition_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`recognition_id`,`employee_id`),
  ADD KEY `recognition_id` (`recognition_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `safety_incidents`
--
ALTER TABLE `safety_incidents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `safety_policies`
--
ALTER TABLE `safety_policies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `safety_training`
--
ALTER TABLE `safety_training`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appraisals`
--
ALTER TABLE `appraisals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `logintbl`
--
ALTER TABLE `logintbl`
  MODIFY `LoginID` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
