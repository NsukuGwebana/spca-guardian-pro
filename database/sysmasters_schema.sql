-- =====================================================================
-- SPCA Guardian Pro: database structure (MySQL 5.7+ / MariaDB 10.3+)
--
-- Reconstructed from the application's SQL queries: every table and
-- column the PHP code reads or writes is defined here. Column types
-- follow how the code binds each value. Structure only, no data.
--
-- Import: phpMyAdmin > create database `sysmasters` > Import > this file
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Staff accounts and security
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS users (
    idnumber          INT AUTO_INCREMENT PRIMARY KEY,
    surname           VARCHAR(100) NOT NULL,
    firstname         VARCHAR(100) NOT NULL,
    username          VARCHAR(50)  NOT NULL UNIQUE,
    userpassword      VARCHAR(255) NOT NULL,            -- password_hash() output (60+ chars)
    userrole          VARCHAR(30)  NOT NULL,            -- Administrator | Manager | Vet | Volunteer | Guest
    phonenumber       VARCHAR(20),
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS logtrails (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    userid      INT,                                    -- users.idnumber
    attempttime DATETIME DEFAULT CURRENT_TIMESTAMP,
    comment     VARCHAR(255),
    INDEX idx_logtrails_user (userid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Animals and kennels
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS animals (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    Aname            VARCHAR(100) NOT NULL,
    Species          VARCHAR(50)  NOT NULL,
    Animal_breed     VARCHAR(100),
    Aage             INT DEFAULT 0,                     -- years
    Color            VARCHAR(50) DEFAULT 'Unknown',
    Aweight          DECIMAL(6,2),                      -- kg
    Width            DECIMAL(6,2),                      -- metres, used for kennel allocation
    Height           DECIMAL(6,2),                      -- metres
    Health_status    VARCHAR(20) DEFAULT 'Healthy',     -- Healthy | Unhealthy
    Intake_date      DATE,
    status           VARCHAR(30) DEFAULT 'Available',   -- Available | Pending | Not Available | Adopted
    Kennel_status    VARCHAR(100),                      -- name of the assigned kennel
    treatment_status VARCHAR(50),                       -- e.g. Under Treatment | Treatment Completed
    animal_picture   VARCHAR(255),                      -- file path in uploads/
    seen             TINYINT(1) DEFAULT 0,              -- admin notification flag
    is_deleted       TINYINT(1) DEFAULT 0,              -- soft delete (archive)
    deleted_at       DATETIME NULL,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_animals_status (status, is_deleted),
    INDEX idx_animals_species (Species)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kennels (
    kennel_id             INT AUTO_INCREMENT PRIMARY KEY,
    kennel_name           VARCHAR(100) NOT NULL,
    max_width             DECIMAL(6,2) NOT NULL,        -- metres
    max_height            DECIMAL(6,2) NOT NULL,        -- metres
    species_compatibility VARCHAR(30) DEFAULT 'General',-- General | Dog | Cat | Bird | Rabbit | Emergency
    kennel_type           VARCHAR(30) DEFAULT 'Standard',
    status                VARCHAR(30) DEFAULT 'Available', -- Available | Occupied | Maintenance
    notes                 TEXT,
    is_deleted            TINYINT(1) DEFAULT 0,
    deleted_at            DATETIME NULL,
    created_at            DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kennels_status (status, is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS animal_kennel (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    animal_id       INT NOT NULL,                       -- animals.id
    kennel_id       INT NOT NULL,                       -- kennels.kennel_id
    assignment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_emergency    TINYINT(1) DEFAULT 0,
    release_date    DATETIME NULL,
    INDEX idx_ak_animal (animal_id),
    INDEX idx_ak_kennel (kennel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Veterinary care
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS appointments (            -- vet appointments
    app_id     INT AUTO_INCREMENT PRIMARY KEY,
    animal_id  INT NOT NULL,                            -- animals.id
    vet_id     INT,                                     -- users.idnumber (role Vet)
    app_date   DATETIME NOT NULL,
    Reason     VARCHAR(255),
    app_status VARCHAR(30) DEFAULT 'Scheduled',         -- Scheduled | Completed | Missed | Cancelled
    INDEX idx_app_animal (animal_id),
    INDEX idx_app_date (app_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS treatments (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    app_id            INT NOT NULL,                     -- appointments.app_id
    treatment_types   VARCHAR(100),
    treatment_details TEXT,
    treatment_date    DATE,
    recorded_by       VARCHAR(100),
    treatment_status  VARCHAR(30) DEFAULT 'InProgress',
    INDEX idx_treat_app (app_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS medical_care (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    Animal_id        INT NOT NULL,                      -- animals.id
    Vet_id           INT,                               -- users.idnumber
    Appointment_Date DATE,
    Treatment_type   VARCHAR(100),
    procedure_status VARCHAR(30) DEFAULT 'Pending',     -- Pending | Completed
    INDEX idx_med_animal (Animal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vaccinations (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT,                                      -- animals.id
    vaccine   VARCHAR(100),
    due_date  DATE,
    status    VARCHAR(30) DEFAULT 'Pending',
    INDEX idx_vacc_due (due_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Adoption and fostering
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS adoptions (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    animal_id           INT NOT NULL,                   -- animals.id
    full_name           VARCHAR(150) NOT NULL,
    id_number           VARCHAR(20),
    date_of_birth       DATE,
    phone               VARCHAR(20),
    email               VARCHAR(255),
    address_street      VARCHAR(255),
    address_city        VARCHAR(100),
    address_province    VARCHAR(100),
    address_postal_code VARCHAR(10),
    employment_status   VARCHAR(50),
    monthly_income      VARCHAR(50),
    housing_type        VARCHAR(50),
    has_fenced_yard     TINYINT(1) DEFAULT 0,
    has_other_pets      TINYINT(1) DEFAULT 0,
    other_pets_details  TEXT,
    reason              TEXT,
    id_copy             VARCHAR(255),                   -- uploaded file path in uploads/id_copy/
    status              VARCHAR(30) DEFAULT 'Pending',  -- Pending | Approved | Rejected
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_adopt_animal (animal_id),
    INDEX idx_adopt_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS appointment (             -- adoption meet-and-greet appointments
    appointment_id   INT AUTO_INCREMENT PRIMARY KEY,
    adoption_id      INT NOT NULL,                      -- adoptions.id
    appointment_date DATETIME NOT NULL,
    notes            TEXT,
    status           VARCHAR(30) DEFAULT 'Scheduled',   -- Scheduled | Completed | Missed
    INDEX idx_appt_adoption (adoption_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fosters (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    animal_id           INT NOT NULL,                   -- animals.id
    full_name           VARCHAR(150) NOT NULL,
    id_number           VARCHAR(20),
    date_of_birth       DATE,
    phone               VARCHAR(20),
    email               VARCHAR(255),
    address_street      VARCHAR(255),
    address_city        VARCHAR(100),
    address_province    VARCHAR(100),
    address_postal_code VARCHAR(10),
    employment_status   VARCHAR(50),
    monthly_income      VARCHAR(50),
    housing_type        VARCHAR(50),
    has_fenced_yard     TINYINT(1) DEFAULT 0,
    has_other_pets      TINYINT(1) DEFAULT 0,
    other_pets_details  TEXT,
    reason              TEXT,
    id_copy             VARCHAR(255),
    status              VARCHAR(30) DEFAULT 'Pending',
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_foster_animal (animal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Cruelty reports and investigations
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS cruelty_report (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    reporter_name VARCHAR(150),
    contact_info  VARCHAR(255),
    animal_type   VARCHAR(50),
    cruelty_type  VARCHAR(100),
    location      VARCHAR(255),
    witnesses     TEXT,
    date          DATETIME,
    description   TEXT,
    seen          TINYINT(1) DEFAULT 0,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cruelty_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cruelty_investigation (
    cruelty_id   INT PRIMARY KEY,                       -- cruelty_report.id (one investigation per report)
    investigator VARCHAR(150),
    status       VARCHAR(30) DEFAULT 'Open',            -- Open | In Progress | Closed
    actions      TEXT,
    follow_up    TEXT,
    notes        TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Donations
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS donor (
    donor_id  INT AUTO_INCREMENT PRIMARY KEY,
    F_name    VARCHAR(100) NOT NULL,
    L_name    VARCHAR(100) NOT NULL,
    E_address VARCHAR(255),
    Phone_num VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS donations (
    donation_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id    INT NOT NULL,                           -- donor.donor_id
    damount     DECIMAL(10,2) NOT NULL,                 -- rand
    dlocation   VARCHAR(100),
    dtype       VARCHAR(50),
    ddate       DATETIME NOT NULL,
    seen        TINYINT(1) DEFAULT 0,                   -- acknowledged / receipt sent
    INDEX idx_don_donor (donor_id, ddate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Volunteers, events and community
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS volunteers (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    first_name      VARCHAR(100),
    last_name       VARCHAR(100),
    email           VARCHAR(255) UNIQUE,
    phone           VARCHAR(20),
    join_date       DATE,
    profile_picture VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS volunteer_tasks (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    volunteer_id INT NOT NULL,                          -- volunteers.id
    task         VARCHAR(255),
    status       VARCHAR(30) DEFAULT 'Assigned',        -- Assigned | Completed
    INDEX idx_vt_volunteer (volunteer_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS volunteer_events (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    event_name  VARCHAR(150),
    event_date  DATE NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS community_engagement (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    volunteer_id         INT,
    volunteer_Fname      VARCHAR(100),
    volunteer_Lname      VARCHAR(100),
    contact_number       VARCHAR(20),
    email                VARCHAR(255),
    date                 DATE,
    activities_name      VARCHAR(150),
    activity_description TEXT,
    start_time           TIME,
    end_time             TIME,
    lapse_time           TIME,                          -- hours worked
    supervisor_name      VARCHAR(100),
    supervisor_surname   VARCHAR(100),
    status               VARCHAR(30) DEFAULT 'Pending', -- Pending | Approved | Completed
    INDEX idx_ce_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
    event_id       INT AUTO_INCREMENT PRIMARY KEY,
    event_name     VARCHAR(150) NOT NULL,
    event_date     DATE NOT NULL,
    event_time     TIME,
    location       VARCHAR(255),
    description    TEXT,
    max_volunteers INT,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Public contact and newsletter
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS contact_submissions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    email           VARCHAR(255) NOT NULL,
    subject         VARCHAR(255) NOT NULL,
    message         TEXT NOT NULL,
    submission_date DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS spca_subscribers (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    email             VARCHAR(255) NOT NULL UNIQUE,
    subscription_date DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
