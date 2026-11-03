<?php
// Adoption workflow rules. Pure functions (no database), so they are unit tested.
//
//   Pending ──► Home visit ──► Approved ──► Adopted
//      │             │             │
//      └─────────────┴─────────────┴──► Rejected

const ADOPTION_FLOW = [
    'Pending'    => ['Home visit', 'Rejected'],
    'Home visit' => ['Approved', 'Rejected'],
    'Approved'   => ['Adopted', 'Rejected'],
    'Adopted'    => [],
    'Rejected'   => [],
];

/** Can an application move from one status to another? */
function adoption_can_move(string $from, string $to): bool
{
    return in_array($to, ADOPTION_FLOW[$from] ?? [], true);
}

/** The animal's status that matches an application's status. */
function animal_status_for(string $adoptionStatus): string
{
    return match ($adoptionStatus) {
        'Adopted'  => 'Adopted',
        'Rejected' => 'Available',
        default    => 'Pending',      // reserved while the application is open
    };
}

/** Plain-language explanation shown to the applicant on the tracking page. */
function adoption_status_help(string $status): string
{
    return match ($status) {
        'Pending'    => 'We have your application and will review it within 3 to 5 working days.',
        'Home visit' => 'Your application looks good. We will visit your home on the date we emailed you.',
        'Approved'   => 'You are approved! Bring your ID document when you collect your new friend.',
        'Adopted'    => 'Adoption complete. Thank you for giving an animal a home.',
        'Rejected'   => 'Unfortunately we could not approve this application. You are welcome to apply for another animal.',
        default      => '',
    };
}

/**
 * The email an applicant gets when their application moves to $status.
 * Returns [subject, html body]. Values are escaped here.
 */
function adoption_email(string $status, string $name, string $animal, ?string $visitAt = null, string $note = ''): array
{
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $hi = '<p>Dear ' . $h($name) . ',</p>';
    $bye = '<p>Kind regards,<br>SPCA Guardian Pro team</p>';
    $noteHtml = $note !== '' ? '<p><strong>Note from our team:</strong> ' . $h($note) . '</p>' : '';

    switch ($status) {
        case 'Home visit':
            $when = $visitAt ? date('l j F Y \a\t H:i', strtotime($visitAt)) : 'a date we will confirm by phone';
            return ["Home visit for your adoption of {$animal}",
                $hi . '<p>Thank you for applying to adopt <strong>' . $h($animal) . '</strong>. The next step is a short home visit.</p>'
                . '<p><strong>When:</strong> ' . $h($when) . '</p>' . $noteHtml
                . '<p>If that time does not suit you, please reply to this email.</p>' . $bye];
        case 'Approved':
            return ["You're approved to adopt {$animal}!",
                $hi . '<p>Great news: your application to adopt <strong>' . $h($animal) . '</strong> has been approved.</p>'
                . '<p>Please visit us to collect them and <strong>bring your ID document</strong>, as we check it at handover.</p>' . $noteHtml . $bye];
        case 'Adopted':
            return ["Welcome home, {$animal}",
                $hi . '<p>The adoption of <strong>' . $h($animal) . '</strong> is complete. Thank you for giving them a home.</p>'
                . '<p>We will be in touch to book a follow-up check in a few weeks.</p>' . $noteHtml . $bye];
        case 'Rejected':
            return ["About your application to adopt {$animal}",
                $hi . '<p>Thank you for your interest in adopting <strong>' . $h($animal) . '</strong>. Unfortunately we are unable to approve your application this time.</p>'
                . $noteHtml . '<p>You are welcome to apply for another animal.</p>' . $bye];
        default:
            return ["Update on your adoption application", $hi . '<p>Your application status is now: ' . $h($status) . '.</p>' . $bye];
    }
}
