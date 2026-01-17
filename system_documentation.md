# MentorHub System Documentation

## 1. System Overview
MentorHub is a web-based mentorship platform designed to connect students with mentors for guidance, learning, and professional development. The system features role-based access control, real-time communication (chat and video), a social feed for learning, and a wallet system for financial transactions with a built-in commission model.

**Core Technologies:**
- **Backend:** PHP (Vanilla)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5
- **Assets:** FontAwesome (Icons), Google Fonts (Poppins, Plus Jakarta Sans)

---

## 2. Directory Structure

The project follows a modular structure primarily segregated by user roles within the `public/` directory.

- **`public/`**: Contains the main application access points.
  - **`student/`**: Student-facing interfaces (dashboard, appointments, learning feed, wallet).
  - **`mentor/`**: Mentor-facing interfaces (dashboard, requests, posts, withdrawal).
  - **`admin/`**: Admin interfaces for managing users and verifying payments.
  - **`super_admin/`**: Top-level management (system dashboard, global reports, key generation).
  - **`assets/`**: Static assets like images and CSS.
  - **`uploads/`**: User-uploaded content (profile pictures, payment proofs).
- **`includes/`**: Shared backend logic.
  - `db.php`: Database connection configuration.
  - `auth.php`: Authentication helpers.
- **`sql/`**: Database schema definitions.

---

## 3. User Roles & Permissions

The system defines four distinct user roles, each with specific permissions:

### 1. Student
- **Access:** `public/student/`
- **Capabilities:**
  - View mentor profiles and make appointment requests.
  - Join video mentorship sessions.
  - View and interact with the "Learning Feed" (**View Comments**, Like, Post Comments).
  - Chat with mentors.
  - Manage a wallet (Top-up via bank transfer/upload proof).
  - Rate and review mentors after sessions.

### 2. Mentor
- **Access:** `public/mentor/`
- **Capabilities:**
  - Create and edit a professional profile.
  - Accept or reject appointment requests.
  - **Sidebar Notifications:** View real-time badge counts for pending appointment requests directly in the sidebar.
  - Conduct video sessions.
  - Post educational content to the "Learning Feed".
  - Chat with connected students.
  - Withdraw earnings from their wallet (subject to system commission).

### 3. Admin
- **Access:** `public/admin/`
- **Capabilities:**
  - Manage users (Students/Mentors) created under their purview.
  - Verify and approve student top-up requests.
  - Monitor user activity.

### 4. Super Admin
- **Access:** `public/super_admin/`
- **Capabilities:**
  - Full system oversight.
  - **Financial Oversight:** View `System Wallet` accumulating commissions from completed sessions.
  - View global financial reports (Total Revenue, Pending Withdrawals).
  - Manage other Administrators.
  - Generate invitation keys for new Admin registration.
  - Control system-wide settings.

---

## 4. Database Schema

The database `mentorship_db` consists of the following key tables:

### Users & Auth
- **`users`**: Stores all user accounts (Student, Mentor, Admin).
  - Columns: `id`, `name`, `email`, `password`, `role`, `status`, `balance`, `teaching_balance`, `image_path`, `mentor_status`, `qr_code`, `created_by`, `created_at`.
- **`super_admins`**: Dedicated table for super admin credentials.
- **`admin_keys`**: Manages invitation tokens for new admin registration.

### Mentorship & Interaction
- **`appointments`**: Tracks mentorship sessions.
  - Columns: `id`, `student_id`, `mentor_id`, `status` (pending, accepted, completed, cancelled), `room_id`, `points` (cost), `mentor_paid`, `created_at`.
- **`ratings`**: Feedback from students to mentors.
- **`messages`**: Real-time chat messages.

### Social Feed
- **`mentor_posts`**: Educational posts created by mentors.
- **`post_likes`**: Likes on mentor posts.
- **`post_comments`**: Comments on mentor posts.

### Financial
- **`system_wallet`**: Tracks central system funds (Commission Revenue).
  - Columns: `id`, `balance`.
- **`topup_requests`**: Requests from students to add funds.
- **`withdrawal_requests`**: Requests from mentors to withdraw earnings.

---

## 5. Key Modules

### Authentication Module
- Handles login (`login.php`) and logout (`logout.php`).
- Redirects users to their specific dashboards based on `role` session variable.

### Mentorship Flow & Commission System
1.  **Booking:** Student requests appointment (`make_appointment.php`).
2.  **Approval:** Mentor accepts request (`requests.php`).
3.  **Session:** Both parties join a video room (`video_room.php`).
4.  **Completion & Payout:** 
    - When a session is marked **Completed**:
    - **10% Commission** is deducted and sent to the **System Wallet**.
    - **90% Earnings** are credited to the **Mentor's Wallet** (`teaching_balance`).
    - Transaction is atomic to ensure financial accuracy.
5.  **Rating:** Student rates the mentor (`rate_mentor.php`).

### Social Feed (Learning)
- Located at `public/student/learn.php` and `public/mentor/posts.php`.
- Mentors can share knowledge via posts with text/images.
- **Interaction:** Students can engage via likes and now view/add comments directly on the feed line items.

### Wallet System
- **Points/Balance:** The internal currency.
- **Top-up:** Students upload proof of payment -> Admin approves -> Balance updates.
- **Withdrawal:** Mentors request withdrawal -> Super Admin/System processes payout -> Balance deducted.
