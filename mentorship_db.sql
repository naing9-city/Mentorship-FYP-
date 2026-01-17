-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 16, 2026 at 04:23 PM
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
-- Database: `mentorship_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_keys`
--

CREATE TABLE `admin_keys` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_keys`
--

INSERT INTO `admin_keys` (`id`, `token`, `is_used`, `created_at`) VALUES
(1, '37633072', 0, '2026-01-09 18:04:11'),
(2, '98204458', 0, '2026-01-09 18:06:14'),
(3, '64175918', 0, '2026-01-09 18:06:18'),
(4, '21423524', 0, '2026-01-09 18:13:59'),
(5, '18294133', 1, '2026-01-09 18:28:10'),
(6, '24305659', 0, '2026-01-15 18:00:38'),
(7, '15302209', 1, '2026-01-16 10:23:16');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `mentor_id` int(11) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `points` decimal(10,2) DEFAULT 0.00,
  `duration_minutes` int(11) DEFAULT NULL,
  `status` enum('pending','paid','accepted','rejected','completed','cancelled') DEFAULT 'pending',
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `mentor_paid` tinyint(1) DEFAULT 0,
  `room_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `student_id`, `mentor_id`, `scheduled_at`, `points`, `duration_minutes`, `status`, `price`, `created_at`, `mentor_paid`, `room_id`) VALUES
(1, 28, 27, '2025-11-24 06:13:00', 10.00, 60, 'completed', 10.00, '2025-11-24 04:13:39', 0, NULL),
(2, 27, 27, '2025-11-14 04:21:00', 10.00, 30, 'completed', 10.00, '2025-11-24 04:22:07', 0, NULL),
(3, 27, 27, '2025-11-06 04:25:00', 10.00, 30, 'completed', 10.00, '2025-11-24 04:25:21', 0, NULL),
(4, 28, 27, '2025-11-06 04:27:00', 10.00, 30, 'completed', 10.00, '2025-11-24 04:27:26', 0, NULL),
(5, 27, 27, '2025-11-06 04:27:00', 10.00, 60, 'completed', 10.00, '2025-11-24 04:40:25', 0, NULL),
(6, 27, 27, '2025-11-07 04:43:00', 10.00, 60, 'completed', 10.00, '2025-11-24 04:43:31', 0, NULL),
(7, 27, 27, '2025-11-05 04:48:00', 10.00, 60, 'rejected', 10.00, '2025-11-24 04:48:46', 0, NULL),
(8, 27, 27, '2025-11-08 05:07:00', 20.00, 120, 'completed', 20.00, '2025-11-24 05:07:34', 0, NULL),
(9, 27, 27, '2025-11-04 05:18:00', 20.00, 120, 'completed', 20.00, '2025-11-24 05:18:48', 0, NULL),
(10, 27, 27, '2025-11-04 05:18:00', 20.00, 120, 'completed', 20.00, '2025-11-24 05:19:25', 0, NULL),
(11, 17, 29, '2025-11-14 05:26:00', 30.00, 180, 'completed', 30.00, '2025-11-24 05:27:00', 0, NULL),
(12, 29, 29, '2025-11-07 05:36:00', 10.00, 60, 'rejected', 10.00, '2025-11-24 05:36:30', 0, NULL),
(13, 29, 27, '2025-11-21 05:41:00', 10.00, 60, 'rejected', 10.00, '2025-11-24 05:41:08', 0, NULL),
(14, 29, 29, '2025-11-13 05:41:00', 10.00, 60, 'pending', 10.00, '2025-11-24 05:41:58', 0, NULL),
(15, NULL, 35, '2025-11-14 16:51:00', 10.00, 60, 'completed', 10.00, '2025-11-24 16:51:38', 0, NULL),
(16, NULL, 35, '2025-11-06 16:58:00', 20.00, 120, 'completed', 20.00, '2025-11-24 16:58:37', 0, NULL),
(17, NULL, 35, '2025-11-05 17:07:00', 50.00, 300, 'completed', 50.00, '2025-11-24 17:07:11', 0, NULL),
(18, NULL, 35, '2025-11-15 17:11:00', 10.00, 60, 'completed', 10.00, '2025-11-24 17:11:38', 0, NULL),
(19, 38, 37, '2025-11-12 17:50:00', 20.00, 120, 'completed', 20.00, '2025-11-24 17:51:11', 0, NULL),
(20, NULL, 35, '2025-11-08 19:40:00', 10.00, 60, 'completed', 10.00, '2025-11-24 19:40:17', 0, NULL),
(21, NULL, 35, '2025-11-11 19:53:00', 20.00, 120, 'completed', 20.00, '2025-11-24 19:53:15', 0, NULL),
(22, NULL, 35, '2025-11-29 19:59:00', 30.00, 180, 'completed', 30.00, '2025-11-24 19:59:54', 0, NULL),
(23, NULL, 35, '2025-11-14 20:01:00', 20.00, 120, 'completed', 20.00, '2025-11-24 20:01:25', 1, NULL),
(24, NULL, 35, '2025-11-18 20:17:00', 20.00, 120, 'completed', 20.00, '2025-11-24 20:17:33', 1, NULL),
(25, NULL, 35, '2025-11-20 20:22:00', 20.00, 120, 'accepted', 20.00, '2025-11-24 20:22:34', 1, NULL),
(26, 39, 35, '2025-11-21 21:25:00', 20.00, 120, 'completed', 20.00, '2025-11-24 21:25:36', 1, NULL),
(27, 39, 35, '2025-11-13 22:44:00', 10.00, 60, 'completed', 10.00, '2025-11-24 22:44:35', 1, NULL),
(28, 41, 42, '2025-11-28 22:55:00', 10.00, 60, 'completed', 10.00, '2025-11-24 22:55:32', 1, NULL),
(29, 41, 42, '2025-11-22 22:56:00', 30.00, 180, 'completed', 30.00, '2025-11-24 22:56:40', 1, NULL),
(30, 41, 42, '2025-11-09 20:14:00', 10.00, 60, 'completed', 10.00, '2025-11-25 20:14:39', 1, NULL),
(31, 41, 42, '2025-11-10 23:11:00', 10.00, 60, 'completed', 10.00, '2025-11-25 23:11:19', 1, NULL),
(32, 41, 42, '2025-11-13 23:31:00', 20.00, 120, 'completed', 20.00, '2025-11-25 23:32:08', 1, NULL),
(33, 51, 28, '2025-12-26 18:31:00', 20.00, 120, 'rejected', 20.00, '2025-12-01 18:31:33', 0, NULL),
(34, 51, 28, '2025-12-27 18:32:00', 10.00, 60, 'completed', 10.00, '2025-12-01 18:32:39', 1, NULL),
(35, 51, 28, '2025-12-20 19:10:00', 20.00, 120, 'completed', 20.00, '2025-12-01 19:10:46', 1, NULL),
(36, 51, 28, '2025-12-20 19:12:00', 10.00, 60, 'rejected', 10.00, '2025-12-01 19:12:34', 0, NULL),
(37, 28, 27, '2026-01-01 17:58:00', 10.00, 60, 'completed', 10.00, '2025-12-29 17:58:51', 1, NULL),
(38, 28, 27, '2025-12-19 02:43:00', 10.00, 60, 'completed', 10.00, '2025-12-30 02:43:32', 1, 'eduMent_38_015a1e29'),
(39, 27, 28, '2026-01-01 14:45:00', 10.00, 60, 'cancelled', 10.00, '2025-12-30 14:45:24', 0, NULL),
(40, 27, 28, '2026-01-02 14:49:00', 10.00, 60, 'cancelled', 10.00, '2025-12-30 14:49:12', 0, NULL),
(41, 17, 30, '2025-12-11 22:38:00', 10.00, 60, 'completed', 10.00, '2025-12-30 22:38:16', 1, 'eduMent_41_1a79fb65'),
(42, 17, 28, '2025-12-24 23:50:00', 10.00, 60, 'cancelled', 10.00, '2025-12-30 23:50:43', 0, NULL),
(43, 17, 30, '2025-12-31 23:51:00', 10.00, 60, 'completed', 10.00, '2025-12-30 23:51:47', 1, NULL),
(44, 17, 30, '2025-12-31 23:52:00', 10.00, 60, 'completed', 10.00, '2025-12-30 23:52:50', 1, 'eduMent_44_a5f9ed6e'),
(45, 53, 29, '2026-01-02 17:30:00', 10.00, 60, 'cancelled', 10.00, '2025-12-31 15:27:35', 0, NULL),
(46, 53, 30, '2026-03-31 15:31:00', 0.00, 60, 'cancelled', 0.00, '2025-12-31 15:31:57', 0, 'eduMent_46_e67ee8d7'),
(47, 56, 30, '2026-01-22 00:56:00', 0.00, 60, 'pending', 0.00, '2026-01-16 00:56:17', 0, NULL),
(48, 53, 56, '2026-01-23 22:55:00', 20.00, 120, 'completed', 20.00, '2026-01-16 22:55:40', 1, NULL),
(49, 53, 56, '2026-01-30 23:06:00', 10.00, 60, 'rejected', 10.00, '2026-01-16 23:02:33', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contents`
--

CREATE TABLE `contents` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

CREATE TABLE `follows` (
  `id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`id`, `follower_id`, `following_id`, `created_at`) VALUES
(4, 27, 28, '2025-12-29 17:09:33');

-- --------------------------------------------------------

--
-- Table structure for table `mentor_applications`
--

CREATE TABLE `mentor_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `experience` text NOT NULL,
  `certificate` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `applied_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mentor_applications`
--

INSERT INTO `mentor_applications` (`id`, `user_id`, `admin_id`, `experience`, `certificate`, `status`, `applied_at`, `reviewed_at`, `created_at`) VALUES
(3, 26, NULL, 'kkk', NULL, 'approved', NULL, NULL, '2025-11-24 03:27:40'),
(4, 27, NULL, 'kcc', NULL, 'approved', NULL, NULL, '2025-11-24 03:34:39'),
(5, 29, NULL, 'kv c', NULL, 'approved', NULL, NULL, '2025-11-24 05:23:43'),
(6, 14, NULL, 'kv c', NULL, 'rejected', NULL, NULL, '2025-11-24 05:24:50'),
(7, 30, NULL, 'llcc', NULL, 'approved', NULL, NULL, '2025-11-24 05:45:22'),
(9, 1, NULL, 'gg', NULL, 'pending', NULL, NULL, '2025-11-24 13:24:14'),
(10, 32, 0, 'hb', NULL, 'pending', '2025-11-24 13:52:25', NULL, NULL),
(11, 35, 33, 'poewfq', NULL, 'approved', '2025-11-24 16:49:23', '2025-11-24 16:50:07', NULL),
(12, 37, 36, 'my mc', '1763977633_MC.jpeg', 'approved', '2025-11-24 17:47:13', '2025-11-24 17:47:59', NULL),
(13, 42, 40, 'bnk', NULL, 'approved', '2025-11-24 22:54:05', '2025-11-24 22:54:13', NULL),
(14, 50, 33, 'mc', '1764085023_MC.jpeg', 'approved', '2025-11-25 23:37:03', '2025-11-25 23:37:25', NULL),
(15, 28, 1, 'ok;', NULL, 'approved', '2025-12-01 17:43:59', '2025-12-01 17:44:08', NULL),
(16, 53, 14, 'web | botany teacher', 'docs/doc_53_1767164558.png', 'approved', '2025-12-31 15:02:38', '2025-12-31 15:02:58', '2025-12-31 15:02:38'),
(17, 54, 14, 'Web design | I teach web UI & UX', 'docs/doc_54_1767166821.png', 'approved', '2025-12-31 15:40:21', '2025-12-31 15:40:58', '2025-12-31 15:40:21'),
(18, 17, 14, 'deep_learning | NLP', 'docs/doc_17_1768492338.docx', 'approved', '2026-01-15 23:52:18', '2026-01-15 23:53:34', '2026-01-15 23:52:18'),
(19, 56, 14, 'PHP | i want to become a mentor', 'docs/doc_56_1768493410.pdf', 'approved', '2026-01-16 00:10:10', '2026-01-16 00:11:07', '2026-01-16 00:10:10');

-- --------------------------------------------------------

--
-- Table structure for table `mentor_posts`
--

CREATE TABLE `mentor_posts` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `posts_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mentor_posts`
--

INSERT INTO `mentor_posts` (`id`, `mentor_id`, `content`, `image_path`, `created_at`, `posts_count`) VALUES
(1, 28, 'hello, mates\r\nhow is going', NULL, '2025-12-29 16:54:12', 0),
(2, 27, 'this is the compose email', '1767000067_69524803d6858_compose email.png', '2025-12-29 17:21:07', 0),
(3, 28, 'here is the activity log', '1767010823_695272072d1f1_acitivity log.png', '2025-12-29 20:20:23', 0),
(4, 30, 'how is going??', NULL, '2025-12-30 21:04:06', 0),
(5, 30, 'this is the highlighted', '1767162980_6954c46455922_Gemini_Generated_Image_h1st7lh1st7lh1st.png', '2025-12-31 14:36:20', 0),
(6, 30, 'Attention all students!\r\nwe have a class today.', NULL, '2025-12-31 15:42:23', 0),
(8, 56, 'Hello', NULL, '2026-01-16 00:56:55', 0);

-- --------------------------------------------------------

--
-- Table structure for table `mentor_sessions`
--

CREATE TABLE `mentor_sessions` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `minutes` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `attachment_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `created_at`, `is_read`, `attachment_path`) VALUES
(1, 32, 27, 'hii', '2025-12-01 09:31:17', 1, NULL),
(2, 32, 27, 'hrll', '2025-12-01 09:31:28', 1, NULL),
(3, 32, 27, 'hrll', '2025-12-01 09:31:30', 1, NULL),
(4, 27, 32, 'hello', '2025-12-01 09:32:10', 0, NULL),
(5, 27, 32, 'hello', '2025-12-01 09:32:13', 0, NULL),
(6, 32, 27, 'hii', '2025-12-01 09:32:37', 1, NULL),
(7, 32, 27, 'hii', '2025-12-01 09:32:39', 1, NULL),
(8, 32, 27, 'hii', '2025-12-01 09:39:21', 1, NULL),
(9, 27, 32, 'i would like to make appoinment with you', '2025-12-01 09:39:52', 0, NULL),
(10, 28, 27, 'hello', '2025-12-01 09:41:46', 1, NULL),
(11, 27, 28, 'hiii', '2025-12-01 09:42:07', 1, NULL),
(12, 27, 28, 'hiii', '2025-12-01 09:42:09', 1, NULL),
(13, 27, 28, 'hii', '2025-12-01 09:46:01', 1, NULL),
(14, 28, 28, 'hii', '2025-12-01 09:46:44', 1, NULL),
(15, 28, 27, 'hello', '2025-12-01 10:05:30', 1, NULL),
(16, 51, 28, 'hii', '2025-12-01 10:29:26', 1, NULL),
(17, 51, 28, 'when wiill you be available', '2025-12-01 10:29:36', 1, NULL),
(18, 28, 51, 'coming 22 july', '2025-12-01 10:30:55', 0, NULL),
(19, 51, 28, 'hiii', '2025-12-01 11:07:22', 1, NULL),
(20, 28, 51, 'how are you', '2025-12-01 11:07:48', 0, NULL),
(21, 51, 28, 'hiii', '2025-12-01 11:09:49', 1, NULL),
(22, 51, 28, 'hii', '2025-12-02 03:37:40', 1, NULL),
(23, 51, 28, 'dammmn', '2025-12-02 03:38:26', 1, NULL),
(24, 28, 51, 'sppl', '2025-12-02 03:38:34', 0, NULL),
(25, 28, 51, 'စားပြီးပြီလား', '2025-12-02 03:38:49', 0, NULL),
(26, 28, 28, 'hii', '2025-12-03 01:04:36', 1, NULL),
(27, 4, 27, 'hello', '2025-12-29 04:46:02', 1, NULL),
(28, 4, 27, 'hii', '2025-12-29 04:56:14', 1, NULL),
(29, 4, 27, 'hii', '2025-12-29 04:56:16', 1, NULL),
(30, 4, 27, 'hello', '2025-12-29 04:56:22', 1, NULL),
(31, 4, 28, 'hii', '2025-12-29 06:36:49', 1, NULL),
(32, 27, 28, 'hii', '2025-12-29 09:41:01', 1, NULL),
(33, 28, 27, '', '2025-12-29 10:15:06', 1, '1767003306_695254aaed02a_compose email.png'),
(34, 27, 28, 'hii', '2025-12-30 06:16:57', 0, '1767075417_69536e591499f_CDE2243_IoT_Declare_012526_AIU23102140_ZayarLinn.pdf.pdf'),
(35, 27, 28, '', '2025-12-30 06:17:12', 0, '1767075432_69536e689fab0_CDE2243_IoT_Declare_012526_AIU23102140_ZayarLinn.pdf.pdf'),
(36, 27, 28, '', '2025-12-30 06:17:14', 0, '1767075434_69536e6aabce9_CDE2243_IoT_Declare_012526_AIU23102140_ZayarLinn.pdf.pdf'),
(37, 17, 30, 'hii', '2025-12-30 16:02:15', 1, NULL),
(38, 53, 29, 'i want to teach with you', '2025-12-31 07:26:40', 0, NULL),
(39, 54, 30, 'hii', '2025-12-31 07:47:07', 1, NULL),
(40, 54, 30, 'will you be available for this friday', '2025-12-31 07:47:16', 1, NULL),
(41, 56, 30, 'hello', '2026-01-15 16:55:03', 0, NULL),
(42, 56, 30, 'i wan tot make appointment', '2026-01-15 16:55:10', 0, NULL),
(43, 56, 17, 'is it available', '2026-01-15 16:55:34', 0, NULL),
(44, 53, 56, 'hello', '2026-01-16 10:38:39', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `post_comments`
--

CREATE TABLE `post_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_comments`
--

INSERT INTO `post_comments` (`id`, `post_id`, `user_id`, `content`, `created_at`) VALUES
(1, 1, 27, 'good', '2025-12-29 17:29:16'),
(2, 3, 27, 'good', '2025-12-30 02:39:47'),
(3, 8, 56, 'hi', '2026-01-16 23:05:55'),
(4, 5, 56, 'great', '2026-01-16 23:06:15'),
(5, 8, 56, 'how is going?', '2026-01-16 23:09:09');

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`id`, `post_id`, `user_id`, `created_at`) VALUES
(5, 2, 14, '2025-12-30 00:24:11'),
(6, 3, 27, '2025-12-30 02:39:39'),
(7, 2, 28, '2025-12-30 02:44:10'),
(8, 2, 4, '2025-12-30 14:21:44'),
(14, 5, 56, '2026-01-16 23:06:09');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `mentor_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `appointment_id`, `student_id`, `mentor_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 28, 27, 4, 'good mentor', '2025-12-29 17:51:42'),
(2, 4, 28, 27, 5, 'very good', '2025-12-29 17:51:54'),
(3, 37, 28, 27, 5, 'great', '2025-12-29 18:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `super_admins`
--

CREATE TABLE `super_admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `super_admins`
--

INSERT INTO `super_admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Super Admin', 'superadmin@example.com', '482c811da5d5b4bc6d497ffa98491e38', '2025-11-24 15:40:08');

-- --------------------------------------------------------

--
-- Table structure for table `system_wallet`
--

CREATE TABLE `system_wallet` (
  `id` int(11) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_wallet`
--

INSERT INTO `system_wallet` (`id`, `balance`) VALUES
(1, 84.00);

-- --------------------------------------------------------

--
-- Table structure for table `topup_requests`
--

CREATE TABLE `topup_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `proof_image` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bank_info` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `rejection_proof` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `topup_requests`
--

INSERT INTO `topup_requests` (`id`, `student_id`, `admin_id`, `amount`, `proof_image`, `status`, `created_at`, `bank_info`, `rejection_reason`, `rejection_proof`) VALUES
(1, 27, 1, 1000.00, 'payments/proof_1767080616_695382a83280b.png', 'pending', '2025-12-30 07:43:36', NULL, NULL, NULL),
(2, 30, 14, 133.00, 'payments/proof_1767085210_6953949aaac1a.png', 'approved', '2025-12-30 09:00:10', NULL, NULL, NULL),
(3, 30, 14, 133.00, 'payments/proof_1767085776_695396d042880.png', 'rejected', '2025-12-30 09:09:36', NULL, NULL, NULL),
(4, 30, 14, 100.00, 'payments/proof_1767091223_6953ac17ed117.png', 'rejected', '2025-12-30 10:40:23', '{\"account_holder\":\"gjkl\",\"bank_name\":\"Maybank (Malayan Banking)\",\"account_number\":\"23567\"}', 'khkl;', 'payouts/refund_4_1767091259.png'),
(5, 17, 14, 500.00, 'payments/proof_1767104864_6953e1602e674.png', 'rejected', '2025-12-30 14:27:44', '{\"account_holder\":\"zyl\",\"bank_name\":\"Maybank (Malayan Banking)\",\"account_number\":\"12345678\"}', 'none', 'payouts/refund_5_1767105319.png'),
(6, 17, 14, 30.00, 'payments/proof_1767105388_6953e36c52645.png', 'approved', '2025-12-30 14:36:28', '{\"account_holder\":\"zyl\",\"bank_name\":\"Maybank (Malayan Banking)\",\"account_number\":\"123456\"}', NULL, NULL),
(7, 17, 14, 10.00, 'topup_1767110262_17.png', 'approved', '2025-12-30 15:57:42', '{\"account_holder\":\"zyl\",\"bank_name\":\"Maybank\",\"account_number\":\"12345678\"}', NULL, NULL),
(8, 53, 14, 30.00, 'topup_1767166167_53.png', 'approved', '2025-12-31 07:29:27', '{\"account_holder\":\"zyl\",\"bank_name\":\"Maybank\",\"account_number\":\"123456\"}', NULL, NULL),
(9, 52, 14, 20.00, 'Direct Assignment', 'approved', '2026-01-09 17:21:13', 'Direct Admin Top-up', NULL, NULL),
(10, 56, 14, 20.00, 'topup_1768497328_56.png', 'approved', '2026-01-15 17:15:28', '{\"account_holder\":\"zyl\",\"bank_name\":\"Maybank\",\"account_number\":\"123456\"}', NULL, NULL),
(11, 53, 14, 50.00, 'topup_1768497430_53.png', 'approved', '2026-01-15 17:17:10', '{\"account_holder\":\"kk\",\"bank_name\":\"Maybank\",\"account_number\":\"1234567\"}', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `type` enum('topup','assign') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `admin_id`, `type`, `amount`, `stripe_session_id`, `user_id`, `created_at`) VALUES
(1, 40, 'topup', 500.00, NULL, NULL, '2025-11-25 03:17:36'),
(2, 40, 'assign', 30.00, NULL, 43, '2025-11-25 03:19:26'),
(3, 40, 'assign', 50.00, NULL, 44, '2025-11-25 03:20:05'),
(4, 40, 'assign', 30.00, NULL, 45, '2025-11-25 03:40:31'),
(5, 33, 'topup', 3000.00, NULL, NULL, '2025-11-25 04:55:05'),
(6, 33, 'assign', 300.00, NULL, 46, '2025-11-25 04:55:36'),
(7, 33, 'assign', 33.00, NULL, 47, '2025-11-25 06:53:57'),
(8, 33, 'assign', 33.00, NULL, 48, '2025-11-25 06:54:51'),
(9, 33, 'assign', 33.00, NULL, 49, '2025-11-25 06:56:01'),
(10, 33, 'assign', 50.00, NULL, 49, '2025-11-25 06:59:09'),
(11, 33, 'assign', 50.00, NULL, 49, '2025-11-25 06:59:17'),
(12, 33, 'assign', 50.00, NULL, 49, '2025-11-25 07:00:25'),
(13, 33, 'assign', 30.00, NULL, 49, '2025-11-25 07:00:43'),
(14, 33, 'assign', 30.00, NULL, 49, '2025-11-25 07:03:50'),
(15, 33, 'assign', 30.00, NULL, 46, '2025-11-25 07:03:59'),
(16, 33, 'assign', 10.00, NULL, 49, '2025-11-25 07:04:58'),
(17, 33, 'assign', 30.00, NULL, 39, '2025-11-25 07:06:40'),
(18, 33, 'assign', 10.00, NULL, NULL, '2025-11-25 07:07:54'),
(19, 33, 'assign', 11.00, NULL, NULL, '2025-11-25 07:08:00'),
(20, 33, 'assign', 10.00, NULL, NULL, '2025-11-25 07:08:42'),
(21, 33, 'assign', 50.00, NULL, NULL, '2025-11-25 07:11:33'),
(22, 33, 'assign', 30.00, NULL, NULL, '2025-11-25 15:28:25'),
(23, 33, 'assign', 30.00, NULL, NULL, '2025-11-25 15:28:48'),
(24, 33, 'assign', 300.00, NULL, NULL, '2025-11-25 15:29:12'),
(25, 33, 'topup', 3000.00, NULL, NULL, '2025-11-25 15:29:47'),
(26, 33, 'assign', 1000.00, NULL, NULL, '2025-11-25 15:35:00'),
(27, 33, 'assign', 320.00, NULL, 50, '2025-11-25 15:36:01'),
(28, 1, 'topup', 500.00, NULL, NULL, '2025-12-01 09:13:34'),
(29, 1, 'assign', 20.00, NULL, NULL, '2025-12-01 09:13:51'),
(30, 1, 'topup', 20.00, NULL, 32, '2025-12-01 09:20:59'),
(31, 1, 'topup', 9.00, NULL, 27, '2025-12-01 09:21:21'),
(32, 1, 'assign', 300.00, NULL, 51, '2025-12-01 10:28:26'),
(33, 1, 'topup', 30.00, NULL, 27, '2025-12-01 10:38:03'),
(34, 1, 'topup', 300.00, NULL, NULL, '2025-12-01 10:39:25'),
(35, 1, 'topup', 30.00, NULL, 32, '2025-12-03 00:55:47'),
(36, 1, 'topup', 4000.00, NULL, NULL, '2025-12-03 00:56:02'),
(37, 14, 'topup', 500.00, NULL, NULL, '2025-12-29 04:10:25'),
(38, 14, 'assign', 20.00, NULL, 52, '2025-12-29 04:11:57'),
(39, 14, 'topup', 133.00, NULL, 30, '2025-12-30 09:00:32'),
(40, 14, 'topup', 30.00, NULL, 17, '2025-12-30 14:36:41'),
(41, 14, 'topup', 10.00, NULL, 17, '2025-12-30 15:58:25'),
(42, 14, 'assign', 20.00, NULL, 53, '2025-12-31 07:01:37'),
(43, 14, 'topup', 30.00, NULL, 53, '2025-12-31 07:30:43'),
(44, 14, 'assign', 40.00, NULL, 54, '2025-12-31 07:38:00'),
(45, 14, 'topup', 100.00, 'cs_test_a1nW3Z0DjhILQ96NI4qujuM6GYZL9zrLzJHoklSzxAnv4PwuprTQWXzuFU', NULL, '2026-01-09 16:09:33'),
(46, 14, 'topup', 5000.00, 'cs_test_a1NIx9uyeRqxrA8l16w8BtitfOMHVaxKFtuJbrHeRsarxXTl5JuFs8HRgj', NULL, '2026-01-09 16:10:23'),
(47, 14, 'topup', 20.00, NULL, 52, '2026-01-09 17:15:29'),
(48, 14, 'topup', 20.00, NULL, 52, '2026-01-09 17:16:44'),
(49, 14, '', 20.00, NULL, 52, '2026-01-09 17:21:13'),
(50, 14, 'assign', 200.00, NULL, 56, '2026-01-15 16:08:24'),
(51, 14, 'topup', 20.00, NULL, 56, '2026-01-15 17:17:32'),
(52, 14, 'topup', 50.00, NULL, 53, '2026-01-15 17:17:39'),
(53, 57, 'topup', 5000.00, 'cs_test_a1FTrMb9nVBKOEaZ1Op1oc83yLlpINR2vtrKjP8pQ7QhEoaJhjMt23DouC', NULL, '2026-01-16 10:25:50'),
(54, 57, 'assign', 200.00, NULL, 58, '2026-01-16 10:27:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_by_admin` int(11) DEFAULT NULL,
  `role` enum('admin','student','mentor') DEFAULT 'student',
  `status` enum('active','suspended') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `mentor_id` int(11) DEFAULT NULL,
  `is_mentor` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `mentor_status` varchar(20) DEFAULT 'none',
  `hourly_rate` decimal(10,2) DEFAULT 10.00,
  `is_volunteer` tinyint(1) DEFAULT 0,
  `admin_id` int(11) DEFAULT NULL,
  `education` text DEFAULT NULL,
  `expertise` text DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `mentor_documents` text DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `teaching_balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_by_admin`, `role`, `status`, `created_by`, `mentor_id`, `is_mentor`, `created_at`, `balance`, `mentor_status`, `hourly_rate`, `is_volunteer`, `admin_id`, `education`, `expertise`, `bio`, `mentor_documents`, `profile_photo`, `last_seen`, `qr_code`, `teaching_balance`) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$Dsf7l38esnTOrCsakcFci.GSWg0xrF6xCInt9IWLSMaG4/KbhS7Bm', NULL, 'admin', 'active', NULL, NULL, 0, '2025-11-22 15:28:09', 4391.00, 'pending', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(3, 'Main Admin', 'adminn@example.com', '$2y$10$1Xe3uFEx6XYe0Gq7lYx9euMZ2qzqH7w2XStO2z7yC2FzRF9rD0zrG', NULL, 'admin', 'active', NULL, NULL, 0, '2025-11-22 21:45:57', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(4, 'dd', 'd@gmail.com', '$2y$10$4V7zZW85NJbQDRPfZca.he.oE6tYcwrtPDMuGdI/PNzDGFyENiw7i', NULL, 'student', 'active', 1, NULL, 0, '2025-11-23 13:04:45', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(5, 'dd', 'nn@gmail.com', '$2y$10$XRDEeAoDY2oxrbL6ilEcp.z/p2RFoWT2iXrF2Iq6yqrw6xbNBp9Xu', NULL, 'student', 'active', NULL, NULL, 0, '2025-11-23 13:35:29', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(6, 'cc', 'c@gmail.com', '$2y$10$KDtN9BQL8JCl4TqXPldHAuVf5zX.V3RF2lRVcbyagiBktt8nYVrJe', NULL, 'student', 'active', NULL, NULL, 0, '2025-11-23 16:59:34', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(7, 'll', 'l@gmail.com', '$2y$10$6hoq5CwIyXwZ.5EcvrqJEurwZ2S66PJ9CKnfSe2rtXnLdia1I.KUG', NULL, 'student', 'active', NULL, NULL, 0, '2025-11-23 17:28:32', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(8, 'kk', 'admin1@gmail.com', '$2y$10$sRYf1sj02LMfmB7DnV0h6.lyjtuLmnzlmLLOdAtM6VxCVZGy9RzqC', NULL, 'admin', 'active', NULL, NULL, 0, '2025-11-23 17:54:50', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(9, 'ui', 'admin2@gmail.com', '$2y$10$JXMOudxLSpuWi43x8Tdebu73VArn4DW.aHcNP1r6eyZSRurOn9tke', NULL, 'admin', 'active', NULL, NULL, 0, '2025-11-23 18:08:12', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(10, 'fjal', 'fj@gmail.com', '$2y$10$3hJhlF0Tes5g7OlH7sFMreMxkR/gVfhDBeJN2JOAWnN77dfamFJLG', NULL, 'student', 'active', NULL, NULL, 0, '2025-11-23 18:09:43', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(11, 'tt', 'test@gmail.com', '$2y$10$8M3q04K.QtJoFLXgg8mjauFZcfV1ZTRMP1phE1lG1LMwahlPBWcaC', NULL, 'admin', 'active', NULL, NULL, 0, '2025-11-23 18:10:26', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(12, 'students from admin', 'fla@gmail.com', '$2y$10$pzrqmOcWUblNGChVHdnrdes7He4ai7ABdTxh1wqD8ap4apqVCR/QG', NULL, 'student', 'active', NULL, NULL, 0, '2025-11-23 18:23:04', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(14, 'ajfa', 'admin4@gmail.com', '$2y$10$pGkOiwuY3Vx8VVeycyGXvOGtYxukZp9VlCZlfy7UHIfSnkfUljqs6', NULL, 'admin', 'active', NULL, NULL, 0, '2025-11-23 18:25:26', 4987.00, 'rejected', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'qrcodes/qr_14_1767108630.jpeg', 0.00),
(15, 'student from admin 4', 'afj@gmail.com', '$2y$10$7qbqtoOgq/MOWqy.Zq9R/esC1qbkSursooZhYTej38oelvkryWUgm', NULL, 'student', 'active', NULL, NULL, 0, '2025-11-23 18:26:11', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(17, 'student from admin 4', 'jsjk@gmail.com', '$2y$10$xoP/mynuYjee28Bj.sFYGu4ezjkTyts7mc3KwKXiIh9zDHnqCKCm6', NULL, 'mentor', 'active', 14, NULL, 1, '2025-11-23 18:50:50', 35.00, 'approved', 10.00, 0, NULL, 'phd', 'deep_learning', 'NLP', 'docs/doc_17_1768492338.docx', NULL, '2026-01-15 23:46:45', NULL, 0.00),
(26, 'dk', 'dk@gmail.com', '$2y$10$cF5N7NiKKuERTOQscVP9fen3rnMxyzJ40a9attI3z0EJzHEMODOXa', NULL, 'mentor', 'active', 1, NULL, 0, '2025-11-24 03:26:58', 50.00, 'pending', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(27, 'Kamal', 'kc@gmail.com', '$2y$10$B/kgrRnF7n3xDRB6lw03OeQqeds7iffb/SR0c5Hu6eZ/PJhu8mMFS', NULL, 'mentor', 'active', 1, NULL, 1, '2025-11-24 03:34:04', 68.00, 'approved', 10.00, 0, NULL, '', '', '', NULL, NULL, '2025-12-30 14:18:26', NULL, 126.00),
(28, 'wine wine', 'w@gmail.com', '$2y$10$VClECgNis0i69MbOx9DULuaefJOIbMV.E/TIKIocrNMwurPCfq/Nm', NULL, 'mentor', 'active', 1, NULL, 1, '2025-11-24 03:51:06', 41.00, 'approved', 10.00, 0, NULL, 'PhD in botany', 'Botany', 'teaching botany', NULL, NULL, '2025-12-30 13:55:22', NULL, 27.00),
(29, 'v', 'vk@gmail.com', '$2y$10$mzpiUATxo4MoYRR7e2DKp.kQ8d6GeXqM/yseHCT2Thkidm/uPgn8K', NULL, 'mentor', 'active', 14, NULL, 1, '2025-11-24 05:22:55', 59.00, 'approved', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 27.00),
(30, 'dc', 'dc@gmail.com', '$2y$10$/wb9wUHCOtMK9CNcsTe/EeS4xeQX6wrSeMM5lrccLz3B9OfBK9r1S', NULL, 'mentor', 'active', 14, NULL, 1, '2025-11-24 05:44:14', 35.00, 'approved', 10.00, 1, NULL, '', '', '', NULL, NULL, '2025-12-31 16:56:20', NULL, 27.00),
(32, 'b', 'b@gmail.com', '$2y$10$mJXrldGEdV2TbOAURh1HDeJoGXfiUDKB4KCwJcdTGLUZdKU/HYEBq', NULL, 'student', 'active', 1, NULL, 0, '2025-11-24 13:47:47', 115.00, 'pending', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(33, 'admin20', 'admin20@gmail.com', '$2y$10$2DzFg8RsfpXVoG7Xfn/xNuKObfcU5B825ojqffP2IGpepJGdjRZoa', NULL, 'admin', 'active', NULL, NULL, 0, '2025-11-24 16:40:31', 3560.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(35, 'h', 'hgj@gmail.com', '$2y$10$J7dSLyLrun4PkTRt06A1XOw3fB5gSiakWeFf9NxLtM8Eceo8.gr0y', NULL, 'mentor', 'active', 33, NULL, 1, '2025-11-24 16:48:16', 731.00, 'approved', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 198.00),
(36, 'ttt', 'uadmin@gmail.com', '$2y$10$JSbEfYoHmB0STRzN/8ffeODy9DVvezekOCfq/8gUYfP3BJHsJXNVS', NULL, 'admin', 'active', NULL, NULL, 0, '2025-11-24 17:43:12', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(37, 'MGMG', 'mgmg@gmail.com', '$2y$10$ri3oXm7m.d6F9s6Pim7aUucMq/OeTeF066yNWyYKhOSjjX9BMafV2', NULL, 'mentor', 'active', 36, NULL, 1, '2025-11-24 17:45:35', 539.00, 'approved', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 18.00),
(38, 'kyw kyw', 'kyw@gmail.com', '$2y$10$1KIDt2AYefNkHWd9l6CjgePbkn6p3jz0C4ecSiyNqpFuc3IEhx19C', NULL, 'student', 'active', 36, NULL, 0, '2025-11-24 17:49:08', 280.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(39, 'er', 'nbv@gmail.com', '$2y$10$aSkxT9NCwL4BLhuTx2BR7e15gcy51LLWTncHrh.m/pQS5MFALd5Xa', NULL, 'student', 'active', 33, NULL, 0, '2025-11-24 20:56:28', 300.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(40, 'k', 'km@gmail.com', '$2y$10$ljDb32VKcd./tjWb3zuqyeZMD.A/poDGJ9q24JqBLYagRuIauJLDW', NULL, 'admin', 'active', NULL, NULL, 0, '2025-11-24 22:51:33', 390.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(41, 'uh', 'uh@gmail.com', '$2y$10$jEQnUwizyuUw/jbKEb95We9Ncr4LWX1GkAo7YbFVZIFm8wSoJk8Ii', NULL, 'student', 'active', 40, NULL, 0, '2025-11-24 22:52:27', 240.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(42, 'kl', 'lk@gmail.com', '$2y$10$jZzyfHqPXXeOYee4YdJrb.kXfZQSdK.aDELrulgAPCkSIyeOd0FgO', NULL, 'mentor', 'active', 40, NULL, 1, '2025-11-24 22:52:51', 612.00, 'approved', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 72.00),
(43, 'yt', 'yt@gmail.com', '$2y$10$xmOwuIFJ.6BzpTdy39sjq.DbEklghSlRRa52uKxR/7sM2xPT0eb0G', NULL, 'student', 'active', 40, NULL, 0, '2025-11-25 11:19:26', 30.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(44, 'kkk', 'nk@gmail.com', '$2y$10$XGru04q2nuDvK2aTJ/Qdp.6FSCGtaesvZpfobyaum9Tbu5yVBXTt.', NULL, 'student', 'active', 40, NULL, 0, '2025-11-25 11:20:05', 50.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(45, 'nm', 'nm@gmail.com', '$2y$10$4P9Xm.S5jBE90I75aEVreewgUSiofP7ekEukcLrzDEb3Jn82GHa3u', NULL, 'student', 'active', 40, NULL, 0, '2025-11-25 11:40:31', 330.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(46, 'utl', 'bvm@gmail.com', '$2y$10$M/bjDr1gzJMG2COm15md8ePXLK4XJlWStBagjniwWNNTZ94MM4xqK', NULL, 'student', 'active', 33, NULL, 0, '2025-11-25 12:55:36', 330.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(49, 'ii', 'ii@gmail.com', '$2y$10$9GuY9DVJEtZZl1PtIO4RU.bsWga/asRGt2PiQ96hVfsnr20doM566', NULL, 'student', 'active', 33, NULL, 0, '2025-11-25 14:56:01', 253.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(50, 'nw', 'nct@gmail.com', '$2y$10$SMf91OSqJp0yuoJJbSAmHu0WvyUQqBxxaiA5wZjU/uajn3h7kAaju', NULL, 'mentor', 'active', 33, NULL, 1, '2025-11-25 23:36:01', 320.00, 'approved', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(51, 'sharaf', 'sharaf@gmail.com', '$2y$10$3AKhQwj/6KIHerzITRVDXO5iPVY6eQ8pFUmYWj5oRl4i6X2bkpYo6', NULL, 'student', 'active', 1, NULL, 0, '2025-12-01 18:28:26', 270.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(52, 'vnm', 'vbj@gmail.com', '$2y$10$gkuDJT9NJCGk1bkRBdmlyer68IJoiUQ6yMzn8x/TCVzerC0KYIWkG', NULL, 'student', 'active', 14, NULL, 0, '2025-12-29 12:11:57', 80.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(53, 'UMT', 'umt@gmail.com', '$2y$10$xImnn/bxF6PZ26OYG9dOb.KuZj8cNvCt5FJ7H7gmWcWY.Z0.AP8ZK', NULL, 'mentor', 'active', 14, NULL, 1, '2025-12-31 15:01:37', 40.00, 'approved', 10.00, 0, NULL, 'phd', 'web', 'botany teacher', 'docs/doc_53_1767164558.png', NULL, '2026-01-16 22:57:40', NULL, 0.00),
(54, 'naing naing', 'naing@gmail.com', '$2y$10$IcrskqM0ju5r.393fVK4B.m2hQo5Cef3ufwY1Tyje5GcoJDz3KZtS', NULL, 'mentor', 'active', 14, NULL, 1, '2025-12-31 15:38:00', 40.00, 'approved', 10.00, 0, NULL, 'BCS computing', 'Web design', 'I teach web UI & UX', 'docs/doc_54_1767166821.png', NULL, '2025-12-31 15:47:03', NULL, 0.00),
(55, 'uk', 'uk@gmail.com', '$2y$10$edKvVTlhP5YGxsa2ENr.WuDrQOyCeq0GSIt0VbgIlzPcqXCBY6ghe', NULL, 'admin', 'active', NULL, NULL, 0, '2026-01-10 02:32:11', 0.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(56, 'klm', 'klm@gmail.com', '$2y$10$lMAiDmyfNzPVD1YEnIt0S.UjzeCM9HUwoOltFvVzZmFYlJyyF0nCi', NULL, 'mentor', 'active', 14, NULL, 1, '2026-01-16 00:08:24', 238.00, 'approved', 10.00, 0, NULL, 'BSC computing', 'PHP', 'i want to become a mentor', 'docs/doc_56_1768493410.pdf', NULL, '2026-01-16 01:57:59', NULL, 18.00),
(57, 'nmm', 'nmm@gmail.com', '$2y$10$xCj.uYTyoIHArnsh2YA93Om0ReOMyoQY1Y/WQlIw11aobe2.OisU2', NULL, 'admin', 'active', NULL, NULL, 0, '2026-01-16 18:24:09', 4800.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00),
(58, 'Naing Naing', 'naning2002@gmail.com', '$2y$10$i/Z2nz36doGArQ1U3CwRAejJ1SjIX0GT51xqsPXCDQhNblJxLKCDq', NULL, 'student', 'active', 57, NULL, 0, '2026-01-16 18:27:55', 200.00, 'none', 10.00, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-16 18:30:58', NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `user_id` int(11) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_details` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_proof` varchar(255) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `withdrawal_requests`
--

INSERT INTO `withdrawal_requests` (`id`, `mentor_id`, `admin_id`, `amount`, `payment_details`, `status`, `created_at`, `admin_proof`, `rejection_reason`) VALUES
(1, 30, 14, 100.00, 'hlv', 'approved', '2025-12-30 09:30:50', NULL, NULL),
(2, 30, 14, 20.00, '{\"account_holder\":\"T\",\"bank_name\":\"Affin Bank\",\"account_number\":\"123344\"}', 'approved', '2025-12-30 10:08:24', 'payouts/payout_2_1767089367.png', NULL),
(3, 30, 14, 20.00, '{\"account_holder\":\"T\",\"bank_name\":\"Affin Bank\",\"account_number\":\"123344\"}', 'approved', '2025-12-30 10:09:42', 'payouts/payout_3_1767089456.jpeg', NULL),
(4, 30, 14, 20.00, '{\"account_holder\":\"T\",\"bank_name\":\"Affin Bank\",\"account_number\":\"123344\"}', 'rejected', '2025-12-30 10:11:04', NULL, 'none'),
(5, 30, 14, 20.00, '{\"account_holder\":\"T\",\"bank_name\":\"Affin Bank\",\"account_number\":\"123344\"}', 'rejected', '2025-12-30 10:11:25', NULL, 'invalid'),
(6, 30, 14, 20.00, '{\"account_holder\":\"T\",\"bank_name\":\"Affin Bank\",\"account_number\":\"123344\"}', 'rejected', '2025-12-30 10:11:27', NULL, 'ol'),
(7, 30, 14, 10.00, '{\"account_holder\":\"yuk\",\"bank_name\":\"Bank Muamalat Malaysia\",\"account_number\":\"12345678\"}', 'rejected', '2025-12-30 15:32:50', NULL, 'invalid'),
(8, 30, 14, 5.00, '{\"account_holder\":\"T\",\"bank_name\":\"Alliance Bank\",\"account_number\":\"12345678\"}', 'approved', '2025-12-30 15:34:50', 'payouts/payout_8_1767108998.png', NULL),
(9, 30, 14, 50.00, '{\"account_holder\":\"zayar\",\"bank_name\":\"AmBank\",\"account_number\":\"1234567\"}', 'approved', '2025-12-31 07:43:12', 'payouts/payout_9_1767167044.png', NULL),
(10, 53, 14, 20.00, '{\"account_holder\":\"yyy\",\"bank_name\":\"RHB Bank\",\"account_number\":\"123456\"}', 'approved', '2026-01-15 17:18:22', 'payouts/payout_10_1768497567.png', NULL),
(11, 56, 14, 10.00, '{\"account_holder\":\"hjkl\",\"bank_name\":\"RHB Bank\",\"account_number\":\"1234567890\"}', 'rejected', '2026-01-15 17:23:28', NULL, 'see me later'),
(12, 53, 14, 20.00, '{\"account_holder\":\"jkll\",\"bank_name\":\"Bank Muamalat Malaysia\",\"account_number\":\"12345678\"}', 'approved', '2026-01-16 10:44:43', 'payouts/payout_12_1768560338.png', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_keys`
--
ALTER TABLE `admin_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `mentor_id` (`mentor_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `contents`
--
ALTER TABLE `contents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentor_id` (`mentor_id`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follow` (`follower_id`,`following_id`),
  ADD KEY `following_id` (`following_id`);

--
-- Indexes for table `mentor_applications`
--
ALTER TABLE `mentor_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `mentor_posts`
--
ALTER TABLE `mentor_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentor_id` (`mentor_id`);

--
-- Indexes for table `mentor_sessions`
--
ALTER TABLE `mentor_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `super_admins`
--
ALTER TABLE `super_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `system_wallet`
--
ALTER TABLE `system_wallet`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `topup_requests`
--
ALTER TABLE `topup_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stripe_session_id` (`stripe_session_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_keys`
--
ALTER TABLE `admin_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contents`
--
ALTER TABLE `contents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `follows`
--
ALTER TABLE `follows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `mentor_applications`
--
ALTER TABLE `mentor_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `mentor_posts`
--
ALTER TABLE `mentor_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `mentor_sessions`
--
ALTER TABLE `mentor_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `post_comments`
--
ALTER TABLE `post_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `topup_requests`
--
ALTER TABLE `topup_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contents`
--
ALTER TABLE `contents`
  ADD CONSTRAINT `contents_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentor_applications`
--
ALTER TABLE `mentor_applications`
  ADD CONSTRAINT `mentor_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentor_posts`
--
ALTER TABLE `mentor_posts`
  ADD CONSTRAINT `mentor_posts_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentor_sessions`
--
ALTER TABLE `mentor_sessions`
  ADD CONSTRAINT `mentor_sessions_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `mentor_sessions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD CONSTRAINT `post_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `mentor_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `mentor_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `topup_requests`
--
ALTER TABLE `topup_requests`
  ADD CONSTRAINT `topup_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `topup_requests_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD CONSTRAINT `withdrawal_requests_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `withdrawal_requests_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
