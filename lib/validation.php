<?php
// Input checks shared by the public forms. Pure functions, unit tested.

/** South African ID number: 13 digits, valid birth date and Luhn check digit. */
function valid_sa_id(string $id): bool
{
    if (!preg_match('/^\d{13}$/', $id)) return false;
    [$yy, $mm, $dd] = [substr($id, 0, 2), (int)substr($id, 2, 2), (int)substr($id, 4, 2)];
    if (!checkdate($mm, $dd, 2000 + (int)$yy) && !checkdate($mm, $dd, 1900 + (int)$yy)) return false;

    $sum = 0;
    for ($i = 0; $i < 13; $i++) {
        $d = (int)$id[$i];
        if ($i % 2 === 1) { $d *= 2; if ($d > 9) $d -= 9; }
        $sum += $d;
    }
    return $sum % 10 === 0;
}

/** Accepts a valid SA ID or a passport number (6 to 12 letters/digits). */
function valid_identity_number(string $id): bool
{
    $id = strtoupper(trim($id));
    if (preg_match('/^\d{13}$/', $id)) return valid_sa_id($id);
    return (bool)preg_match('/^[A-Z0-9]{6,12}$/', $id) && preg_match('/[A-Z]/', $id);
}

/** South African phone number such as 082 123 4567 or +27 82 123 4567. */
function valid_phone(string $phone): bool
{
    $digits = preg_replace('/[\s\-()]/', '', $phone);
    return (bool)preg_match('/^(\+27|0)[1-9]\d{8}$/', $digits);
}

function valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** At least 8 characters with a letter and a number. Returns a list of problems. */
function password_problems(string $pw): array
{
    $p = [];
    if (strlen($pw) < 8) $p[] = 'Use at least 8 characters.';
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) $p[] = 'Use at least one letter and one number.';
    return $p;
}
