<?php
// Helpers for long lists: pagination and CSV export. Pure functions, unit tested.

/** Works out the page window. Returns [page, pages, offset]. */
function paginate(int $total, int $page, int $perPage = 20): array
{
    $perPage = max(1, $perPage);
    $pages   = max(1, (int)ceil($total / $perPage));
    $page    = min(max(1, $page), $pages);
    return [$page, $pages, ($page - 1) * $perPage];
}

/** One CSV line. Cells starting with = + - @ are prefixed with ' so Excel won't run them as formulas. */
function csv_line(array $cells): string
{
    $out = [];
    foreach ($cells as $c) {
        $c = (string)($c ?? '');
        if ($c !== '' && strpbrk($c[0], "=+-@\t\r") !== false && !is_numeric($c)) $c = "'" . $c;
        $out[] = '"' . str_replace('"', '""', $c) . '"';
    }
    return implode(',', $out) . "\r\n";
}

/** Sends rows to the browser as a CSV download (opens in Excel) and stops. */
function send_csv(string $filename, array $headers, iterable $rows): never
{
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename) . '"');
    echo "\xEF\xBB\xBF";               // UTF-8 marker so Excel shows accents correctly
    echo csv_line($headers);
    foreach ($rows as $r) echo csv_line(array_values($r));
    exit;
}

/** Builds the pager links, keeping the current search/filter values. */
function pager_html(int $page, int $pages, array $keep = []): string
{
    if ($pages <= 1) return '';
    $link = function (int $p, string $label, bool $current = false) use ($keep) {
        $qs = http_build_query(array_merge($keep, ['page' => $p]));
        return $current ? '<span class="pager-current" aria-current="page">' . $label . '</span>'
                        : '<a href="?' . htmlspecialchars($qs) . '">' . $label . '</a>';
    };
    $html = '<nav class="pager" aria-label="Pages">';
    if ($page > 1) $html .= $link($page - 1, '&larr; Previous');
    for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++) $html .= $link($p, (string)$p, $p === $page);
    if ($page < $pages) $html .= $link($page + 1, 'Next &rarr;');
    return $html . '</nav>';
}
