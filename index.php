<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect to dashboard if logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$page_title = "CarRent Pro - Premium Car Rental Service";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#7c3aed',
                        secondary: '#f8fafc'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="index.php" class="text-2xl font-bold text-primary">CarRent Pro</a>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="#home" class="text-gray-900 hover:text-primary px-3 py-2 text-sm font-medium">Home</a>
                        <a href="cars.php" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">Our Cars</a>
                        <a href="#about" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">About</a>
                        <a href="#contact" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">Contact</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="login.php" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">Login</a>
                    <a href="register.php" class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-purple-700">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="bg-gradient-to-r from-purple-600 to-blue-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">Premium Car Rental Service</h1>
                <p class="text-xl md:text-2xl mb-8 text-purple-100">Experience luxury and comfort with our premium fleet of vehicles</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="cars.php" class="bg-white text-primary px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">View Our Fleet</a>
                    <a href="register.php" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-primary transition duration-300">Book Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Cars Section -->
    <section id="cars" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Our Premium Fleet</h2>
                <p class="text-lg text-gray-600">Choose from our carefully curated collection of luxury and economy vehicles</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                try {
                    $cars = get_all_cars($pdo);
                    $featured_cars = array_slice($cars, 0, 6);
                    
                    foreach ($featured_cars as $car) {
                        renderCarCard($car);
                    }
                } catch (Exception $e) {
                    echo "<div class='col-span-full text-center py-8'>
                            <div class='bg-gray-100 rounded-lg p-8'>
                                <i class='fas fa-car text-gray-400 text-6xl mb-4'></i>
                                <h3 class='text-xl font-semibold text-gray-700 mb-2'>Database Setup Required</h3>
                                <p class='text-gray-500 mb-4'>Please complete the setup to view available cars.</p>
                                <a href='setup.php' class='bg-primary text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition duration-300'>
                                    Complete Setup
                                </a>
                            </div>
                          </div>";
                }
                ?>
            </div>
            
            <div class="text-center mt-12">
                <a href="cars.php" class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:bg-purple-700 transition duration-300">
                    View All Cars
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Why Choose CarRent Pro?</h2>
                <p class="text-lg text-gray-600">We provide exceptional service and premium vehicles for all your transportation needs</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="bg-primary text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Fully Insured</h3>
                    <p class="text-gray-600">All our vehicles come with comprehensive insurance coverage for your peace of mind.</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-primary text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-comments text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">24/7 Chat Support</h3>
                    <p class="text-gray-600">Real-time chat support with automatic responses and file upload capabilities.</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-primary text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-shield text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Role-Based Access</h3>
                    <p class="text-gray-600">Secure system with different access levels for users, admins, and super admins.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">About CarRent Pro</h2>
                    <p class="text-lg text-gray-600 mb-6">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. 
                        Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
                    </p>
                    <p class="text-lg text-gray-600 mb-8">
                        Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. 
                        Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                    </p>
                    <a href="#contact" class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition duration-300">Get In Touch</a>
                </div>
                <div class="bg-gradient-to-br from-purple-400 to-blue-400 rounded-lg h-96 flex items-center justify-center">
                    <i class="fas fa-car text-white text-8xl"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-16 bg-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Contact Us</h2>
                <p class="text-lg text-gray-600">Get in touch with us for any inquiries or bookings</p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <div>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-6">Get In Touch</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt text-primary text-xl mr-4"></i>
                            <span class="text-gray-600">123 Car Rental Street, Downtown City, 12345</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-phone text-primary text-xl mr-4"></i>
                            <span class="text-gray-600">+1 (555) 123-4567</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-primary text-xl mr-4"></i>
                            <span class="text-gray-600">info@carrentpro.com</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-comments text-primary text-xl mr-4"></i>
                            <span class="text-gray-600">24/7 Live Chat Support</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <form class="space-y-6">
                        <div>
                            <input type="text" placeholder="Your Name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <input type="email" placeholder="Your Email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <textarea placeholder="Your Message" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-purple-700 transition duration-300">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-2xl font-bold text-primary mb-4">CarRent Pro</h3>
                    <p class="text-gray-400">Premium car rental service with advanced features and role-based access control.</p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#home" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="cars.php" class="text-gray-400 hover:text-white">Our Cars</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-white">About</a></li>
                        <li><a href="#contact" class="text-gray-400 hover:text-white">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Services</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Luxury Cars</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Economy Cars</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Long Term Rental</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Airport Transfer</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Follow Us</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400">&copy; 2024 CarRent Pro. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function bookCar(carId) {
            alert('Please login to book a car.');
            window.location.href = 'login.php';
        }
    </script>
</body>
</html>
