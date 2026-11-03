-- SPCA Guardian Pro: volunteer dashboard (November 2026).
-- Run once in phpMyAdmin (SQL tab or Import) after 001.

-- Logged hours belong to the volunteer's account and need an Administrator's approval.
ALTER TABLE `community_engagement`
  ADD COLUMN `user_id` INT NULL DEFAULT NULL,
  ADD COLUMN `status` ENUM('Pending','Approved') NOT NULL DEFAULT 'Pending',
  ADD KEY `idx_ce_user` (`user_id`);

-- A volunteer can sign up for each event once (volunteer_id = users.idnumber).
ALTER TABLE `event_signups`
  ADD UNIQUE KEY `uq_event_volunteer` (`event_id`, `volunteer_id`);
