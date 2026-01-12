<?php
/**
 * CampusX - College Management System
 * Landing Page / Home Page
 * Version: 1.0
 */

// Start session
session_start();

// Include configuration
require_once 'config/config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: modules/admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'teacher') {
        header('Location: modules/teacher/dashboard.php');
    } elseif ($_SESSION['role'] === 'student') {
        header('Location: modules/student/dashboard.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusX - College Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* Navigation */
        nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo:hover {
            opacity: 0.9;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
        }

        nav a:hover {
            opacity: 0.8;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
            border: 2px solid white;
        }

        .btn-primary:hover {
            background: white;
            color: #667eea;
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6rem 2rem;
            text-align: center;
            min-height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        .hero .btn-group {
            justify-content: center;
            gap: 2rem;
        }

        /* Features Section */
        .features {
            padding: 5rem 2rem;
            background: #f8f9fa;
        }

        .features .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .features h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #333;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #667eea;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .feature-card p {
            color: #666;
            line-height: 1.8;
        }

        /* User Roles Section */
        .roles {
            padding: 5rem 2rem;
            background: white;
        }

        .roles .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .roles h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #333;
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .role-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s;
        }

        .role-card:hover {
            transform: scale(1.05);
        }

        .role-card h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .role-card ul {
            list-style: none;
            margin: 1.5rem 0;
            text-align: left;
        }

        .role-card li {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .role-card li:last-child {
            border-bottom: none;
        }

        /* Footer */
        footer {
            background: #333;
            color: white;
            padding: 2rem;
            text-align: center;
        }

        footer p {
            margin: 0.5rem 0;
        }

        .footer-links {
            margin: 1rem 0;
        }

        .footer-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 1rem;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            nav ul {
                gap: 1rem;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .features h2, .roles h2 {
                font-size: 2rem;
            }

            .btn-group {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="index.php" class="logo">
                📚 CampusX
            </a>
            <ul>
                <li><a href="#features">Features</a></li>
                <li><a href="#roles">Roles</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="btn-group">
                <a href="modules/authentication/login.php" class="btn btn-primary">Login</a>
                <a href="modules/authentication/register.php" class="btn btn-secondary">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to CampusX</h1>
            <p>A Comprehensive College Management System for Modern Educational Institutions</p>
            <div class="btn-group">
                <a href="modules/authentication/login.php" class="btn btn-secondary">Get Started</a>
                <a href="#features" class="btn btn-primary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2>Why Choose CampusX?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Automated Management</h3>
                    <p>Streamline admissions, attendance, grades, and assignments with our automated system.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure & Reliable</h3>
                    <p>Enterprise-grade security ensures your data is protected with role-based access control.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Real-time Updates</h3>
                    <p>Get instant access to attendance, results, and performance data for informed decisions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>User-Friendly Interface</h3>
                    <p>Intuitive design makes it easy for students, teachers, and administrators to navigate.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📈</div>
                    <h3>Advanced Reports</h3>
                    <p>Generate detailed analytics and reports for better decision-making and performance tracking.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🌐</div>
                    <h3>Web-Based Access</h3>
                    <p>Access the system anytime, anywhere from any device with internet connectivity.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- User Roles Section -->
    <section class="roles" id="roles">
        <div class="container">
            <h2>User Roles & Capabilities</h2>
            <div class="roles-grid">
                <div class="role-card">
                    <h3>👨‍💼 Administrator</h3>
                    <ul>
                        <li>Manage students and teachers</li>
                        <li>Verify admissions</li>
                        <li>Assign courses</li>
                        <li>Generate reports</li>
                        <li>System configuration</li>
                    </ul>
                </div>
                <div class="role-card">
                    <h3>👨‍🏫 Teacher</h3>
                    <ul>
                        <li>Take attendance</li>
                        <li>Create assignments</li>
                        <li>Enter exam grades</li>
                        <li>Send notifications</li>
                        <li>View course details</li>
                    </ul>
                </div>
                <div class="role-card">
                    <h3>👨‍🎓 Student</h3>
                    <ul>
                        <li>View attendance</li>
                        <li>Submit assignments</li>
                        <li>Apply for exams</li>
                        <li>Check results</li>
                        <li>Apply for leave</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <p>&copy; 2025 CampusX - College Management System. All rights reserved.</p>
        <p>Developed by: K-Gang (Kesh Bahadur Thapa & Khyam Narayan Dhakal)</p>
        <div class="footer-links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms & Conditions</a>
            <a href="#">Support</a>
            <a href="#">Documentation</a>
        </div>
        <p>Tribhuvan University | Prithvi Narayan Campus, Pokhara</p>
    </footer>
</body>
</html>