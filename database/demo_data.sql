-- =====================================================================
-- SPCA Guardian Pro: DEMO DATA for a public demo site
--
-- Every person, animal and record below is fictional sample data.
-- Import AFTER sysmasters_schema.sql. Dates are relative to the import
-- day, so dashboards and "last 30 days" figures always look current.
-- Re-import both files to reset the demo.
--
-- Demo logins (all use the password  SpcaDemo2026! ):
--   demo_admin      -> Administrator dashboard
--   demo_vet        -> Vet dashboard
--   demo_volunteer  -> Volunteer dashboard
-- =====================================================================

SET NAMES utf8mb4;

-- Staff accounts (passwords stored as password_hash output)
INSERT INTO users (surname, firstname, username, userpassword, userrole, phonenumber) VALUES
('Admin',     'Demo', 'demo_admin',     '$2y$10$8eNkvlbiMj8zthhcnI2xVebCb8auLSwOSanFHX8MY3FAqBCYwp4uq', 'Administrator', '000 000 0001'),
('Vet',       'Demo', 'demo_vet',       '$2y$10$yAfb40JuTPeS.BrIc8QXDeVD6p4Q8CN26uUbBxcrRRhHTj7OV1Liy', 'Vet',           '000 000 0002'),
('Volunteer', 'Demo', 'demo_volunteer', '$2y$10$k1/os6G6S85Kh.zRV4dlGO/z.hvuW6pTD1ep2ueGgNax.rFj78CV2', 'Volunteer',     '000 000 0003');

-- Kennels
INSERT INTO kennels (kennel_name, max_width, max_height, species_compatibility, kennel_type, status, notes) VALUES
('Dog Run A',      2.50, 2.00, 'Dog',     'Large',      'Occupied',  'Outdoor run with shade'),
('Dog Run B',      2.00, 1.80, 'Dog',     'Standard',   'Occupied',  NULL),
('Dog Run C',      2.00, 1.80, 'Dog',     'Standard',   'Available', NULL),
('Cattery 1',      1.20, 1.20, 'Cat',     'Indoor',     'Occupied',  'Climbing shelves'),
('Cattery 2',      1.20, 1.20, 'Cat',     'Indoor',     'Available', NULL),
('Small Pets',     1.00, 1.00, 'General', 'Indoor',     'Occupied',  'Birds and rabbits'),
('Quarantine 1',   1.50, 1.50, 'General', 'Quarantine', 'Available', 'New intakes awaiting a vet check'),
('Kennel 8',       2.00, 1.80, 'General', 'Standard',   'Maintenance', 'Gate repair');

-- Animals (photos are sample images in Images/)
INSERT INTO animals (Aname, Species, Animal_breed, Aage, Color, Aweight, Width, Height, Health_status, Intake_date, status, Kennel_status, treatment_status, animal_picture, seen) VALUES
('Rocky',   'Dog',    'Jack Russell Terrier', 3, 'Tan and white', 7.50,  0.80, 0.60, 'Healthy',   DATE_SUB(CURDATE(), INTERVAL 95 DAY), 'Available',     'Dog Run B',  NULL,              'Images/Bobby.jpeg', 1),
('Snowy',   'Dog',    'Other',                1, 'White',         12.00, 1.00, 0.80, 'Healthy',   DATE_SUB(CURDATE(), INTERVAL 40 DAY), 'Pending',       'Dog Run A',  NULL,              'Images/newdog.jpg', 1),
('Ginger',  'Cat',    'Other',                0, 'Orange',        1.20,  0.40, 0.30, 'Unhealthy', DATE_SUB(CURDATE(), INTERVAL 6 DAY),  'Not Available', 'Cattery 1',  'Under Treatment', 'Images/cat.jpeg',   0),
('Marmalade','Cat',   'Maine Coon',           4, 'Orange',        5.80,  0.70, 0.50, 'Healthy',   DATE_SUB(CURDATE(), INTERVAL 70 DAY), 'Available',     'Cattery 1',  NULL,              'Images/kitty.jpg',  1),
('Kiwi',    'Bird',   'Parakeet',             2, 'Green',         0.04,  0.30, 0.40, 'Healthy',   DATE_SUB(CURDATE(), INTERVAL 25 DAY), 'Available',     'Small Pets', NULL,              'Images/bird.jpeg',  1),
('Biscuit', 'Rabbit', 'Holland Lop',          1, 'Fawn',          1.80,  0.50, 0.40, 'Healthy',   DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'Available',     'Small Pets', NULL,              'Images/rabbit.jpg', 0),
('Duke',    'Dog',    'German Shepherd',      6, 'Black and tan', 32.00, 1.40, 1.10, 'Healthy',   DATE_SUB(CURDATE(), INTERVAL 150 DAY),'Adopted',       NULL,         'Treatment Completed', NULL,            1),
('Luna',    'Cat',    'Siamese',              2, 'Cream',         3.90,  0.60, 0.40, 'Healthy',   DATE_SUB(CURDATE(), INTERVAL 120 DAY),'Adopted',       NULL,         NULL,              NULL,                1);

-- Kennel placements for animals still at the shelter (animal ids 1-6 above)
INSERT INTO animal_kennel (animal_id, kennel_id, assignment_date, is_emergency) VALUES
(1, 2, DATE_SUB(NOW(), INTERVAL 95 DAY), 0),
(2, 1, DATE_SUB(NOW(), INTERVAL 40 DAY), 0),
(3, 4, DATE_SUB(NOW(), INTERVAL 6 DAY),  0),
(4, 4, DATE_SUB(NOW(), INTERVAL 70 DAY), 0),
(5, 6, DATE_SUB(NOW(), INTERVAL 25 DAY), 0),
(6, 6, DATE_SUB(NOW(), INTERVAL 15 DAY), 0);

-- Adoption applications (fictional applicants)
INSERT INTO adoptions (animal_id, full_name, id_number, date_of_birth, phone, email, address_street, address_city, address_province, address_postal_code, employment_status, monthly_income, housing_type, has_fenced_yard, has_other_pets, other_pets_details, reason, status, created_at) VALUES
(7, 'Sample Applicant One',   '0000000000001', '1990-04-12', '000 111 2222', 'applicant1@example.com', '1 Sample Street', 'Makhanda', 'Eastern Cape', '6139', 'Employed',      'R15 000 - R25 000', 'House',     1, 0, NULL,           'Looking for a loyal family dog.',      'Approved', DATE_SUB(NOW(), INTERVAL 120 DAY)),
(8, 'Sample Applicant Two',   '0000000000002', '1995-09-03', '000 111 3333', 'applicant2@example.com', '2 Sample Street', 'Makhanda', 'Eastern Cape', '6139', 'Employed',      'R10 000 - R15 000', 'Apartment', 0, 0, NULL,           'Quiet home, work from home.',          'Approved', DATE_SUB(NOW(), INTERVAL 90 DAY)),
(2, 'Sample Applicant Three', '0000000000003', '1988-01-20', '000 111 4444', 'applicant3@example.com', '3 Sample Street', 'Port Alfred','Eastern Cape', '6170', 'Self-employed', 'R25 000+',          'House',     1, 1, 'One older dog', 'Companion for our older dog.',         'Pending',  DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 'Sample Applicant Four',  '0000000000004', '2000-06-15', '000 111 5555', 'applicant4@example.com', '4 Sample Street', 'Makhanda', 'Eastern Cape', '6139', 'Student',       'Under R5 000',      'Townhouse', 0, 0, NULL,           'Always wanted a small dog.',           'Rejected', DATE_SUB(NOW(), INTERVAL 35 DAY));

-- Adoption meet-and-greet appointment
INSERT INTO appointment (adoption_id, appointment_date, notes, status) VALUES
(3, DATE_ADD(NOW(), INTERVAL 3 DAY), 'Meet-and-greet with Snowy and the applicant''s dog', 'Scheduled');

-- Foster application
INSERT INTO fosters (animal_id, full_name, phone, email, address_city, address_province, housing_type, has_fenced_yard, has_other_pets, reason, status, created_at) VALUES
(3, 'Sample Foster Carer', '000 222 1111', 'foster1@example.com', 'Makhanda', 'Eastern Cape', 'House', 1, 0, 'Can care for a kitten during recovery.', 'Pending', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Vet appointments and treatments (vet_id 2 = demo_vet)
INSERT INTO appointments (animal_id, vet_id, app_date, Reason, app_status) VALUES
(3, 2, DATE_SUB(NOW(), INTERVAL 5 DAY),  'Intake check: eye infection', 'Completed'),
(3, 2, DATE_ADD(NOW(), INTERVAL 2 DAY),  'Follow-up: eye infection',    'Scheduled'),
(1, 2, DATE_SUB(NOW(), INTERVAL 30 DAY), 'Annual vaccinations',         'Completed'),
(6, 2, DATE_ADD(NOW(), INTERVAL 6 DAY),  'Sterilisation',               'Scheduled');

INSERT INTO treatments (app_id, treatment_types, treatment_details, treatment_date, recorded_by, treatment_status) VALUES
(1, 'Medication', 'Antibiotic eye drops twice daily for 7 days', DATE_SUB(CURDATE(), INTERVAL 5 DAY),  'Demo Vet', 'InProgress'),
(3, 'Vaccination', 'Annual booster',                            DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'Demo Vet', 'Completed');

INSERT INTO medical_care (Animal_id, Vet_id, Appointment_Date, Treatment_type, procedure_status) VALUES
(3, 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Eye treatment', 'Pending'),
(1, 2, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'Vaccination',  'Completed');

INSERT INTO vaccinations (animal_id, vaccine, due_date, status) VALUES
(6, 'Rabbit RHD booster', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'Pending'),
(5, 'Health check',       DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'Pending');

-- Cruelty reports and investigations
INSERT INTO cruelty_report (reporter_name, contact_info, animal_type, cruelty_type, location, witnesses, date, description, seen, created_at) VALUES
('Anonymous',       NULL,                 'Dog',  'Neglect',      'Location 1', NULL,        DATE_SUB(NOW(), INTERVAL 2 DAY),  'Dog chained without water or shade.',   0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Sample Reporter', 'reporter@example.com','Cat', 'Abandonment',  'Location 2', 'Neighbour', DATE_SUB(NOW(), INTERVAL 9 DAY),  'Kittens left in a box next to a road.', 1, DATE_SUB(NOW(), INTERVAL 9 DAY)),
('Anonymous',       NULL,                 'Dog',  'Physical abuse','Location 1', NULL,       DATE_SUB(NOW(), INTERVAL 21 DAY), 'Dog seen being hit by its owner.',      1, DATE_SUB(NOW(), INTERVAL 21 DAY)),
('Sample Reporter', 'reporter@example.com','Dog', 'Neglect',      'Location 2', NULL,        DATE_SUB(NOW(), INTERVAL 48 DAY), 'Very thin dog in a yard.',              1, DATE_SUB(NOW(), INTERVAL 48 DAY)),
('Anonymous',       NULL,                 'Bird', 'Neglect',      'Location 1', NULL,        DATE_SUB(NOW(), INTERVAL 75 DAY), 'Bird kept in a tiny dirty cage.',       1, DATE_SUB(NOW(), INTERVAL 75 DAY));

INSERT INTO cruelty_investigation (cruelty_id, investigator, status, actions, follow_up, notes) VALUES
(2, 'Demo Inspector', 'Closed',      'Kittens collected and taken to the shelter', NULL, 'Kittens now in foster care'),
(3, 'Demo Inspector', 'In Progress', 'Warning issued to the owner', 'Follow-up visit in 7 days', NULL);

-- Donors and donations
INSERT INTO donor (F_name, L_name, E_address, Phone_num) VALUES
('Sample', 'Donor One',   'donor1@example.com', '000 333 1111'),
('Sample', 'Donor Two',   'donor2@example.com', '000 333 2222'),
('Sample', 'Donor Three', 'donor3@example.com', '000 333 3333');

INSERT INTO donations (donor_id, damount, dlocation, dtype, ddate, seen) VALUES
(1, 500.00,  'Online', 'Money', DATE_SUB(NOW(), INTERVAL 3 DAY),   0),
(2, 1200.00, 'Online', 'Money', DATE_SUB(NOW(), INTERVAL 12 DAY),  1),
(3, 250.00,  'Shelter','Food',  DATE_SUB(NOW(), INTERVAL 20 DAY),  1),
(1, 300.00,  'Online', 'Money', DATE_SUB(NOW(), INTERVAL 45 DAY),  1),
(2, 800.00,  'Online', 'Money', DATE_SUB(NOW(), INTERVAL 80 DAY),  1),
(3, 150.00,  'Shelter','Blankets', DATE_SUB(NOW(), INTERVAL 110 DAY), 1);

-- Volunteers and activity
INSERT INTO volunteers (first_name, last_name, email, phone, join_date) VALUES
('Demo', 'Volunteer', 'volunteer@example.com', '000 444 1111', DATE_SUB(CURDATE(), INTERVAL 60 DAY));

INSERT INTO volunteer_tasks (volunteer_id, task, status) VALUES
(1, 'Walk the dogs in Dog Runs A and B', 'Assigned'),
(1, 'Clean the cattery',                 'Completed');

INSERT INTO community_engagement (volunteer_id, volunteer_Fname, volunteer_Lname, contact_number, email, date, activities_name, activity_description, start_time, end_time, lapse_time, supervisor_name, supervisor_surname, status) VALUES
(1, 'Demo', 'Volunteer', '000 444 1111', 'volunteer@example.com', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Dog walking',     'Walked Rocky and Snowy',     '09:00:00', '11:00:00', '02:00:00', 'Demo', 'Admin', 'Completed'),
(1, 'Demo', 'Volunteer', '000 444 1111', 'volunteer@example.com', DATE_SUB(CURDATE(), INTERVAL 4 DAY),  'Feeding',         'Morning feeding round',      '08:00:00', '09:30:00', '01:30:00', 'Demo', 'Admin', 'Completed'),
(1, 'Demo', 'Volunteer', '000 444 1111', 'volunteer@example.com', DATE_ADD(CURDATE(), INTERVAL 3 DAY),  'Adoption fair',   'Help at the adoption stand', '09:00:00', '13:00:00', '04:00:00', 'Demo', 'Admin', 'Pending');

INSERT INTO events (event_name, event_date, event_time, location, description, max_volunteers) VALUES
('Monthly Adoption Fair', DATE_ADD(CURDATE(), INTERVAL 3 DAY),  '09:00:00', 'Shelter grounds', 'Meet our animals and adopt a new friend.', 10),
('Township Outreach',     DATE_ADD(CURDATE(), INTERVAL 12 DAY), '08:30:00', 'Community hall',  'Food and basic care for pets in the community.', 8);

INSERT INTO volunteer_events (event_name, event_date, description) VALUES
('Monthly Adoption Fair', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Volunteers needed at the adoption stand');

-- Public contact and newsletter
INSERT INTO contact_submissions (first_name, last_name, email, subject, message, submission_date) VALUES
('Sample', 'Visitor', 'visitor@example.com', 'Volunteering', 'Hi, how do I sign up to volunteer on weekends?', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO spca_subscribers (email) VALUES ('subscriber1@example.com'), ('subscriber2@example.com');
