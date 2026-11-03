-- SPCA Guardian Pro database schema (structure only, no data)
-- Import into an empty database: mysql -u <user> -p <database> < database/schema.sql
-- Then run database/migrations/001_security_and_workflows.sql.

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE `admin` (
  `Admin_id` int NOT NULL,
  `Medcare_report` varchar(45) DEFAULT NULL,
  `Phone_num` varchar(45) DEFAULT NULL,
  `Post_address` varchar(45) DEFAULT NULL,
  `Email_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`Admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `adoptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `animal_id` int NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address_street` varchar(200) NOT NULL,
  `address_city` varchar(100) NOT NULL,
  `address_province` varchar(100) NOT NULL,
  `address_postal_code` varchar(20) NOT NULL,
  `employment_status` varchar(50) NOT NULL,
  `monthly_income` decimal(10,2) DEFAULT NULL,
  `housing_type` enum('House','Apartment','Farm','Other') DEFAULT 'House',
  `has_fenced_yard` tinyint(1) DEFAULT '0',
  `has_other_pets` tinyint(1) DEFAULT '0',
  `other_pets_details` varchar(255) DEFAULT NULL,
  `reason` text NOT NULL,
  `id_copy` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `seen` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `animal_id` (`animal_id`),
  CONSTRAINT `adoptions_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `animal_archive` (
  `dog_id` int NOT NULL AUTO_INCREMENT,
  `Animal_breed` varchar(100) NOT NULL,
  `Health_status` varchar(50) NOT NULL,
  `Kennel_status` varchar(45) NOT NULL,
  `Intake_date` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'available',
  `Aname` varchar(100) NOT NULL,
  `Aage` int NOT NULL,
  `Aheight` decimal(5,2) NOT NULL,
  `Aweight` decimal(5,2) NOT NULL,
  PRIMARY KEY (`dog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `animal_kennel` (
  `assignment_id` int NOT NULL AUTO_INCREMENT,
  `animal_id` int NOT NULL,
  `kennel_id` int NOT NULL,
  `assignment_date` datetime NOT NULL,
  `release_date` datetime DEFAULT NULL,
  `is_emergency` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`assignment_id`),
  KEY `idx_animal_id` (`animal_id`),
  KEY `idx_kennel_id` (`kennel_id`),
  KEY `idx_assignment_dates` (`assignment_date`,`release_date`),
  KEY `idx_current_assignments` (`animal_id`,`release_date`),
  CONSTRAINT `animal_kennel_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `animal_kennel_ibfk_2` FOREIGN KEY (`kennel_id`) REFERENCES `kennels` (`kennel_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `animal_registration` (
  `dog_id` int NOT NULL AUTO_INCREMENT,
  `Animal_breed` varchar(100) NOT NULL,
  `Health_status` varchar(50) NOT NULL,
  `Kennel_status` varchar(45) NOT NULL,
  `Intake_date` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'available',
  `Aname` varchar(100) NOT NULL,
  `Aage` int NOT NULL,
  `Aheight` decimal(5,2) NOT NULL,
  `Aweight` decimal(5,2) NOT NULL,
  PRIMARY KEY (`dog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `animals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Aname` varchar(255) NOT NULL,
  `Species` varchar(100) NOT NULL,
  `Animal_breed` varchar(100) DEFAULT NULL,
  `Aage` decimal(4,1) DEFAULT NULL,
  `Color` varchar(100) DEFAULT NULL,
  `Kennel_status` varchar(100) DEFAULT NULL,
  `Aweight` decimal(6,2) DEFAULT NULL,
  `Width` decimal(5,2) DEFAULT NULL,
  `Height` decimal(5,2) DEFAULT NULL,
  `Health_status` enum('Healthy','Unhealthy') NOT NULL,
  `Intake_date` date DEFAULT NULL,
  `status` enum('Available','Pending','Not Available','Adopted') DEFAULT NULL,
  `animal_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `treatment_status` text,
  `seen` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `appointment` (
  `appointment_id` int NOT NULL AUTO_INCREMENT,
  `adoption_id` int NOT NULL,
  `appointment_date` datetime NOT NULL,
  `notes` text,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`appointment_id`),
  KEY `adoption_id` (`adoption_id`),
  CONSTRAINT `appointment_ibfk_1` FOREIGN KEY (`adoption_id`) REFERENCES `adoptions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `appointments` (
  `app_id` int NOT NULL,
  `animal_id` int DEFAULT NULL,
  `vet_id` int DEFAULT NULL,
  `app_date` date DEFAULT NULL,
  `Reason` text,
  `app_status` enum('Scheduled','InProgress','Completed') NOT NULL,
  PRIMARY KEY (`app_id`),
  KEY `animal_id_idx` (`animal_id`),
  CONSTRAINT `animal_id` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `community_engagement` (
  `volunteer_id` int NOT NULL AUTO_INCREMENT,
  `volunteer_Fname` varchar(100) NOT NULL,
  `volunteer_Lname` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `date` date NOT NULL,
  `activities_name` varchar(150) NOT NULL,
  `activity_description` text,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `lapse_time` varchar(250) DEFAULT NULL,
  `supervisor_name` varchar(150) DEFAULT NULL,
  `supervisor_surname` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`volunteer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cruelty_investigation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cruelty_id` int NOT NULL,
  `investigator` varchar(100) NOT NULL,
  `status` enum('Pending','In Progress','Resolved','Closed') DEFAULT 'Pending',
  `actions` text,
  `follow_up` enum('Yes','No') DEFAULT 'No',
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cruelty` (`cruelty_id`),
  CONSTRAINT `cruelty_investigation_ibfk_1` FOREIGN KEY (`cruelty_id`) REFERENCES `cruelty_report` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cruelty_report` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporter_name` varchar(100) NOT NULL,
  `contact_info` varchar(150) DEFAULT NULL,
  `animal_type` varchar(100) NOT NULL,
  `cruelty_type` varchar(150) NOT NULL,
  `location` varchar(255) NOT NULL,
  `witnesses` text,
  `incident_date` date DEFAULT NULL,
  `date` date NOT NULL,
  `seen` tinyint(1) DEFAULT '0',
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `donations` (
  `donation_id` int NOT NULL AUTO_INCREMENT,
  `dtype` varchar(45) DEFAULT NULL,
  `dlocation` varchar(45) DEFAULT NULL,
  `damount` float DEFAULT NULL,
  `donor_id` int DEFAULT NULL,
  `ddate` date DEFAULT NULL,
  `seen` tinyint(1) DEFAULT '0',
  `receipt_sent` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`donation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `donor` (
  `donor_id` int NOT NULL AUTO_INCREMENT,
  `F_name` varchar(45) DEFAULT NULL,
  `L_name` varchar(45) DEFAULT NULL,
  `E_address` varchar(45) DEFAULT NULL,
  `Phone_num` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`donor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `event_signups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `volunteer_id` int NOT NULL,
  `signup_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `event_signups_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` text,
  `max_volunteers` int DEFAULT '20',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fosters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `animal_id` int NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address_street` varchar(255) NOT NULL,
  `address_city` varchar(100) NOT NULL,
  `address_province` varchar(100) NOT NULL,
  `address_postal_code` varchar(20) NOT NULL,
  `employment_status` varchar(50) NOT NULL,
  `monthly_income` decimal(10,2) DEFAULT NULL,
  `housing_type` varchar(50) DEFAULT NULL,
  `has_fenced_yard` tinyint(1) DEFAULT '0',
  `has_other_pets` tinyint(1) DEFAULT '0',
  `other_pets_details` varchar(255) DEFAULT NULL,
  `reason` text NOT NULL,
  `id_copy` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `kennels` (
  `kennel_id` int NOT NULL AUTO_INCREMENT,
  `kennel_name` varchar(100) NOT NULL,
  `max_width` decimal(5,2) NOT NULL,
  `max_height` decimal(5,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'available',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `species_compatibility` varchar(20) NOT NULL DEFAULT 'General' COMMENT 'Species this kennel is compatible with: Dog, Cat, Bird, Rabbit, General, Emergency',
  `kennel_type` varchar(20) NOT NULL DEFAULT 'Standard' COMMENT 'Type of kennel: Standard, Large, Outdoor, Indoor, Quarantine, Emergency',
  PRIMARY KEY (`kennel_id`),
  UNIQUE KEY `uc_kennel_name` (`kennel_name`),
  CONSTRAINT `chk_max_height` CHECK ((`max_height` > 0)),
  CONSTRAINT `chk_max_width` CHECK ((`max_width` > 0)),
  CONSTRAINT `kennels_chk_1` CHECK ((`status` in (_utf8mb4'available',_utf8mb4'occupied',_utf8mb4'maintenance',_utf8mb4'cleaning')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `medical_care` (
  `id` int DEFAULT NULL,
  `Vet_id` varchar(45) NOT NULL,
  `Appointment_Date` varchar(45) DEFAULT NULL,
  `notes` text,
  `Procedure_status` enum('Scheduled','In progress','Completed') DEFAULT NULL,
  `Treatment_status` enum('In progress','Completed') DEFAULT NULL,
  `vaccination` varchar(45) DEFAULT NULL,
  `spaying` varchar(45) DEFAULT NULL,
  `neutering` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`Vet_id`),
  KEY `id_idx` (`id`),
  CONSTRAINT `id` FOREIGN KEY (`id`) REFERENCES `animals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `message_submissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `submission_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pets` (
  `pet_id` int NOT NULL AUTO_INCREMENT,
  `pet_name` varchar(50) NOT NULL,
  `breed` varchar(50) NOT NULL,
  `description` text,
  `available` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`pet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `spca_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `subscription_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `spca_system` (
  `User_id` varchar(45) NOT NULL,
  `MedCare_report` varchar(45) DEFAULT NULL,
  `Cruelty_report` char(45) DEFAULT NULL,
  `Phone_num` int DEFAULT NULL,
  `Email_address` varchar(45) DEFAULT NULL,
  `Role` mediumtext,
  `Postal_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`User_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `treatments` (
  `treatment_id` int NOT NULL AUTO_INCREMENT,
  `app_id` int DEFAULT NULL,
  `treatment_types` varchar(100) DEFAULT NULL,
  `treatment_details` text,
  `treatment_date` varchar(45) DEFAULT NULL,
  `recorded_by` varchar(45) DEFAULT NULL,
  `treatment_status` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`treatment_id`),
  KEY `appointment_id_idx` (`app_id`),
  CONSTRAINT `appointment_id` FOREIGN KEY (`app_id`) REFERENCES `appointments` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `idnumber` int NOT NULL AUTO_INCREMENT,
  `surname` varchar(35) NOT NULL,
  `firstname` varchar(35) NOT NULL,
  `username` varchar(35) NOT NULL,
  `userpassword` varchar(500) NOT NULL,
  `userrole` varchar(45) NOT NULL,
  `phonenumber` varchar(15) DEFAULT NULL,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idnumber`),
  UNIQUE KEY `User_id` (`idnumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vet` (
  `Vet_id` int NOT NULL,
  `F_name` varchar(45) DEFAULT NULL,
  `L_name` varchar(45) DEFAULT NULL,
  `E_address` varchar(45) DEFAULT NULL,
  `Phone_num` varchar(45) DEFAULT NULL,
  `Post_Address` varchar(45) DEFAULT NULL,
  `Pract_Num` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`Vet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `volunteer` (
  `idnumber` int NOT NULL,
  `F_name` varchar(35) NOT NULL,
  `L_name` varchar(35) NOT NULL,
  `Email_address` varchar(35) DEFAULT NULL,
  `Phone_num` int NOT NULL,
  `Age` int NOT NULL,
  `seen` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`idnumber`),
  UNIQUE KEY `idnumber` (`idnumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
