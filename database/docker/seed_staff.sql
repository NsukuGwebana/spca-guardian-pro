-- Local Docker only: one sign-in per role so you can try the system straight away.
-- Every account's password is Demo12345. Never import this on a public server.
INSERT INTO users (idnumber, surname, firstname, username, userpassword, userrole, phonenumber, email) VALUES
  (1, 'Admin',     'Demo', 'admin',     '$2y$10$DSwSQ3Rfb2KM4vkH0Zzj9.JHAXk1OPtyEIub1TZB8USORxqLx9CU.', 'Administrator', '000 000 0000', 'admin@spca.local'),
  (2, 'Vet',       'Demo', 'vet',       '$2y$10$DSwSQ3Rfb2KM4vkH0Zzj9.JHAXk1OPtyEIub1TZB8USORxqLx9CU.', 'Vet',           '000 000 0000', 'vet@spca.local'),
  (3, 'Volunteer', 'Demo', 'volunteer', '$2y$10$DSwSQ3Rfb2KM4vkH0Zzj9.JHAXk1OPtyEIub1TZB8USORxqLx9CU.', 'Volunteer',     '000 000 0000', 'volunteer@spca.local');
