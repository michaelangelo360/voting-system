# AllVotesGH Voting System

A PHP/MySQL-based voting system API with Paystack integration and USSD support.

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer

## Installation

1. Clone the repository
```
git clone https://github.com/yourusername/voting-system.git
cd voting-system
```

2. Install dependencies
```
composer install
```

3. Create the database
```
mysql -u username -p -e "CREATE DATABASE voting_system"
mysql -u username -p voting_system < database/schema.sql
```

4. Configure the application
```
cp config/config.example.php config/config.php
```
Edit the `config.php` file to set your database credentials, Paystack API keys, and other settings.

5. Set up file permissions
```
chmod -R 755 .
chmod -R 775 uploads/ logs/
```

6. Configure your web server to point to the `public` directory or use PHP's built-in server for development
```
php -S localhost:8000
```

## API Endpoints

### Admin Endpoints

- **POST /api/v1/admin/login**
  - Authenticate admin user
  - Body: `{ "email": "admin@example.com", "password": "password" }`

- **GET /api/v1/admin/events**
  - Get all events
  - Headers: `Authorization: Bearer {token}`

- **POST /api/v1/admin/events**
  - Create a new event
  - Headers: `Authorization: Bearer {token}`
  - Body: `{ "name": "Event Name", "date": "2023-12-31", "cost": 1.0, "owner": "owner@example.com", "owner_password": "password" }`

- **PUT /api/v1/admin/events/{id}**
  - Update an event
  - Headers: `Authorization: Bearer {token}`
  - Body: `{ "name": "Updated Event Name" }`

- **DELETE /api/v1/admin/events/{id}**
  - Delete an event
  - Headers: `Authorization: Bearer {token}`

- **POST /api/v1/admin/upload/event-image**
  - Upload an event image
  - Headers: `Authorization: Bearer {token}`
  - Body: `FormData` with `image` file and `event_id`

### Organizer Endpoints

- **GET /api/v1/organizer/categories**
  - Get categories
  - Headers: `Authorization: Bearer {token}`
  - Query params: `organizer_id` (optional)

- **POST /api/v1/organizer/categories**
  - Create a new category
  - Headers: `Authorization: Bearer {token}`
  - Body: `{ "name": "Category Name", "organizer_id": 1 }`

- **PUT /api/v1/organizer/categories/{id}**
  - Update a category
  - Headers: `Authorization: Bearer {token}`
  - Body: `{ "name": "Updated Category Name" }`

- **DELETE /api/v1/organizer/categories/{id}**
  - Delete a category
  - Headers: `Authorization: Bearer {token}`

- **POST /api/v1/organizer/upload/category-image**
  - Upload a category image
  - Headers: `Authorization: Bearer {token}`
  - Body: `FormData` with `image` file and `category_id`

- **GET /api/v1/organizer/nominees**
  - Get nominees
  - Headers: `Authorization: Bearer {token}`
  - Query params: `organizer_id` (optional), `category_id` (optional)

- **POST /api/v1/organizer/nominees**
  - Create a new nominee
  - Headers: `Authorization: Bearer {token}`
  - Body: `{ "name": "Nominee Name", "category_id": 1, "organizer_id": 1 }`

- **PUT /api/v1/organizer/nominees/{id}**
  - Update a nominee
  - Headers: `Authorization: Bearer {token}`
  - Body: `{ "name": "Updated Nominee Name" }`

- **DELETE /api/v1/organizer/nominees/{id}**
  - Delete a nominee
  - Headers: `Authorization: Bearer {token}`

- **POST /api/v1/organizer/upload/nominee-image**
  - Upload a nominee image
  - Headers: `Authorization: Bearer {token}`
  - Body: `FormData` with `image` file and `nominee_id`

- **POST /api/v1/organizer/vote**
  - Record a vote
  - Body: `{ "nominee_id": 1, "votes": 5, "phone_number": "+233123456789", "email": "voter@example.com" }`

- **GET /api/v1/organizer/vote-records**
  - Get vote records
  - Headers: `Authorization: Bearer {token}`
  - Query params: `organizer_id` (optional)

### Payment Endpoints

- **POST /api/v1/payment/verify**
  - Verify a transaction
  - Body: `{ "reference": "payment_reference" }`

- **POST /api/v1/payment/process**
  - Process a payment
  - Body: `{ "nominee_id": 1, "votes": 5, "email": "voter@example.com", "phone": "+233123456789" }`

### USSD Endpoint

- **POST /api/v1/ussd**
  - Handle USSD requests
  - Body: `{ "sessionID": "session_id", "msisdn": "+233123456789", "network": "MTN", "newSession": true, "userData": "user_input" }`

## Project Structure

```
voting-system/
│
├── config/
│   ├── Database.php              # Database connection
│   └── config.php                # Configuration variables
│
├── controllers/
│   ├── AdminController.php       # Admin functionalities
│   ├── OrganizerController.php   # Organizer functionalities
│   ├── UssdController.php        # USSD handling
│   └── PaymentController.php     # Payment processing
│
├── models/
│   ├── Admin.php                 # Admin model
│   ├── Event.php                 # Event model
│   ├── Category.php              # Category model
│   ├── Nominee.php               # Nominee model
│   ├── Vote.php                  # Vote model
│   ├── Payment.php               # Payment model
│   └── UssdSession.php           # USSD session model
│
├── services/
│   ├── AuthService.php           # Authentication services
│   ├── FileService.php           # File upload handling
│   ├── PaystackService.php       # Paystack integration
│   └── UssdService.php           # USSD session management
│
├── utils/
│   ├── Response.php              # API response formatter
│   ├── Validator.php             # Input validation
│   └── Logger.php                # System logging
│
├── routes/
│   └── api.php                   # API routes definition
│
├── uploads/                      # Directory for uploaded files
│   ├── events/                   # Event images
│   ├── categories/               # Category images
│   └── nominees/                 # Nominee images
│
├── logs/                         # Log files
│
├── .htaccess                     # Apache configuration
├── index.php                     # Entry point
├── composer.json                 # Composer package definition
└── README.md                     # Project documentation
```

## License

This project is licensed under the MIT License - see the LICENSE file for details.