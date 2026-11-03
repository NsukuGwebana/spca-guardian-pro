-- OPTIONAL: only for the made-up sample database used by the live demo.
-- Run after 001. Gives the sample adoptions realistic dates so the
-- management dashboard has something meaningful to show. Skip this on real data.
UPDATE `adoptions` SET status_updated_at = created_at + INTERVAL (8 + (id % 4) * 4) DAY
 WHERE status IN ('Adopted','Rejected') AND status_updated_at IS NULL;
UPDATE `adoptions` SET status_updated_at = created_at + INTERVAL 7 DAY
 WHERE status = 'Approved' AND status_updated_at IS NULL;
UPDATE `adoptions` SET status = 'Home visit', status_updated_at = created_at + INTERVAL 3 DAY,
       home_visit_at = created_at + INTERVAL 6 DAY
 WHERE id = 17 AND status = 'Pending';

