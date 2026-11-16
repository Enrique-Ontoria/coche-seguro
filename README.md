# 🚗 CocheSeguro — Used Car Buyer's Guide & Inspection Tool

Web application built to help everyday buyers evaluate second-hand vehicles without getting scammed, featuring engine reliability traffic lights and an in-situ interactive inspection checklist.

## Key Features
- **Dynamic Search & Usage Filters:** Filter vehicles by brand, model, chassis generation code, or intended driving profile.
- **Engine Reliability Traffic Light:** Clear color-coded breakdown (Green, Yellow, Red) highlighting bulletproof engines versus known endemic mechanical failures.
- **Interactive On-Site Checklist:** Real-time DOM risk evaluator built with Vanilla JavaScript and HTML5 `data-*` attributes that calculates purchase risk as the user inspects the vehicle.

## Tech Stack
- **Backend:** PHP 8+ (PDO with prepared statements)
- **Database:** MySQL / MariaDB (Relational schema with foreign keys)
- **Frontend:** HTML5, CSS3, Bootstrap 5, Vanilla JavaScript

## Local Setup
1. Clone this repository into your local web server root directory (`htdocs` for XAMPP).
2. Create a database named `coche_seguro` in phpMyAdmin and import `database/coche_seguro.sql`.
3. Duplicate `db.example.php`, rename it to `db.php`, and fill in your local database credentials.
4. Start Apache and MySQL, then navigate to `http://localhost/coche-seguro/index.php`.