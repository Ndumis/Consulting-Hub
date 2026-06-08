-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 01, 2025 at 09:21 PM
-- Server version: 10.5.29-MariaDB
-- PHP Version: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `thekcaar_kconsulting`
--

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `event_type` varchar(100) DEFAULT 'meeting',
  `client_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(50) DEFAULT 'scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `calendar_events`
--

INSERT INTO `calendar_events` (`id`, `title`, `description`, `event_date`, `event_time`, `event_type`, `client_id`, `project_id`, `created_by`, `created_at`, `updated_at`, `status`) VALUES
(1, 'Project Kickoff Meeting', 'Initial project planning and requirements gathering', '2025-09-13', '10:00:00', 'meeting', 1, 1, 1, '2025-09-11 13:23:34', '2025-09-11 13:23:34', 'scheduled'),
(2, 'Client Presentation', 'Present final project deliverables to client', '2025-09-16', '14:30:00', 'presentation', 2, 2, 1, '2025-09-11 13:23:34', '2025-09-11 13:23:34', 'scheduled'),
(3, 'Team Sprint Planning', 'Plan upcoming development sprint', '2025-09-14', '09:00:00', 'planning', NULL, 3, 1, '2025-09-11 13:23:34', '2025-09-11 13:23:34', 'scheduled'),
(4, 'Marketing Campaign Review', 'Review Q4 marketing campaign performance', '2025-09-15', '11:00:00', 'review', 3, NULL, 1, '2025-09-11 13:23:35', '2025-09-11 13:23:35', 'scheduled'),
(5, 'Budget Planning Session', 'Plan departmental budget for next quarter', '2025-09-17', '15:00:00', 'planning', NULL, NULL, 1, '2025-09-11 13:23:35', '2025-09-11 13:23:35', 'scheduled');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `job_posting_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `resume_file` varchar(255) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'applied',
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`id`, `job_posting_id`, `first_name`, `last_name`, `email`, `phone`, `resume_file`, `cover_letter`, `status`, `application_date`, `created_at`) VALUES
(1, 1, 'Sarah', 'Johnson', 'sarah.johnson@email.com', '+1-555-0123', NULL, 'I am excited to apply for this position. With 5 years of experience in software development, I believe I would be a valuable addition to your team.', 'applied', '2025-09-11 13:22:29', '2025-09-11 13:22:29'),
(2, 1, 'Michael', 'Chen', 'michael.chen@email.com', '+1-555-0456', NULL, 'Dear Hiring Manager, I am writing to express my interest in this role. My background in computer science and industry experience have prepared me well for this opportunity.', 'applied', '2025-09-11 13:22:29', '2025-09-11 13:22:29'),
(3, 2, 'Emma', 'Davis', 'emma.davis@email.com', '+1-555-0789', NULL, 'I am thrilled to apply for this marketing position. With my degree in Marketing and digital marketing experience, I have successfully managed campaigns that increased brand awareness.', 'reviewed', '2025-09-11 13:22:30', '2025-09-11 13:22:30');

-- --------------------------------------------------------

--
-- Table structure for table `candidate_education`
--

CREATE TABLE `candidate_education` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `institution_name` varchar(100) NOT NULL,
  `degree_type` varchar(50) NOT NULL,
  `field_of_study` varchar(100) DEFAULT NULL,
  `start_year` int(11) DEFAULT NULL,
  `end_year` int(11) DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `gpa` decimal(3,2) DEFAULT NULL,
  `honors` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidate_work_experience`
--

CREATE TABLE `candidate_work_experience` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `position_title` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `responsibilities` text DEFAULT NULL,
  `achievements` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'prospect',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `email`, `phone`, `company`, `address`, `status`, `created_at`) VALUES
(1, 'TechCorp', 'contact@techcorp.com', NULL, 'TechCorp Solutions Inc.', NULL, 'active', '2025-09-11 10:40:33'),
(2, 'GreenEnergy', 'info@greenenergy.com', NULL, 'GreenEnergy Solutions', NULL, 'active', '2025-09-11 10:40:33'),
(3, 'RetailPlus', 'sales@retailplus.com', NULL, 'RetailPlus Ltd', NULL, 'prospect', '2025-09-11 10:40:33'),
(4, 'TechCorp Solutions', 'contact@techcorp.com', '+1-555-0101', 'TechCorp Solutions Inc.', '123 Tech Street, Silicon Valley, CA 94000', 'active', '2025-09-11 10:53:23'),
(5, 'Green Energy Ltd', 'info@greenenergy.com', '+1-555-0102', 'Green Energy Limited', '456 Solar Avenue, Austin, TX 73000', 'active', '2025-09-11 10:53:23'),
(6, 'Fashion Forward', 'hello@fashionforward.com', '+1-555-0103', 'Fashion Forward LLC', '789 Style Boulevard, New York, NY 10001', 'prospect', '2025-09-11 10:53:24'),
(7, 'HealthTech Innovations', 'contact@healthtech.com', '+1-555-0104', 'HealthTech Innovations Corp', '321 Medical Plaza, Boston, MA 02101', 'active', '2025-09-11 10:53:24'),
(8, 'EduLearn Platform', 'support@edulearn.com', '+1-555-0105', 'EduLearn Educational Services', '654 Learning Lane, Chicago, IL 60601', 'prospect', '2025-09-11 10:53:25'),
(9, 'RetailMax Chain', 'business@retailmax.com', '+1-555-0106', 'RetailMax Chain Stores', '987 Commerce Drive, Miami, FL 33101', 'active', '2025-09-11 10:53:25'),
(10, 'StartupX', 'founders@startupx.com', '+1-555-0107', 'StartupX Technologies', '147 Innovation Hub, Seattle, WA 98101', 'prospect', '2025-09-11 10:53:25'),
(11, 'Global Ride (Pty)Ltd', '', '+1-555-0108', 'Manufacturing Plus Industries', '258 Industrial Park, Detroit, MI 48201', 'prospect', '2025-09-11 10:53:26');

-- --------------------------------------------------------

--
-- Table structure for table `client_contacts`
--

CREATE TABLE `client_contacts` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_to` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_meetings`
--

CREATE TABLE `client_meetings` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `meeting_title` varchar(200) NOT NULL,
  `meeting_date` datetime NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `agenda` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'scheduled',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultation_requests`
--

CREATE TABLE `consultation_requests` (
  `id` int(11) NOT NULL,
  `company` varchar(255) NOT NULL,
  `industry` varchar(100) NOT NULL,
  `company_size` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `services` varchar(100) NOT NULL,
  `consultation_type` varchar(100) NOT NULL,
  `timeline` varchar(50) NOT NULL,
  `budget` varchar(50) NOT NULL,
  `current_challenges` text NOT NULL,
  `desired_outcomes` text NOT NULL,
  `current_systems` text DEFAULT NULL,
  `decision_maker` varchar(50) NOT NULL,
  `decision_timeline` varchar(50) NOT NULL,
  `competitors` varchar(50) DEFAULT NULL,
  `meeting_type` varchar(50) NOT NULL,
  `preferred_location` varchar(100) DEFAULT NULL,
  `availability` varchar(100) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `qualification_score` int(3) NOT NULL,
  `submitted_at` datetime NOT NULL,
  `assigned_to` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `consultation_requests`
--

INSERT INTO `consultation_requests` (`id`, `company`, `industry`, `company_size`, `name`, `position`, `email`, `phone`, `services`, `consultation_type`, `timeline`, `budget`, `current_challenges`, `desired_outcomes`, `current_systems`, `decision_maker`, `decision_timeline`, `competitors`, `meeting_type`, `preferred_location`, `availability`, `additional_info`, `qualification_score`, `submitted_at`, `assigned_to`) VALUES
(1, 'KConsulting', 'financial', 'startup', 'KHAYELIHLE SIMELANE', 'ceo', 'mkhayguze@gmail.com', '0653307703', 'it-consulting', 'strategic', 'long', '500k-1m', 'We need resources to embark on the new project related to bank', 'We need a team that can develop, test and mantain a loan system', 'Java, angular, spring boot, .net, camunda, mongo db, sql ', 'yes', 'month', 'few', 'flexible', 'cape-town', 'evening', '', 0, '2025-08-22 10:28:45', NULL),
(2, 'KConsulting Firm', 'other', 'startup', 'MS SINQOBILE C NDLOVU', 'coordinator', 'christobellndlovu@gmail.com', '0698367250', 'marketing', 'strategic', 'immediate', 'under-50k', 'Our pain pointsare our digital marketing.', 'We want to grow our social media through paid strategic marketing campaigns that engage our audience.', 'Non.', 'yes', 'immediate', 'early', 'in-person', 'durban', 'afternoon', '', 0, '2025-08-22 22:37:49', NULL),
(3, 'KConsulting Firm', 'other', 'startup', 'MS SINQOBILE C NDLOVU', 'cto', 'christobellndlovu@gmail.com', '0698367250', 'marketing', 'strategic', 'immediate', 'under-50k', ' b', 'hhb', '', 'influencer', 'month', 'many', 'virtual', 'cape-town', 'afternoon', '', 0, '2025-08-22 23:10:20', NULL),
(4, 'KConsulting', 'healthcare', 'startup', 'KHAYELIHLE SIMELANE', 'coordinator', 'mkhayguze@gmail.com', '0653307703', 'software-development', 'Basic', 'long', 'over-1m', 'No one is building and Improving current solutions', 'Improving and building new healthcare solution', '.Net, Angular, SQL and API', 'yes', 'longer', 'no', 'flexible', 'neutral', 'morning', '', 0, '2025-08-23 16:43:50', NULL),
(5, 'NSFAS', 'government', 'large', 'KHAYELIHLE SIMELANE', 'manager', 'mkhayguze@gmail.com', '0653307703', 'it-consulting', 'Basic', 'medium', '500k-1m', 'Our consultation process ensures we can deliver the highest value for your investment. We work with businesses ready for transformation.', 'Our consultation process ensures we can deliver the highest value for your investment. We work with businesses ready for transformation.', 'Our consultation process ensures we can deliver the highest value for your investment. We work with businesses ready for transformation.', 'yes', 'month', 'few', 'virtual', 'neutral', 'afternoon', 'Our consultation process ensures we can deliver the highest value for your investment. We work with businesses ready for transformation.', 0, '2025-09-12 11:24:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `custom_reports`
--

CREATE TABLE `custom_reports` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `report_type` varchar(50) NOT NULL,
  `sql_query` text NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_campaigns`
--

CREATE TABLE `email_campaigns` (
  `id` int(11) NOT NULL,
  `marketing_campaign_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `content` text NOT NULL,
  `recipient_list` text DEFAULT NULL,
  `scheduled_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) DEFAULT 'draft',
  `sent_count` int(11) DEFAULT 0,
  `open_count` int(11) DEFAULT 0,
  `click_count` int(11) DEFAULT 0,
  `total_recipients` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_campaigns`
--

INSERT INTO `email_campaigns` (`id`, `marketing_campaign_id`, `client_id`, `subject`, `body`, `content`, `recipient_list`, `scheduled_date`, `status`, `sent_count`, `open_count`, `click_count`, `total_recipients`, `created_by`, `created_at`) VALUES
(1, 1, NULL, 'Welcome to Our Summer Collection!', 'Discover the hottest trends for this season. Get 15% off your first purchase with code SUMMER15.', '', 'newsletter@company.com, customers@lists.com', '2024-09-15 06:00:00', 'sent', 2500, 850, 125, 0, 5, '2025-09-11 10:54:20'),
(2, 1, NULL, 'Last Chance: Summer Sale Ending Soon!', 'Don\'t miss out on amazing deals. Sale ends in 48 hours. Shop now before it\'s too late!', '', 'customers@lists.com', '2024-09-28 08:00:00', 'sent', 3200, 1100, 180, 0, 6, '2025-09-11 10:54:21'),
(3, 4, NULL, 'Monthly Newsletter - September 2024', 'Industry insights, company updates, and exclusive tips for our valued subscribers.', '', 'newsletter@lists.com', '2024-09-01 10:00:00', 'sent', 5400, 1890, 340, 0, 5, '2025-09-11 10:54:21'),
(4, 4, NULL, 'Exclusive Member Benefits Update', 'New perks added to your membership! Check out what\'s new and how to access your benefits.', '', 'members@lists.com', '2024-09-10 12:00:00', 'sent', 1800, 720, 95, 0, 6, '2025-09-11 10:54:21'),
(5, 3, NULL, 'Brand Story: Our Journey So Far', 'From startup to industry leader - read about our mission, values, and what drives us forward.', '', 'newsletter@lists.com', '2024-09-20 14:00:00', 'scheduled', 0, 0, 0, 0, 5, '2025-09-11 10:54:22'),
(6, 7, NULL, 'You\'ve Got Rewards to Claim!', 'Thanks for referring your friends! Your reward points are ready. Here\'s how to redeem them.', '', 'rewards@lists.com', '2024-09-25 09:00:00', 'scheduled', 0, 0, 0, 0, 6, '2025-09-11 10:54:22'),
(7, 6, NULL, 'Partnership Opportunity Update', 'Exciting collaboration opportunities await! Learn about our new partnership program.', '', 'partners@lists.com', '2025-09-12 12:46:33', 'draft', 0, 0, 0, 3, 5, '2025-09-11 10:54:23');

-- --------------------------------------------------------

--
-- Table structure for table `email_recipients`
--

CREATE TABLE `email_recipients` (
  `id` int(11) NOT NULL,
  `email_campaign_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_recipients`
--

INSERT INTO `email_recipients` (`id`, `email_campaign_id`, `email`, `name`, `status`, `sent_at`, `opened_at`, `clicked_at`, `created_at`) VALUES
(1, 6, 'mkhayguze@gmail.com', 'KHAYELIHLE SIMELANE', 'pending', NULL, NULL, NULL, '2025-09-12 06:32:00'),
(2, 7, 'mkhayguze@gmail.com', 'KHAYELIHLE SIMELANE', 'pending', NULL, NULL, NULL, '2025-09-12 12:43:17'),
(3, 7, 'mkhayguze@gmail.com', 'KHAYELIHLE SIMELANE', 'pending', NULL, NULL, NULL, '2025-09-12 12:46:04'),
(4, 7, 'mkhayguze@gmail.com', 'KHAYELIHLE SIMELANE', 'pending', NULL, NULL, NULL, '2025-09-12 12:46:33');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(30) DEFAULT 'IT',
  `position` varchar(50) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'office',
  `description` text NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expense_date` date NOT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `project_id`, `category`, `description`, `amount`, `expense_date`, `receipt_file`, `status`, `approved_by`, `approved_at`, `submitted_by`, `created_at`) VALUES
(4, NULL, 'office', 'Office supplies and equipment', 320.00, '2024-09-20', NULL, 'pending', NULL, NULL, 10, '2025-09-11 10:58:13'),
(6, 5, 'consultation', 'Security expert consultation', 800.00, '2024-09-25', NULL, 'pending', NULL, NULL, 10, '2025-09-11 10:58:14'),
(8, 6, 'hosting', 'AWS infrastructure costs', 420.00, '2024-10-01', NULL, 'pending', NULL, NULL, 9, '2025-09-11 10:58:14');

-- --------------------------------------------------------

--
-- Table structure for table `hr_employees`
--

CREATE TABLE `hr_employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(30) DEFAULT 'IT',
  `position` varchar(50) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_employees`
--

INSERT INTO `hr_employees` (`id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `department`, `position`, `salary`, `hire_date`, `status`, `manager_id`, `created_at`) VALUES
(1, 'EMP004', 'Michael', 'Chen', 'michael.chen@company.com', '+1-555-2001', 'IT', 'Senior Developer', 85000.00, '2023-03-15', 'active', NULL, '2025-09-11 10:54:31'),
(2, 'EMP005', 'Jennifer', 'Martinez', 'jennifer.martinez@company.com', '+1-555-2002', 'Marketing', 'Content Specialist', 58000.00, '2023-06-01', 'active', NULL, '2025-09-11 10:54:31'),
(3, 'EMP006', 'Robert', 'Taylor', 'robert.taylor@company.com', '+1-555-2003', 'Finance', 'Senior Accountant', 72000.00, '2023-01-20', 'active', NULL, '2025-09-11 10:54:32'),
(4, 'EMP007', 'Amanda', 'Wilson', 'amanda.wilson@company.com', '+1-555-2004', 'HR', 'HR Coordinator', 55000.00, '2023-09-10', 'active', NULL, '2025-09-11 10:54:32'),
(5, 'EMP008', 'Christopher', 'Davis', 'chris.davis@company.com', '+1-555-2005', 'Clients', 'Account Manager', 68000.00, '2023-04-05', 'active', NULL, '2025-09-11 10:54:33'),
(6, 'EMP009', 'Michelle', 'Garcia', 'michelle.garcia@company.com', '+1-555-2006', 'IT', 'DevOps Engineer', 82000.00, '2023-07-12', 'active', NULL, '2025-09-11 10:54:33'),
(7, 'EMP010', 'Daniel', 'Rodriguez', 'daniel.rodriguez@company.com', '+1-555-2007', 'Marketing', 'Social Media Manager', 52000.00, '2023-08-18', 'active', NULL, '2025-09-11 10:54:34'),
(8, 'EMP011', 'Rachel', 'Lee', 'rachel.lee@company.com', '+1-555-2008', 'Finance', 'Financial Analyst', 64000.00, '2023-02-28', 'active', NULL, '2025-09-11 10:54:34'),
(9, 'EMP012', 'Kevin', 'Anderson', 'kevin.anderson@company.com', '+1-555-2009', 'Clients', 'Customer Success Manager', 61000.00, '2023-05-14', 'active', NULL, '2025-09-11 10:54:34');

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_requests`
--

CREATE TABLE `hr_leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `leave_type` varchar(30) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hr_leave_requests`
--

INSERT INTO `hr_leave_requests` (`id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `days_requested`, `reason`, `status`, `approved_by`, `approved_at`, `created_at`) VALUES
(7, 1, 'Annual Leave', '2024-10-15', '2024-10-18', 4, 'Family vacation to mountains', 'approved', 7, NULL, '2025-09-11 10:54:35'),
(8, 2, 'Sick Leave', '2024-09-20', '2024-09-22', 3, 'Flu symptoms, doctor recommended rest', 'approved', 7, NULL, '2025-09-11 10:54:35'),
(9, 3, 'Personal Leave', '2024-11-01', '2024-11-01', 1, 'Moving to new apartment', 'approved', 1, NULL, '2025-09-11 10:54:36'),
(10, 4, 'Annual Leave', '2024-12-20', '2024-12-31', 10, 'Christmas and New Year holidays', 'pending', NULL, NULL, '2025-09-11 10:54:36'),
(11, 5, 'Maternity Leave', '2024-11-15', '2025-02-15', 90, 'Expected delivery date approach', 'approved', 7, NULL, '2025-09-11 10:54:37'),
(12, 6, 'Annual Leave', '2024-10-10', '2024-10-12', 3, 'Wedding anniversary celebration', 'approved', 8, NULL, '2025-09-11 10:54:37'),
(13, 7, 'Sick Leave', '2024-09-25', '2024-09-25', 1, 'Medical appointment', 'approved', 8, NULL, '2025-09-11 10:54:38'),
(14, 8, 'Annual Leave', '2024-11-25', '2024-11-29', 5, 'Thanksgiving week with family', 'rejected', 1, NULL, '2025-09-11 10:54:38'),
(15, 9, 'Personal Leave', '2024-10-08', '2024-10-08', 1, 'Child school event', 'approved', 11, NULL, '2025-09-11 10:54:39'),
(19, 5, 'personal', '2025-09-12', '2025-09-25', 14, 'hfdbdfhvdfhdffddh', 'pending', NULL, NULL, '2025-09-12 12:41:35');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat_rate` decimal(4,4) NOT NULL DEFAULT 0.1500,
  `vat_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `quotation_id`, `client_id`, `project_id`, `invoice_date`, `due_date`, `status`, `subtotal`, `vat_rate`, `vat_amount`, `total_amount`, `paid_amount`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'INV-2025-DEMO', 4, 1, NULL, '2025-09-11', '2025-10-11', 'draft', 2000.00, 0.1500, 300.00, 2300.00, 0.00, 'Demo invoice converted from QUO-2025-TEST', 1, '2025-09-11 10:40:33', '2025-09-11 10:40:33'),
(3, 'INV-2025-001', 4, 1, 4, '2024-09-18', '2024-10-18', 'sent', 12500.00, 0.1500, 1875.00, 14375.00, 5000.00, 'Partial payment received. Balance due on completion.', 9, '2025-09-11 10:55:14', '2025-09-11 10:55:14'),
(4, 'INV-2025-002', NULL, 3, 3, '2024-09-12', '2024-10-12', 'sent', 8900.00, 0.1500, 1335.00, 10235.00, 0.00, 'Database migration services as per signed agreement.', 10, '2025-09-11 10:55:15', '2025-09-11 10:55:15'),
(5, 'INV-2025-003', 7, 7, 7, '2024-09-30', '2024-10-30', 'paid', 9500.00, 0.1500, 1425.00, 10925.00, 10925.00, 'Analytics dashboard development - converted from quotation.', 10, '2025-09-11 10:55:15', '2025-09-12 10:36:59'),
(6, 'INV-2025-004', NULL, 2, 2, '2024-09-08', '2024-10-08', 'paid', 6800.00, 0.1500, 1020.00, 7820.00, 7820.00, 'Consultation and project planning phase completed.', 9, '2025-09-11 10:55:16', '2025-09-11 10:55:16'),
(7, 'INV-2025-005', NULL, 5, 5, '2024-09-22', '2024-10-22', 'sent', 11250.00, 0.1500, 1687.50, 12937.50, 0.00, 'Security audit phase 1 - vulnerability assessment completed.', 10, '2025-09-11 10:55:16', '2025-09-11 10:55:16');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 2, 'Software Development - Phase 1', 1.00, 1500.00, 1500.00, '2025-09-11 10:40:34'),
(2, 2, 'Testing & QA Services', 1.00, 500.00, 500.00, '2025-09-11 10:40:34'),
(6, 2, 'Database Analysis', 20.00, 120.00, 2400.00, '2025-09-11 10:55:18'),
(7, 2, 'Migration Planning', 15.00, 110.00, 1650.00, '2025-09-11 10:55:19'),
(8, 2, 'Data Backup Services', 12.00, 105.00, 1260.00, '2025-09-11 10:55:19'),
(9, 2, 'Initial Migration Phase', 28.00, 135.00, 3780.00, '2025-09-11 10:55:19'),
(10, 3, 'Dashboard Architecture', 25.00, 130.00, 3250.00, '2025-09-11 10:55:20'),
(11, 3, 'Frontend Development', 35.00, 120.00, 4200.00, '2025-09-11 10:55:20'),
(12, 3, 'Data Integration', 20.00, 100.00, 2000.00, '2025-09-11 10:55:21'),
(13, 4, 'Project Consultation', 16.00, 150.00, 2400.00, '2025-09-11 10:55:21'),
(14, 4, 'Technical Specification', 20.00, 125.00, 2500.00, '2025-09-11 10:55:22'),
(15, 4, 'Architecture Planning', 18.00, 100.00, 1800.00, '2025-09-11 10:55:22'),
(16, 5, 'Security Assessment', 45.00, 140.00, 6300.00, '2025-09-11 10:55:23'),
(17, 5, 'Vulnerability Testing', 30.00, 130.00, 3900.00, '2025-09-11 10:55:23'),
(18, 5, 'Compliance Review', 15.00, 120.00, 1800.00, '2025-09-11 10:55:23'),
(19, 6, 'Consulting Services', 4.00, 350.00, 1400.00, '2025-09-11 11:20:36'),
(20, 6, 'Implementation Support', 6.00, 220.00, 1320.00, '2025-09-11 11:20:37'),
(21, 7, 'Consulting Services', 4.00, 350.00, 1400.00, '2025-09-11 11:20:37'),
(22, 7, 'Implementation Support', 6.00, 220.00, 1320.00, '2025-09-11 11:20:38');

-- --------------------------------------------------------

--
-- Table structure for table `job_postings`
--

CREATE TABLE `job_postings` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `department` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `salary_range` varchar(50) DEFAULT NULL,
  `employment_type` varchar(20) DEFAULT 'full_time',
  `status` varchar(20) DEFAULT 'active',
  `posted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_postings`
--

INSERT INTO `job_postings` (`id`, `title`, `department`, `description`, `requirements`, `salary_range`, `employment_type`, `status`, `posted_by`, `created_at`) VALUES
(1, 'Senior Software Developer', 'IT', 'We are looking for an experienced software developer to join our team.', 'Bachelor degree in Computer Science, 5+ years experience', 'R500,000 - R800,000', 'full_time', 'active', 1, '2025-09-10 20:50:10'),
(2, 'Marketing Manager', 'Marketing', 'Lead our marketing initiatives and campaigns.', 'Marketing degree, 3+ years management experience', 'R400,000 - R600,000', 'full_time', 'active', 1, '2025-09-10 20:50:10'),
(3, 'HR Specialist', 'HR', 'Support HR operations and employee relations.', 'HR qualification, 2+ years experience', 'R300,000 - R450,000', 'full_time', 'active', 1, '2025-09-10 20:50:10'),
(9, 'Senior Software Developer', 'IT', 'We are looking for a Senior Software Developer to join our dynamic IT team. The successful candidate will be responsible for developing, testing, and maintaining software applications that support our business operations.', 'Bachelor\'s degree in Computer Science or related field. 5+ years of experience in software development. Proficiency in PHP, JavaScript, Python, and SQL.', 'R45,000 - R65,000 per month', 'full_time', 'active', 1, '2025-09-11 10:34:53');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `leave_type` varchar(30) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketing_blog_posts`
--

CREATE TABLE `marketing_blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'General',
  `tags` varchar(500) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `publish_date` date DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marketing_blog_posts`
--

INSERT INTO `marketing_blog_posts` (`id`, `title`, `content`, `excerpt`, `featured_image`, `category`, `tags`, `status`, `publish_date`, `client_id`, `campaign_id`, `author_id`, `created_at`, `updated_at`) VALUES
(1, '10 Digital Marketing Trends for 2025', 'The digital marketing landscape continues to evolve at breakneck speed. As we move into 2025, businesses must adapt to new technologies, changing consumer behaviors, and emerging platforms to stay competitive...', NULL, NULL, 'Digital Marketing', 'trends, marketing, 2025, AI', 'published', '2025-09-11', 1, 1, 6, '2025-09-11 13:25:26', '2025-09-12 07:36:20'),
(2, 'Building Brand Authority Through Content Marketing', 'Content marketing remains one of the most effective ways to build brand authority and establish thought leadership in your industry...', NULL, NULL, 'Content Strategy', 'content marketing, brand authority', 'published', '2025-09-08', 2, 2, 7, '2025-09-11 13:25:26', '2025-09-12 07:36:29'),
(3, 'Social Media Marketing Best Practices for Small Businesses', 'Social media marketing can be overwhelming for small businesses with limited resources. However, with the right strategy and focus, small businesses can compete effectively...', NULL, NULL, 'Social Media', 'social media, small business', 'published', '2025-09-04', 3, NULL, 6, '2025-09-11 13:25:27', '2025-09-12 07:38:07'),
(4, 'Email Marketing Automation: A Complete Guide', 'Email marketing automation allows businesses to nurture leads and maintain customer relationships at scale...', '', '', 'technology', 'email marketing, automation', 'scheduled', '2025-09-06', 1, 3, 7, '2025-09-11 13:25:27', '2025-09-12 11:05:58'),
(5, 'The Future of E-commerce: Trends to Watch', 'The e-commerce industry continues to evolve rapidly, driven by technological advances and changing consumer expectations...', NULL, NULL, 'E-commerce', 'ecommerce, future trends', 'published', '2025-09-09', 2, NULL, 5, '2025-09-11 13:25:27', '2025-09-12 07:37:54');

-- --------------------------------------------------------

--
-- Table structure for table `marketing_campaigns`
--

CREATE TABLE `marketing_campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `campaign_name` varchar(100) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(30) DEFAULT 'Social Media',
  `campaign_type` varchar(50) DEFAULT 'Social Media',
  `status` varchar(20) DEFAULT 'planning',
  `budget` decimal(10,2) DEFAULT 0.00,
  `spent` decimal(10,2) DEFAULT 0.00,
  `target_audience` text DEFAULT NULL,
  `metrics` text NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marketing_campaigns`
--

INSERT INTO `marketing_campaigns` (`id`, `name`, `campaign_name`, `client_id`, `description`, `type`, `campaign_type`, `status`, `budget`, `spent`, `target_audience`, `metrics`, `start_date`, `end_date`, `created_by`, `created_at`) VALUES
(1, 'Summer Product Launch 2024', 'Summer Product Launch 2024', NULL, 'Multi-channel campaign for new product line launch', 'Product Launch', 'Social Media', 'active', 15000.00, 8500.00, 'Young professionals aged 25-40, tech-savvy consumers', '', '2024-07-01', '2024-09-30', 5, '2025-09-11 10:54:12'),
(2, 'Black Friday Sales Promotion', 'Black Friday Sales Promotion', NULL, 'Seasonal sales campaign with aggressive discounts', 'Seasonal Sales', 'Social Media', 'planning', 25000.00, 2000.00, 'Existing customers and price-conscious shoppers', '', '2024-11-15', '2024-11-30', 5, '2025-09-11 10:54:12'),
(3, 'Brand Awareness Q4 2024', 'Brand Awareness Q4 2024', NULL, 'Increase brand recognition through content marketing', 'Brand Awareness', 'Social Media', 'active', 8000.00, 3200.00, 'Business owners and decision makers', '', '2024-10-01', '2024-12-31', 6, '2025-09-11 10:54:13'),
(4, 'Email Newsletter Automation', 'Email Newsletter Automation', NULL, 'Automated email sequence for customer retention', 'Email Marketing', 'Social Media', 'active', 3000.00, 1800.00, 'Existing customer base, newsletter subscribers', '', '2024-08-15', '2024-12-15', 6, '2025-09-11 10:54:13'),
(5, 'Social Media Growth Initiative', 'Social Media Growth Initiative', NULL, 'Increase social media following and engagement', 'Social Media', 'Social Media', 'active', 5000.00, 2100.00, 'Millennials and Gen Z consumers', '', '2024-09-01', '2024-11-30', 6, '2025-09-11 10:54:14'),
(6, 'Partnership Marketing Program', 'Partnership Marketing Program', NULL, 'Collaborate with industry partners for co-marketing', 'Partnership', 'Social Media', 'planning', 12000.00, 500.00, 'B2B clients and industry professionals', '', '2024-11-01', '2025-01-31', 5, '2025-09-11 10:54:14'),
(7, 'Customer Referral Campaign', 'Customer Referral Campaign', NULL, 'Incentivize existing customers to refer new clients', 'Referral Program', 'Social Media', 'active', 4000.00, 1200.00, 'Happy existing customers', '', '2024-09-15', '2024-12-31', 6, '2025-09-11 10:54:15'),
(8, 'Video Marketing Series', 'Video Marketing Series', NULL, 'Educational video content for YouTube and social media', 'Content Marketing', 'Social Media', 'in-progress', 7500.00, 3100.00, 'Educational content seekers, professionals', '', '2024-08-01', '2024-10-31', 5, '2025-09-11 10:54:15');

-- --------------------------------------------------------

--
-- Table structure for table `money_flow`
--

CREATE TABLE `money_flow` (
  `id` int(11) NOT NULL,
  `transaction_type` varchar(20) NOT NULL,
  `category` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `money_flow`
--

INSERT INTO `money_flow` (`id`, `transaction_type`, `category`, `amount`, `description`, `transaction_date`, `client_id`, `project_id`, `invoice_id`, `created_by`, `created_at`) VALUES
(1, 'income', 'Payment', 10925.00, 'Payment for invoice INV-2025-003', '2025-09-12', 7, NULL, 5, 1, '2025-09-12 10:36:59');

-- --------------------------------------------------------

--
-- Table structure for table `performance_reviews`
--

CREATE TABLE `performance_reviews` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `review_period_start` date NOT NULL,
  `review_period_end` date NOT NULL,
  `overall_rating` int(11) DEFAULT 3,
  `goals_achievement` text DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `areas_for_improvement` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `performance_reviews`
--

INSERT INTO `performance_reviews` (`id`, `employee_id`, `reviewer_id`, `review_period_start`, `review_period_end`, `overall_rating`, `goals_achievement`, `strengths`, `areas_for_improvement`, `comments`, `status`, `created_at`) VALUES
(1, 7, 8, '0000-00-00', '0000-00-00', 3, 'gnvcxv', 'vncxv', 'ncbvcxv', 'ncvxvv', 'draft', '2025-09-12 12:34:59'),
(2, 5, 5, '2025-09-01', '2025-09-12', 3, 'vzdxcvxcv', 'cvxc', 'cxvcvx', 'cvxcv', 'draft', '2025-09-12 12:38:51');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `category` varchar(30) DEFAULT 'Web Dev',
  `priority` varchar(20) DEFAULT 'medium',
  `progress` int(11) DEFAULT 0,
  `status` varchar(20) DEFAULT 'pending',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `client_id`, `category`, `priority`, `progress`, `status`, `start_date`, `end_date`, `created_at`) VALUES
(1, 'E-Commerce Website Redesign', 'Complete redesign of client e-commerce platform with modern UI/UX', 1, 'Web Dev', 'high', 75, 'in-progress', '2024-09-01', '2024-11-15', '2025-09-11 10:53:39'),
(2, 'Mobile App Development', 'Native iOS and Android app for client business management', 2, 'Mobile Dev', 'high', 40, 'in-progress', '2024-08-15', '2024-12-01', '2025-09-11 10:53:39'),
(3, 'Database Migration Project', 'Migrate legacy database to modern MySQL infrastructure', 3, 'Database', 'medium', 90, 'in-progress', '2024-07-01', '2024-10-30', '2025-09-11 10:53:40'),
(4, 'API Integration System', 'Integrate third-party payment and shipping APIs', 4, 'Integration', 'medium', 60, 'in-progress', '2024-09-10', '2024-11-30', '2025-09-11 10:53:40'),
(5, 'Security Audit & Compliance', 'Complete security review and GDPR compliance implementation', 5, 'Security', 'high', 25, 'in-progress', '2024-09-20', '2024-12-15', '2025-09-11 10:53:41'),
(6, 'Cloud Infrastructure Setup', 'Migrate to AWS cloud infrastructure with auto-scaling', 6, 'DevOps', 'medium', 80, 'in-progress', '2024-08-01', '2024-10-15', '2025-09-11 10:53:41'),
(7, 'Internal Dashboard Development', 'Custom analytics dashboard for business intelligence', 7, 'Web Dev', 'low', 15, 'pending', '2024-10-01', '2024-12-30', '2025-09-11 10:53:41'),
(8, 'Legacy System Modernization', 'Upgrade old PHP system to modern framework', 8, 'Modernization', 'high', 5, 'planning', '2024-11-01', '2025-02-28', '2025-09-11 10:53:42');

-- --------------------------------------------------------

--
-- Table structure for table `project_assignments`
--

CREATE TABLE `project_assignments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'Developer',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_assignments`
--

INSERT INTO `project_assignments` (`id`, `project_id`, `user_id`, `role`, `assigned_at`) VALUES
(2, 1, 3, 'Frontend Developer', '2025-09-11 10:53:43'),
(3, 1, 4, 'Backend Developer', '2025-09-11 10:53:43'),
(5, 2, 3, 'Mobile Developer', '2025-09-11 10:53:44'),
(6, 2, 4, 'UI/UX Designer', '2025-09-11 10:53:45'),
(8, 3, 3, 'DevOps Engineer', '2025-09-11 10:53:46'),
(9, 4, 3, 'API Developer', '2025-09-11 10:53:46'),
(10, 4, 4, 'Integration Specialist', '2025-09-11 10:53:46'),
(12, 5, 4, 'Compliance Officer', '2025-09-11 10:53:47'),
(13, 6, 3, 'Cloud Architect', '2025-09-11 10:53:48'),
(14, 6, 4, 'DevOps Engineer', '2025-09-11 10:53:48'),
(15, 7, 3, 'Full Stack Developer', '2025-09-11 10:53:49');

-- --------------------------------------------------------

--
-- Table structure for table `project_comments`
--

CREATE TABLE `project_comments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `is_blocker` tinyint(1) DEFAULT 0,
  `parent_comment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_comments`
--

INSERT INTO `project_comments` (`id`, `project_id`, `user_id`, `comment`, `is_blocker`, `parent_comment_id`, `created_at`) VALUES
(2, 1, 3, 'Frontend development is 60% complete. Need API endpoints for user authentication.', 0, NULL, '2025-09-11 10:53:50'),
(3, 1, 4, 'Backend API development on track. Database schema finalized.', 0, NULL, '2025-09-11 10:53:51'),
(4, 1, 3, 'BLOCKER: Waiting for client to provide product images and content.', 1, NULL, '2025-09-11 10:53:51'),
(6, 2, 3, 'iOS version in development. Need testing on latest iOS versions.', 0, NULL, '2025-09-11 10:53:52'),
(7, 2, 4, 'BLOCKER: Waiting for App Store developer account credentials.', 1, NULL, '2025-09-11 10:53:52'),
(9, 3, 3, 'BLOCKER: Need client approval for maintenance window scheduling.', 1, NULL, '2025-09-11 10:53:53'),
(10, 4, 3, 'Payment gateway integration 70% complete.', 0, NULL, '2025-09-11 10:53:54'),
(11, 4, 4, 'Shipping API testing in progress. Documentation review needed.', 0, NULL, '2025-09-11 10:53:54'),
(13, 5, 4, 'GDPR compliance documentation in review.', 0, NULL, '2025-09-11 10:53:55'),
(14, 6, 3, 'AWS infrastructure setup complete. Auto-scaling configured.', 0, NULL, '2025-09-11 10:53:56'),
(15, 7, 3, 'Dashboard wireframes completed. Starting development.', 0, NULL, '2025-09-11 10:53:56'),
(17, 7, 1, 'gndnxgfhxdfg', 0, 15, '2025-09-12 06:34:49');

-- --------------------------------------------------------

--
-- Table structure for table `project_revenues`
--

CREATE TABLE `project_revenues` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `revenue_type` varchar(50) DEFAULT 'milestone',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `received_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT 'bank_transfer',
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_revenues`
--

INSERT INTO `project_revenues` (`id`, `project_id`, `client_id`, `revenue_type`, `amount`, `received_date`, `payment_method`, `reference_number`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 1, 'milestone', 5000.00, '2024-09-10', 'bank_transfer', 'TXN-001-2024', 'First milestone payment for website redesign project', 9, '2025-09-11 10:58:00'),
(2, 2, 2, 'initial_payment', 7500.00, '2024-09-15', 'credit_card', 'CC-002-2024', 'Initial payment for mobile app development', 9, '2025-09-11 10:58:00'),
(3, 3, 3, 'final_payment', 8900.00, '2024-09-20', 'wire_transfer', 'WIRE-003-2024', 'Final payment for database migration completion', 10, '2025-09-11 10:58:01'),
(4, 1, 1, 'milestone', 7500.00, '2024-09-25', 'bank_transfer', 'TXN-004-2024', 'Second milestone payment - frontend completion', 9, '2025-09-11 10:58:01'),
(5, 4, 4, 'milestone', 6200.00, '2024-09-28', 'check', 'CHK-005-2024', 'API integration milestone payment', 10, '2025-09-11 10:58:01'),
(6, 6, 6, 'initial_payment', 5500.00, '2024-10-01', 'bank_transfer', 'TXN-006-2024', 'Cloud infrastructure setup initial payment', 9, '2025-09-11 10:58:02'),
(7, 2, 2, 'milestone', 9200.00, '2024-10-03', 'credit_card', 'CC-007-2024', 'Mobile app beta version completion', 10, '2025-09-11 10:58:02'),
(8, 5, 5, 'milestone', 8800.00, '2024-10-05', 'wire_transfer', 'WIRE-008-2024', 'Security audit phase 1 completion', 9, '2025-09-11 10:58:03'),
(9, 7, 7, 'initial_payment', 3500.00, '2024-10-08', 'bank_transfer', 'TXN-009-2024', 'Dashboard development kickoff payment', 10, '2025-09-11 10:58:03');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `supplier_email` varchar(100) DEFAULT NULL,
  `supplier_phone` varchar(20) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `order_date` date NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat_rate` decimal(4,4) NOT NULL DEFAULT 0.1500,
  `vat_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_number`, `supplier_name`, `supplier_email`, `supplier_phone`, `project_id`, `status`, `order_date`, `expected_delivery`, `subtotal`, `vat_rate`, `vat_amount`, `total_amount`, `notes`, `created_by`, `created_at`) VALUES
(1, 'PO-2025-001', 'Office Supplies Ltd', NULL, NULL, NULL, 'approved', '2025-09-11', '2025-09-18', 1800.00, 0.1500, 270.00, 2070.00, 'Monthly office supplies and equipment for the team', 1, '2025-09-11 10:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `description`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 1, 'Ergonomic Office Chairs', 6.00, 150.00, 900.00, '2025-09-11 10:40:34'),
(2, 1, 'Standing Desk Converters', 4.00, 125.00, 500.00, '2025-09-11 10:40:34'),
(3, 1, 'Monitor Arms (Dual)', 5.00, 80.00, 400.00, '2025-09-11 10:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `quotation_number` varchar(50) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `quotation_date` date NOT NULL,
  `valid_until` date NOT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat_rate` decimal(4,4) NOT NULL DEFAULT 0.1500,
  `vat_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `converted_invoice_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `quotation_number`, `client_id`, `project_id`, `quotation_date`, `valid_until`, `status`, `subtotal`, `vat_rate`, `vat_amount`, `total_amount`, `notes`, `created_by`, `created_at`, `updated_at`, `converted_invoice_id`) VALUES
(4, 'QUO-2025-TEST', 1, NULL, '2025-09-11', '2025-10-11', 'completed', 2000.00, 0.1500, 300.00, 2300.00, 'Test quotation for conversion demo', 1, '2025-09-11 10:40:33', '2025-09-11 10:40:34', 2),
(5, 'QUO-2025-001', 1, 1, '2024-09-01', '2024-10-01', 'sent', 12500.00, 0.1500, 1875.00, 14375.00, 'Website redesign project including responsive design, SEO optimization, and content management system.', 9, '2025-09-11 10:55:04', '2025-09-11 10:55:04', NULL),
(6, 'QUO-2025-002', 2, 2, '2024-09-05', '2024-10-05', 'draft', 18750.00, 0.1500, 2812.50, 21562.50, 'Mobile application development for iOS and Android platforms with backend API integration.', 9, '2025-09-11 10:55:05', '2025-09-11 10:55:05', NULL),
(7, 'QUO-2025-003', 3, 3, '2024-09-10', '2024-10-10', 'sent', 8900.00, 0.1500, 1335.00, 10235.00, 'Database migration and optimization services including data backup and recovery procedures.', 10, '2025-09-11 10:55:05', '2025-09-11 10:55:05', NULL),
(8, 'QUO-2025-004', 4, 4, '2024-09-15', '2024-10-15', 'accepted', 15200.00, 0.1500, 2280.00, 17480.00, 'API integration services for payment processing, shipping, and inventory management systems.', 9, '2025-09-11 10:55:06', '2025-09-11 10:55:06', NULL),
(9, 'QUO-2025-005', 5, 5, '2024-09-20', '2024-10-20', 'draft', 22100.00, 0.1500, 3315.00, 25415.00, 'Comprehensive security audit and GDPR compliance implementation for enterprise systems.', 10, '2025-09-11 10:55:06', '2025-09-11 10:55:06', NULL),
(10, 'QUO-2025-006', 6, 6, '2024-09-25', '2024-10-25', 'sent', 13800.00, 0.1500, 2070.00, 15870.00, 'Cloud infrastructure setup and migration to AWS with auto-scaling and monitoring.', 9, '2025-09-11 10:55:07', '2025-09-11 10:55:07', NULL),
(11, 'QUO-2025-007', 7, 7, '2024-09-28', '2024-10-28', 'accepted', 9500.00, 0.1500, 1425.00, 10925.00, 'Custom analytics dashboard development with real-time reporting capabilities.', 10, '2025-09-11 10:55:07', '2025-09-11 10:55:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotation_items`
--

INSERT INTO `quotation_items` (`id`, `quotation_id`, `description`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 4, 'Software Development - Phase 1', 1.00, 1500.00, 1500.00, '2025-09-11 10:40:33'),
(2, 4, 'Testing & QA Services', 1.00, 500.00, 500.00, '2025-09-11 10:40:33'),
(14, 4, 'Payment Gateway Integration', 25.00, 140.00, 3500.00, '2025-09-11 10:55:13'),
(15, 4, 'Shipping API Implementation', 20.00, 120.00, 2400.00, '2025-09-11 10:55:13'),
(16, 4, 'Inventory Management System', 35.00, 135.00, 4725.00, '2025-09-11 10:55:14'),
(17, 4, 'Testing & Quality Assurance', 18.00, 85.00, 1530.00, '2025-09-11 10:55:14'),
(18, 5, 'Website Development Services', 1.00, 2500.00, 2500.00, '2025-09-11 11:20:29'),
(19, 5, 'Database Setup & Configuration', 8.00, 125.00, 1000.00, '2025-09-11 11:20:30'),
(20, 6, 'Website Development Services', 1.00, 2500.00, 2500.00, '2025-09-11 11:20:30'),
(21, 6, 'Database Setup & Configuration', 8.00, 125.00, 1000.00, '2025-09-11 11:20:31'),
(22, 7, 'Website Development Services', 1.00, 2500.00, 2500.00, '2025-09-11 11:20:31'),
(23, 7, 'Database Setup & Configuration', 8.00, 125.00, 1000.00, '2025-09-11 11:20:32'),
(24, 7, 'User Interface Design', 12.00, 95.00, 1140.00, '2025-09-11 11:20:32'),
(25, 8, 'Website Development Services', 1.00, 2500.00, 2500.00, '2025-09-11 11:20:33'),
(26, 8, 'Database Setup & Configuration', 8.00, 125.00, 1000.00, '2025-09-11 11:20:33'),
(27, 9, 'Website Development Services', 1.00, 2500.00, 2500.00, '2025-09-11 11:20:34'),
(28, 9, 'Database Setup & Configuration', 8.00, 125.00, 1000.00, '2025-09-11 11:20:34'),
(29, 10, 'Website Development Services', 1.00, 2500.00, 2500.00, '2025-09-11 11:20:34'),
(30, 10, 'Database Setup & Configuration', 8.00, 125.00, 1000.00, '2025-09-11 11:20:35'),
(31, 11, 'Website Development Services', 1.00, 2500.00, 2500.00, '2025-09-11 11:20:35'),
(32, 11, 'Database Setup & Configuration', 8.00, 125.00, 1000.00, '2025-09-11 11:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `social_media_posts`
--

CREATE TABLE `social_media_posts` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `platform` varchar(20) NOT NULL,
  `content` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `scheduled_for` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) DEFAULT 'draft',
  `engagement_stats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`engagement_stats`)),
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `social_media_posts`
--

INSERT INTO `social_media_posts` (`id`, `campaign_id`, `client_id`, `platform`, `content`, `image_url`, `scheduled_for`, `status`, `engagement_stats`, `created_by`, `created_at`) VALUES
(1, 1, NULL, 'Facebook', 'Check out our new summer collection! Perfect for the season ahead. #SummerStyle #NewArrivals', NULL, '2024-09-15 08:00:00', 'published', NULL, 5, '2025-09-11 10:54:16'),
(2, 1, NULL, 'Instagram', 'Behind the scenes of our product photoshoot! 📸 What\'s your favorite piece from our new collection?', NULL, '2024-09-16 12:30:00', 'published', NULL, 6, '2025-09-11 10:54:16'),
(3, 1, NULL, 'Twitter', 'Summer sale starts NOW! Get 20% off all items. Limited time offer. #SummerSale #LimitedOffer', NULL, '2024-09-17 07:00:00', 'published', NULL, 5, '2025-09-11 10:54:16'),
(4, 1, NULL, 'LinkedIn', 'We\'re excited to announce the launch of our innovative product line, designed with modern professionals in mind.', NULL, '2024-09-18 09:00:00', 'scheduled', NULL, 6, '2025-09-11 10:54:17'),
(5, 3, NULL, 'Facebook', 'What makes a brand memorable? Our latest blog post explores the psychology of brand recognition.', NULL, '2024-09-20 11:00:00', 'scheduled', NULL, 5, '2025-09-11 10:54:17'),
(6, 3, NULL, 'Instagram', 'Brand storytelling matters! 📖 Here\'s how we craft authentic stories that resonate with our audience.', NULL, '2024-09-21 14:00:00', 'scheduled', NULL, 6, '2025-09-11 10:54:18'),
(7, 5, NULL, 'TikTok', 'Quick tip Tuesday! Here\'s a 30-second productivity hack that will change your workday. #ProductivityTips', NULL, '2024-09-22 17:00:00', 'draft', NULL, 6, '2025-09-11 10:54:18'),
(8, 5, NULL, 'Instagram', 'Community spotlight! 🌟 Featuring amazing content from our followers. Tag us to be featured next!', NULL, '2024-09-23 10:00:00', 'scheduled', NULL, 5, '2025-09-11 10:54:19'),
(9, 7, NULL, 'Facebook', 'Refer a friend and you both save! Our referral program is now live. Win-win for everyone! 🤝', NULL, '2024-09-25 08:30:00', 'scheduled', NULL, 6, '2025-09-11 10:54:19'),
(10, 8, NULL, 'YouTube', 'New video alert! Learn the top 5 strategies for effective time management in our latest tutorial.', NULL, '2024-09-26 13:00:00', 'draft', NULL, 5, '2025-09-11 10:54:20'),
(11, 8, 6, 'Instagram', 'Test Campaign', NULL, '2025-09-17 06:32:00', 'draft', NULL, NULL, '2025-09-12 06:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `system_activity`
--

CREATE TABLE `system_activity` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `department` varchar(50) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'employee',
  `department` varchar(20) DEFAULT 'IT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `department`, `created_at`) VALUES
(1, 'admin', 'admin@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'admin', 'IT', '2025-09-11 10:40:32'),
(3, 'john_manager', 'john@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'manager', 'IT', '2025-09-11 10:53:18'),
(4, 'sarah_dev', 'sarah@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'employee', 'IT', '2025-09-11 10:53:18'),
(5, 'mike_dev', 'mike@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'employee', 'IT', '2025-09-11 10:53:19'),
(6, 'lisa_marketing', 'lisa@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'manager', 'Marketing', '2025-09-11 10:53:19'),
(7, 'tom_marketing', 'tom@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'employee', 'Marketing', '2025-09-11 10:53:20'),
(8, 'jane_hr', 'jane@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'manager', 'HR', '2025-09-11 10:53:20'),
(9, 'bob_hr', 'bob@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'employee', 'HR', '2025-09-11 10:53:20'),
(10, 'emma_finance', 'emma@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'manager', 'Finance', '2025-09-11 10:53:21'),
(11, 'david_finance', 'david@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'employee', 'Finance', '2025-09-11 10:53:21'),
(12, 'anna_clients', 'anna@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'manager', 'Clients', '2025-09-11 10:53:22'),
(13, 'peter_clients', 'peter@company.com', '$2y$10$8Ls0PpUeS.1xIcTYi2vN7e/GK/2jV23amBgiEX78kTKiEioFUxMoS', 'employee', 'Clients', '2025-09-11 10:53:22');

-- --------------------------------------------------------

--
-- Table structure for table `user_activities`
--

CREATE TABLE `user_activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `page_url` varchar(255) DEFAULT NULL,
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `additional_data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_activities`
--

INSERT INTO `user_activities` (`id`, `user_id`, `username`, `activity_type`, `description`, `page_url`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `session_id`, `additional_data`, `created_at`) VALUES
(1, 1, 'admin_user', 'login', 'User logged in successfully', '/auth/login.php', 'user', 1, '192.168.1.100', NULL, NULL, NULL, '2024-01-15 07:30:00'),
(2, 2, 'manager_john', 'create', 'Created new project', '/projects/create.php', 'project', 101, '192.168.1.101', NULL, NULL, NULL, '2024-01-15 08:15:00'),
(3, 1, 'admin_user', 'update', 'Updated user permissions', '/admin/users/edit.php', 'user', 2, '192.168.1.100', NULL, NULL, NULL, '2024-01-15 09:20:00'),
(4, 3, 'user_sarah', 'view', 'Viewed dashboard', '/dashboard.php', 'page', NULL, '192.168.1.102', NULL, NULL, NULL, '2024-01-15 10:05:00'),
(5, 2, 'manager_john', 'delete', 'Deleted old report', '/reports/delete.php', 'report', 205, '192.168.1.101', NULL, NULL, NULL, '2024-01-15 11:40:00'),
(6, 4, 'finance_mike', 'download', 'Downloaded financial report', '/reports/download.php', 'report', 301, '192.168.1.103', NULL, NULL, NULL, '2024-01-15 12:25:00'),
(7, 1, 'admin_user', 'logout', 'User logged out', '/auth/logout.php', 'user', 1, '192.168.1.100', NULL, NULL, NULL, '2024-01-15 13:10:00'),
(8, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:13:11'),
(9, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:30'),
(10, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:32'),
(11, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:34'),
(12, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:36'),
(13, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:40'),
(14, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:42'),
(15, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:43'),
(16, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:46'),
(17, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:47'),
(18, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:14:49'),
(19, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:25:47'),
(20, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ae1ca201c01557a91758743832e51096', NULL, '2025-09-12 07:25:49'),
(21, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'c995abf988908f96e1320c84ed9faa96', NULL, '2025-09-12 07:29:06'),
(22, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'c995abf988908f96e1320c84ed9faa96', NULL, '2025-09-12 07:29:06'),
(23, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'c995abf988908f96e1320c84ed9faa96', NULL, '2025-09-12 07:29:09'),
(24, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?ajax=view_employee&id=7', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'c995abf988908f96e1320c84ed9faa96', NULL, '2025-09-12 07:29:21'),
(25, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?ajax=get_department_managers&dept=Marketing', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'c995abf988908f96e1320c84ed9faa96', NULL, '2025-09-12 07:29:21'),
(26, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'c995abf988908f96e1320c84ed9faa96', NULL, '2025-09-12 07:30:14'),
(27, 6, 'lisa_marketing', 'login', 'User \'lisa_marketing\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:30:37'),
(28, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:30:37'),
(29, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:30:44'),
(30, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:30:55'),
(31, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:30:56'),
(32, 6, 'lisa_marketing', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:30:58'),
(33, 6, 'lisa_marketing', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:31:03'),
(34, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:31:08'),
(35, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:33:05'),
(36, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:33:50'),
(37, 6, 'lisa_marketing', 'logout', 'User \'lisa_marketing\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9b628638641481e928571871836b5b8b', NULL, '2025-09-12 07:34:41'),
(38, 6, 'lisa_marketing', 'login', 'User \'lisa_marketing\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:34:43'),
(39, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:34:43'),
(40, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:36:33'),
(41, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:36:48'),
(42, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:38:10'),
(43, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:41:23'),
(44, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:41:25'),
(45, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:41:51'),
(46, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:41:53'),
(47, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:41:55'),
(48, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:41:58'),
(49, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:13'),
(50, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:13'),
(51, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:27'),
(52, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:28'),
(53, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:28'),
(54, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:30'),
(55, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:32'),
(56, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:32'),
(57, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:33'),
(58, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:34'),
(59, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:34'),
(60, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:36'),
(61, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:37'),
(62, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:37'),
(63, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:38'),
(64, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:39'),
(65, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:39'),
(66, 6, 'lisa_marketing', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:40'),
(67, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:42'),
(68, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:42'),
(69, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:43'),
(70, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:42:44'),
(71, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:19'),
(72, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:21'),
(73, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:21'),
(74, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:22'),
(75, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:22'),
(76, 6, 'lisa_marketing', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:24'),
(77, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:49:24'),
(78, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:50:20'),
(79, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:50:25'),
(80, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:50:28'),
(81, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:50:34'),
(82, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:50:37'),
(83, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:50:47'),
(84, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:19'),
(85, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:24'),
(86, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:26'),
(87, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:29'),
(88, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:46'),
(89, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:46'),
(90, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:48'),
(91, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:48'),
(92, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:51'),
(93, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:51'),
(94, 6, 'lisa_marketing', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:53'),
(95, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:54:53'),
(96, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:56:26'),
(97, 6, 'lisa_marketing', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:56:28'),
(98, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:56:31'),
(99, 6, 'lisa_marketing', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:57:44'),
(100, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:57:47'),
(101, 6, 'lisa_marketing', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:57:49'),
(102, 6, 'lisa_marketing', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:57:52'),
(103, 6, 'lisa_marketing', 'logout', 'User \'lisa_marketing\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '006b493d300d34b470233988952b9ea6', NULL, '2025-09-12 07:57:54'),
(104, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '382961dda4ac686b821e2f245d659829', NULL, '2025-09-12 07:57:57'),
(105, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '382961dda4ac686b821e2f245d659829', NULL, '2025-09-12 07:57:57'),
(106, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '382961dda4ac686b821e2f245d659829', NULL, '2025-09-12 07:58:58'),
(107, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '382961dda4ac686b821e2f245d659829', NULL, '2025-09-12 07:59:03'),
(108, 3, 'john_manager', 'login', 'User \'john_manager\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:01:30'),
(109, 3, 'john_manager', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:01:30'),
(110, 3, 'john_manager', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:01:34'),
(111, 3, 'john_manager', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:01:37'),
(112, 3, 'john_manager', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:01:38'),
(113, 3, 'john_manager', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:01:39'),
(114, 3, 'john_manager', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:02:17'),
(115, 3, 'john_manager', 'logout', 'User \'john_manager\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'e21209f39bd31f93a1cb46a10553513b', NULL, '2025-09-12 08:02:19'),
(116, 8, 'jane_hr', 'login', 'User \'jane_hr\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9cab475def0b378d6e4d62ba53fc32f7', NULL, '2025-09-12 08:02:26'),
(117, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9cab475def0b378d6e4d62ba53fc32f7', NULL, '2025-09-12 08:02:26'),
(118, 8, 'jane_hr', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9cab475def0b378d6e4d62ba53fc32f7', NULL, '2025-09-12 08:02:28'),
(119, 8, 'jane_hr', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9cab475def0b378d6e4d62ba53fc32f7', NULL, '2025-09-12 08:02:31'),
(120, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9cab475def0b378d6e4d62ba53fc32f7', NULL, '2025-09-12 08:02:34'),
(121, 8, 'jane_hr', 'logout', 'User \'jane_hr\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '9cab475def0b378d6e4d62ba53fc32f7', NULL, '2025-09-12 08:03:16'),
(122, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:03:42'),
(123, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:03:42'),
(124, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:04:18'),
(125, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:04:23'),
(126, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:04:25'),
(127, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:04:31'),
(128, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'cd394192b7f7c0b757d0ba3bb9daf8d5', NULL, '2025-09-12 08:04:34'),
(129, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:13:47'),
(130, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:13:47'),
(131, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:19:34'),
(132, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/departments/project_detail.php?id=8', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:19:40'),
(133, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/departments/project_detail.php?id=8', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:19:40'),
(134, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:20:06'),
(135, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:20:14'),
(136, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:20:20'),
(137, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:20:28'),
(138, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:20:45'),
(139, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:21:55'),
(140, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:21:57'),
(141, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:22:00'),
(142, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:22:40'),
(143, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:22:48'),
(144, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.116.144.29', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/28.0 Chrome/130.0.0.0 Mobile Safari/537.36', 'fa7f87fcec4432b9bb8aa73e34831c17', NULL, '2025-09-12 08:22:58'),
(145, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:01'),
(146, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:02'),
(147, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:07'),
(148, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:10'),
(149, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:13'),
(150, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:14'),
(151, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'ad1f4223616659226e647e2161b613cb', NULL, '2025-09-12 08:47:16'),
(152, 8, 'jane_hr', 'login', 'User \'jane_hr\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:47:20'),
(153, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:47:20'),
(154, 8, 'jane_hr', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:47:21'),
(155, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:47:24'),
(156, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:48:21'),
(157, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:48:22'),
(158, 8, 'jane_hr', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:49:32'),
(159, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:49:35'),
(160, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:58:24'),
(161, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:58:26'),
(162, 8, 'jane_hr', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:58:33'),
(163, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:58:36'),
(164, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:58:38'),
(165, 8, 'jane_hr', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:59:01'),
(166, 8, 'jane_hr', 'logout', 'User \'jane_hr\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '72bdc45ce79885834d1f7440ea9fc8ec', NULL, '2025-09-12 08:59:40'),
(167, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '4f1b55223c3c69e40e67368b3fd5d4b9', NULL, '2025-09-12 08:59:44'),
(168, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '4f1b55223c3c69e40e67368b3fd5d4b9', NULL, '2025-09-12 08:59:44');
INSERT INTO `user_activities` (`id`, `user_id`, `username`, `activity_type`, `description`, `page_url`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `session_id`, `additional_data`, `created_at`) VALUES
(169, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '4f1b55223c3c69e40e67368b3fd5d4b9', NULL, '2025-09-12 09:02:33'),
(170, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '4f1b55223c3c69e40e67368b3fd5d4b9', NULL, '2025-09-12 09:02:40'),
(171, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'acd47bc116e261c3b1db309c5a4d47de', NULL, '2025-09-12 09:38:37'),
(172, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'acd47bc116e261c3b1db309c5a4d47de', NULL, '2025-09-12 09:38:38'),
(173, 1, 'admin', 'logout', 'User \'admin\' logged out', '/auth/logout.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'acd47bc116e261c3b1db309c5a4d47de', NULL, '2025-09-12 09:38:57'),
(174, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 09:39:05'),
(175, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 09:39:05'),
(176, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?action=new_employee', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:34:19'),
(177, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:35:49'),
(178, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:35:51'),
(179, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:36:33'),
(180, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:36:35'),
(181, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:36:59'),
(182, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php?ajax=view_quotation&id=9', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:57:08'),
(183, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:57:19'),
(184, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php?ajax=view_quotation&id=5', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:57:32'),
(185, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:58:05'),
(186, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:58:05'),
(187, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php?action=new_project', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:58:22'),
(188, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php?view=projects', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:58:33'),
(189, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/departments/project_detail.php?id=8', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:58:47'),
(190, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:59:21'),
(191, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:59:24'),
(192, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:59:27'),
(193, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:59:28'),
(194, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:59:28'),
(195, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?action=new_campaign', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:59:32'),
(196, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:59:35'),
(197, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 10:59:48'),
(198, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'fa025af2f6ade92bfc4665a4b3fe93c1', NULL, '2025-09-12 10:59:58'),
(199, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=overview', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:00:36'),
(200, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:00:46'),
(201, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=overview', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:00:59'),
(202, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:01:05'),
(203, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=overview', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:01:13'),
(204, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:01:18'),
(205, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=overview', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:01:22'),
(206, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:02:07'),
(207, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:03:32'),
(208, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php?action=new_client', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:03:35'),
(209, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php?action=new_client', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:04:46'),
(210, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:05:07'),
(211, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:05:07'),
(212, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:05:11'),
(213, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:05:20'),
(214, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:05:58'),
(215, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:06:12'),
(216, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php?action=new_client', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:06:21'),
(217, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:06:56'),
(218, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:07:38'),
(219, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:07:40'),
(220, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:07:52'),
(221, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.101 Mobile/15E148 Safari/604.1', 'd623e949cfac2ceae75cca448ca4d25f', NULL, '2025-09-12 11:07:52'),
(222, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:09:26'),
(223, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?ajax=view_employee&id=7', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:10:03'),
(224, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?ajax=get_department_managers&dept=Marketing', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:10:03'),
(225, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?ajax=view_employee&id=8', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:10:27'),
(226, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php?ajax=get_department_managers&dept=Finance', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:10:27'),
(227, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:11:07'),
(228, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:25:53'),
(229, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:00'),
(230, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:09'),
(231, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:13'),
(232, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:16'),
(233, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:17'),
(234, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:19'),
(235, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:21'),
(236, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:23'),
(237, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:24'),
(238, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:31'),
(239, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '31b097e23105d7d950ca6ab6f11a2173', NULL, '2025-09-12 11:26:33'),
(240, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:30:01'),
(241, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:30:01'),
(242, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:31:11'),
(243, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:31:28'),
(244, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:33:32'),
(245, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:34:59'),
(246, 1, 'admin', 'create', 'Created performance review for employee ID 7', '/departments/hr.php', 'performance_review', 1, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', '{\"employee_id\":7,\"reviewer_id\":8,\"review_period_start\":\"\",\"review_period_end\":\"\",\"overall_rating\":3}', '2025-09-12 12:34:59'),
(247, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:38:19'),
(248, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:38:51'),
(249, 1, 'admin', 'create', 'Created performance review for employee ID 5', '/departments/hr.php', 'performance_review', 2, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', '{\"employee_id\":5,\"reviewer_id\":5,\"review_period_start\":\"2025-09-01\",\"review_period_end\":\"2025-09-12\",\"overall_rating\":3}', '2025-09-12 12:38:51'),
(250, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:39:45'),
(251, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:41:35'),
(252, 1, 'admin', 'create', 'Created personal leave request for 14 days', '/departments/hr.php', 'leave_request', 19, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', '{\"employee_id\":5,\"leave_type\":\"personal\",\"start_date\":\"2025-09-12\",\"end_date\":\"2025-09-25\",\"days_requested\":14}', '2025-09-12 12:41:35'),
(253, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:18'),
(254, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php?ajax=view_quotation&id=10', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:22'),
(255, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php?ajax=view_quotation&id=10', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:25'),
(256, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:26'),
(257, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:38'),
(258, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:40'),
(259, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:52'),
(260, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:42:58'),
(261, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:43:05'),
(262, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:43:17'),
(263, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:46:04'),
(264, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:46:33'),
(265, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:52:39'),
(266, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:52:40'),
(267, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 12:54:03'),
(268, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:10:16'),
(269, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:10:50'),
(270, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:10:52'),
(271, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:10:53'),
(272, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:11:57'),
(273, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:11:58'),
(274, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:12:06'),
(275, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:12:09'),
(276, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:15:44'),
(277, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:15:46'),
(278, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:15:53'),
(279, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:15:54'),
(280, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:16:36'),
(281, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:18:33'),
(282, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:18:34'),
(283, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:20:56'),
(284, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:20:57'),
(285, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:22:54'),
(286, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:22:55'),
(287, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:23:05'),
(288, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:23:07'),
(289, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:27:05'),
(290, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:27:06'),
(291, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:27:08'),
(292, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:27:17'),
(293, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:27:19'),
(294, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:01'),
(295, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:11'),
(296, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:27'),
(297, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:35'),
(298, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:37'),
(299, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:39'),
(300, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:41'),
(301, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:44'),
(302, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:45'),
(303, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:47'),
(304, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:31:49'),
(305, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:32:00'),
(306, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:32:01'),
(307, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:32:01'),
(308, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:32:07'),
(309, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:32:11'),
(310, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:32:17'),
(311, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:48:50'),
(312, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:48:51'),
(313, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:51:21'),
(314, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:51:23'),
(315, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:53:18'),
(316, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:54:00'),
(317, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:54:05'),
(318, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:54:07'),
(319, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:54:57'),
(320, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:56:44'),
(321, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:56:56'),
(322, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:58:38'),
(323, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:59:17'),
(324, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:59:19');
INSERT INTO `user_activities` (`id`, `user_id`, `username`, `activity_type`, `description`, `page_url`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `session_id`, `additional_data`, `created_at`) VALUES
(325, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:59:20'),
(326, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:59:22'),
(327, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:59:23'),
(328, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 13:59:28'),
(329, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 14:00:48'),
(330, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 14:01:19'),
(331, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 14:01:45'),
(332, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 14:01:48'),
(333, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 14:02:29'),
(334, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'd8914ced0a2a485b6b993636cf6c3b90', NULL, '2025-09-12 14:02:33'),
(335, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:39:52'),
(336, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:39:52'),
(337, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:41:04'),
(338, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:43:26'),
(339, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:44:07'),
(340, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:44:51'),
(341, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:44:58'),
(342, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:45:31'),
(343, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.132.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'da9851281ebaf495f4dd39e6febe3840', NULL, '2025-09-12 17:45:33'),
(344, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-18 10:04:33'),
(345, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-18 10:04:33'),
(346, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-18 10:04:46'),
(347, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-18 10:04:49'),
(348, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-18 10:04:55'),
(349, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=blog-posts', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:11:10'),
(350, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:12:03'),
(351, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:13:18'),
(352, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:14:16'),
(353, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:14:47'),
(354, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:17:30'),
(355, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:17:36'),
(356, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:17:55'),
(357, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:17:57'),
(358, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:18:13'),
(359, 1, 'admin', 'edit', 'Approved leave request', '/departments/hr.php', 'leave_request', 9, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', '{\"new_status\":\"approved\",\"approved_by\":1}', '2025-09-19 18:18:13'),
(360, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', NULL, '2025-09-19 18:18:33'),
(361, 1, 'admin', 'edit', 'Rejected leave request', '/departments/hr.php', 'leave_request', 14, '41.56.239.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '1d04ae0884e93341b8ffd683b9cefab6', '{\"new_status\":\"rejected\",\"approved_by\":1}', '2025-09-19 18:18:33'),
(362, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 08:54:36'),
(363, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 08:54:36'),
(364, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:06:59'),
(365, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:07:50'),
(366, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:07:59'),
(367, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:08:39'),
(368, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:14:23'),
(369, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php?view=projects', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:19:55'),
(370, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php?view=create', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:19:59'),
(371, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php?view=projects', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:24:57'),
(372, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/departments/project_detail.php?id=8', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:25:13'),
(373, 1, 'admin', 'page_visit', 'Viewed project detail page (ID: 8)', '/departments/project_detail.php?id=8', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:25:13'),
(374, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:25:36'),
(375, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:25:41'),
(376, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:25:43'),
(377, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=campaigns', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:27:39'),
(378, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:27:50'),
(379, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:27:52'),
(380, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:28:08'),
(381, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=overview', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:28:16'),
(382, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 09:29:55'),
(383, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 10:04:08'),
(384, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 11:01:44'),
(385, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 11:02:07'),
(386, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 11:02:34'),
(387, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 11:03:19'),
(388, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.177.171', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'c8b26ce18bfa9768061dcc5463725fc3', NULL, '2025-09-22 11:03:27'),
(389, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '197.184.74.106', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.122 Mobile/15E148 Safari/604.1', '26658a0152f04e66b071c19afeaca0dc', NULL, '2025-09-23 00:28:28'),
(390, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '197.184.74.106', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_0_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/140.0.7339.122 Mobile/15E148 Safari/604.1', '26658a0152f04e66b071c19afeaca0dc', NULL, '2025-09-23 00:28:28'),
(391, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.185.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'ae8640d8170ee1c9978dd4fe60c301d2', NULL, '2025-10-14 14:33:36'),
(392, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.185.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'ae8640d8170ee1c9978dd4fe60c301d2', NULL, '2025-10-14 14:33:36'),
(393, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.185.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'ae8640d8170ee1c9978dd4fe60c301d2', NULL, '2025-10-14 14:33:54'),
(394, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.185.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'ae8640d8170ee1c9978dd4fe60c301d2', NULL, '2025-10-14 17:15:03'),
(395, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.185.38', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'ae8640d8170ee1c9978dd4fe60c301d2', NULL, '2025-10-15 09:38:00'),
(396, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:43:24'),
(397, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:43:24'),
(398, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:43:31'),
(399, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php?ajax=view_quotation&id=11', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:43:47'),
(400, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:44:53'),
(401, 1, 'admin', 'page_visit', 'Viewed clients management page', '/departments/clients.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:44:55'),
(402, 1, 'admin', 'page_visit', 'Visited HR Department page', '/departments/hr.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:45:02'),
(403, 1, 'admin', 'page_visit', 'Visited Finance Department page', '/departments/finance.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:26'),
(404, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:28'),
(405, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:30'),
(406, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:35'),
(407, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar&month=09&year=2025', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:39'),
(408, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar&month=08&year=2025', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:42'),
(409, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-calendar&month=09&year=2025', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:45'),
(410, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=social-posts', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:46'),
(411, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php?view=email-campaigns', NULL, NULL, '41.56.164.136', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '7ffd1a49c39b5a27537347c9b3797cfa', NULL, '2025-10-24 16:56:55'),
(412, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '75bf9bbb833c0bbd6b19bc5369cec177', NULL, '2025-10-28 21:01:00'),
(413, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '75bf9bbb833c0bbd6b19bc5369cec177', NULL, '2025-10-28 21:01:00'),
(414, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '75bf9bbb833c0bbd6b19bc5369cec177', NULL, '2025-10-28 21:04:35'),
(415, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '75bf9bbb833c0bbd6b19bc5369cec177', NULL, '2025-10-28 21:04:49'),
(416, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '75bf9bbb833c0bbd6b19bc5369cec177', NULL, '2025-10-29 08:22:07'),
(417, 1, 'admin', 'login', 'User \'admin\' logged in successfully', '/auth/login.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:18:47'),
(418, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:18:48'),
(419, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:17'),
(420, 1, 'admin', 'page_visit', 'Visited Business Insights page', '/departments/insights.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:19'),
(421, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:24'),
(422, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:27'),
(423, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:28'),
(424, 1, 'admin', 'page_visit', 'Visited Marketing Department page', '/departments/marketing.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:29'),
(425, 1, 'admin', 'page_visit', 'Visited It page', '/departments/it.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:32'),
(426, 1, 'admin', 'page_visit', 'User accessed main dashboard', '/dashboard.php', NULL, NULL, '41.56.218.63', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '16e838bbc8ce50fa6d780c1cda1c01f3', NULL, '2025-11-01 19:19:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_posting_id` (`job_posting_id`);

--
-- Indexes for table `candidate_education`
--
ALTER TABLE `candidate_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indexes for table `candidate_work_experience`
--
ALTER TABLE `candidate_work_experience`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `client_contacts`
--
ALTER TABLE `client_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `fk_contact_assigned` (`assigned_to`);

--
-- Indexes for table `client_meetings`
--
ALTER TABLE `client_meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `consultation_requests`
--
ALTER TABLE `consultation_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_consultation_assigned` (`assigned_to`);

--
-- Indexes for table `custom_reports`
--
ALTER TABLE `custom_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `email_campaigns`
--
ALTER TABLE `email_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `marketing_campaign_id` (`marketing_campaign_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_email_campaign_client` (`client_id`);

--
-- Indexes for table `email_recipients`
--
ALTER TABLE `email_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_campaign_id` (`email_campaign_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `hr_employees`
--
ALTER TABLE `hr_employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `hr_leave_requests`
--
ALTER TABLE `hr_leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `quotation_id` (`quotation_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `marketing_blog_posts`
--
ALTER TABLE `marketing_blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_campaign_client` (`client_id`);

--
-- Indexes for table `money_flow`
--
ALTER TABLE `money_flow`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `reviewer_id` (`reviewer_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_id` (`project_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `project_comments`
--
ALTER TABLE `project_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_comment_id` (`parent_comment_id`);

--
-- Indexes for table `project_revenues`
--
ALTER TABLE `project_revenues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotation_number` (`quotation_number`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `social_media_posts`
--
ALTER TABLE `social_media_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_social_post_client` (`client_id`);

--
-- Indexes for table `system_activity`
--
ALTER TABLE `system_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_activity` (`user_id`,`created_at`),
  ADD KEY `idx_department_activity` (`department`,`created_at`),
  ADD KEY `idx_activity_type` (`activity_type`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activities`
--
ALTER TABLE `user_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_resource` (`resource_type`,`resource_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `candidate_education`
--
ALTER TABLE `candidate_education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `candidate_work_experience`
--
ALTER TABLE `candidate_work_experience`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `client_contacts`
--
ALTER TABLE `client_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_meetings`
--
ALTER TABLE `client_meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consultation_requests`
--
ALTER TABLE `consultation_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `custom_reports`
--
ALTER TABLE `custom_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_campaigns`
--
ALTER TABLE `email_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `email_recipients`
--
ALTER TABLE `email_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `hr_employees`
--
ALTER TABLE `hr_employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `hr_leave_requests`
--
ALTER TABLE `hr_leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marketing_blog_posts`
--
ALTER TABLE `marketing_blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `money_flow`
--
ALTER TABLE `money_flow`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `project_assignments`
--
ALTER TABLE `project_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `project_comments`
--
ALTER TABLE `project_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `project_revenues`
--
ALTER TABLE `project_revenues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `social_media_posts`
--
ALTER TABLE `social_media_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `system_activity`
--
ALTER TABLE `system_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_activities`
--
ALTER TABLE `user_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=427;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_education`
--
ALTER TABLE `candidate_education`
  ADD CONSTRAINT `candidate_education_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_work_experience`
--
ALTER TABLE `candidate_work_experience`
  ADD CONSTRAINT `candidate_work_experience_ibfk_1` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_contacts`
--
ALTER TABLE `client_contacts`
  ADD CONSTRAINT `client_contacts_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_contact_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `client_meetings`
--
ALTER TABLE `client_meetings`
  ADD CONSTRAINT `client_meetings_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_meetings_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `consultation_requests`
--
ALTER TABLE `consultation_requests`
  ADD CONSTRAINT `fk_consultation_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `custom_reports`
--
ALTER TABLE `custom_reports`
  ADD CONSTRAINT `custom_reports_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `email_campaigns`
--
ALTER TABLE `email_campaigns`
  ADD CONSTRAINT `email_campaigns_ibfk_1` FOREIGN KEY (`marketing_campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `email_campaigns_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_email_campaign_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `email_recipients`
--
ALTER TABLE `email_recipients`
  ADD CONSTRAINT `email_recipients_ibfk_1` FOREIGN KEY (`email_campaign_id`) REFERENCES `email_campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_employees`
--
ALTER TABLE `hr_employees`
  ADD CONSTRAINT `hr_employees_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `hr_employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hr_leave_requests`
--
ALTER TABLE `hr_leave_requests`
  ADD CONSTRAINT `hr_leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD CONSTRAINT `job_postings_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `marketing_blog_posts`
--
ALTER TABLE `marketing_blog_posts`
  ADD CONSTRAINT `marketing_blog_posts_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `marketing_blog_posts_ibfk_2` FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `marketing_blog_posts_ibfk_3` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `marketing_campaigns`
--
ALTER TABLE `marketing_campaigns`
  ADD CONSTRAINT `fk_campaign_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `marketing_campaigns_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `money_flow`
--
ALTER TABLE `money_flow`
  ADD CONSTRAINT `money_flow_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `money_flow_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `money_flow_ibfk_3` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `money_flow_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD CONSTRAINT `performance_reviews_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `hr_employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `performance_reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD CONSTRAINT `project_assignments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_comments`
--
ALTER TABLE `project_comments`
  ADD CONSTRAINT `project_comments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `project_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `project_comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_revenues`
--
ALTER TABLE `project_revenues`
  ADD CONSTRAINT `project_revenues_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_revenues_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_revenues_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quotations_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quotations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `social_media_posts`
--
ALTER TABLE `social_media_posts`
  ADD CONSTRAINT `fk_social_post_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `social_media_posts_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `social_media_posts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_activity`
--
ALTER TABLE `system_activity`
  ADD CONSTRAINT `system_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
