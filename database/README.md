# Database

`sysmasters_schema.sql` creates the full database structure (23 tables, no data).

It was reconstructed from the application code: every table and column used by the PHP queries is defined, with types matching how the code binds values. All of the application's SQL statements were checked against it.

## Import

1. In phpMyAdmin, create a database named `sysmasters` (collation `utf8mb4_general_ci`).
2. Select it, open **Import**, choose `sysmasters_schema.sql` and click **Import**.
3. Register a first staff account through the Create Account form (`Newlogin.html`).

## Notes

- `users.userpassword` is `VARCHAR(255)` because passwords are stored as `password_hash()` output. A shorter column truncates the hash and breaks login.
- Animals, kennels and other records use soft delete (`is_deleted`), so archived items keep their history.
