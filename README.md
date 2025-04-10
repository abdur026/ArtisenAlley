# ArtisanAlley 🎨

A handcrafted marketplace for artisanal products built with PHP and MySQL.

## About the Project

ArtisanAlley is an e-commerce platform designed to showcase and sell unique, handcrafted items from independent artisans. The platform connects creators with customers who appreciate handmade quality and craftsmanship.

## Features

- **Product Browsing & Search**
  - Browse products by category
  - Detailed product pages with images
  - Search functionality with filters
  
- **User Accounts**
  - Secure registration and login
  - User profiles with personal information
  - Order history tracking
  
- **Shopping Experience**
  - Session-based shopping cart
  - Quantity management
  - Checkout process
  
- **Review System**
  - Star ratings (1-5 stars)
  - Written reviews with user attribution
  - Average rating displays
  
- **Admin Dashboard**
  - Product management
  - Order processing
  - User management
  - Analytics reporting

## Tech Stack

- **Backend**: PHP
- **Database**: MySQL
- **Frontend**: HTML, CSS, JavaScript
- **UI Libraries**: Font Awesome for icons
- **Styling**: Custom CSS with responsive design

## Getting Started

### Prerequisites

- PHP 7.4+
- MySQL 5.7+
- Web server (Apache/Nginx)

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/ArtisanAlley.git
   cd ArtisanAlley
   ```

2. Import the database:
   - Create a new MySQL database
   - Import `database/schema.sql` to set up tables
   - Optionally import `database/sample_data.sql` for demo products

3. Configure the database connection:
   - Update credentials in `config/db.php`

4. Set up your web server:
   - Configure document root to point to the project
   - Ensure proper permissions for `uploads` directory

5. Visit the site in your browser

## Project Structure

- `public/` - Front-facing PHP files
- `includes/` - Reusable PHP components
- `config/` - Configuration files
- `database/` - Database scripts
- `uploads/` - User-uploaded content
- `src/` - CSS and JavaScript files

## Development Team

- Khizar Aamir
- Abdur Rehman
- Haider Ali

## License

This project is available for educational purposes. Please contact the authors for commercial use.

---

Made with ❤️ for artisans and craft lovers
