<?php
/**
 * Global Helper Functions & Utilities
 */

require_once __DIR__ . '/../config/config.php';

function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return BASE_URL . ($path ? '/' . $path : '');
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
    header("Location: " . url($path));
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function is_post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function validate_csrf(): bool {
    if (is_post()) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            flash('danger', 'Security validation token expired. Please try again.');
            return false;
        }
    }
    return true;
}

function flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

function format_currency(float|int|string $amount): string {
    $num = (float)$amount;
    return CURRENCY_SYMBOL . ' ' . number_format($num, 2);
}

function format_date(?string $date, string $format = 'd M Y'): string {
    if (empty($date) || $date === '0000-00-00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

function format_time(?string $time, string $format = 'h:i A'): string {
    if (empty($time) || $time === '00:00:00') {
        return '--:--';
    }
    return date($format, strtotime($time));
}

function status_badge(string $status): string {
    $statusLower = strtolower(trim($status));
    $classes = [
        'active' => 'badge-success',
        'present' => 'badge-success',
        'approved' => 'badge-success',
        'paid' => 'badge-success',
        'completed' => 'badge-success',
        'full_time' => 'badge-info',
        'probation' => 'badge-warning',
        'intern' => 'badge-secondary',
        'contract' => 'badge-purple',
        'pending' => 'badge-warning',
        'submitted' => 'badge-warning',
        'in_progress' => 'badge-info',
        'reviewed' => 'badge-info',
        'draft' => 'badge-secondary',
        'late' => 'badge-orange',
        'half_day' => 'badge-warning',
        'on_leave' => 'badge-purple',
        'leave' => 'badge-purple',
        'absent' => 'badge-danger',
        'rejected' => 'badge-danger',
        'cancelled' => 'badge-secondary',
        'resigned' => 'badge-orange',
        'terminated' => 'badge-danger',
        'mandatory' => 'badge-primary',
        'optional' => 'badge-secondary',
        'high' => 'badge-danger',
        'urgent' => 'badge-danger',
        'normal' => 'badge-info',
        'low' => 'badge-secondary'
    ];

    $class = $classes[$statusLower] ?? 'badge-secondary';
    $label = ucwords(str_replace('_', ' ', $statusLower));
    return "<span class=\"badge {$class}\">{$label}</span>";
}

function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Converts numbers into Indian Currency Words (e.g. 57900 -> Fifty Seven Thousand Nine Hundred Rupees Only)
 */
function number_to_words_inr(float|int $number): string {
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        '0' => '', '1' => 'One', '2' => 'Two',
        '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six',
        '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
        '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve',
        '13' => 'Thirteen', '14' => 'Fourteen',
        '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
        '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty',
        '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
        '60' => 'Sixty', '70' => 'Seventy',
        '80' => 'Eighty', '90' => 'Ninety'
    );
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str[] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred
                : $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($point) ? " and " . $words[floor($point / 10) * 10] . " " . $words[$point = $point % 10] . " Paise" : '';
    return trim($result) . " Rupees" . $points . " Only";
}

function time_ago(?string $datetime): string {
    if (empty($datetime)) return 'Just now';
    $time = strtotime($datetime);
    if (!$time) return 'Just now';
    $diff = time() - $time;
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . 'm ago';
    } elseif ($diff < 86400) {
        $hrs = floor($diff / 3600);
        return $hrs . 'h ago';
    } elseif ($diff < 172800) {
        return 'Yesterday';
    } else {
        $days = floor($diff / 86400);
        if ($days < 30) {
            return $days . 'd ago';
        }
        return date('M d', $time);
    }
}

function is_ajax(): bool {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

/**
 * Calculate pagination metadata
 */
function paginate(int $totalItems, int $currentPage = 1, int $perPage = 15): array {
    $perPage = max(1, min(100, $perPage));
    $totalPages = max(1, (int)ceil($totalItems / $perPage));
    $currentPage = max(1, min($totalPages, $currentPage));
    $offset = ($currentPage - 1) * $perPage;

    $startItem = $totalItems > 0 ? $offset + 1 : 0;
    $endItem = min($totalItems, $offset + $perPage);

    return [
        'total_items'  => $totalItems,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'limit'        => $perPage,
        'start_item'   => $startItem,
        'end_item'     => $endItem,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
        'prev_page'    => $currentPage - 1,
        'next_page'    => $currentPage + 1,
    ];
}

/**
 * Build URL with updated query parameter (preserving other GET parameters)
 */
function pagination_url(int $page, ?int $perPage = null): string {
    $params = $_GET;
    $params['page'] = $page;
    if ($perPage !== null) {
        $params['per_page'] = $perPage;
    }
    $route = $params['route'] ?? '';
    unset($params['route']);
    $query = http_build_query($params);
    return url($route) . ($query ? '?' . $query : '');
}

/**
 * Render standard accessible pagination UI component
 */
function render_pagination(array $pagination): string {
    if ($pagination['total_pages'] <= 1 && $pagination['total_items'] <= $pagination['per_page']) {
        return '';
    }

    $cp = $pagination['current_page'];
    $tp = $pagination['total_pages'];

    $html = '<div class="pagination-container">';
    $html .= '<div class="pagination-info">';
    $html .= sprintf('Showing <strong>%d</strong> to <strong>%d</strong> of <strong>%d</strong> entries',
        $pagination['start_item'],
        $pagination['end_item'],
        $pagination['total_items']
    );
    $html .= '</div>';

    $html .= '<nav class="pagination-nav" aria-label="Table pagination">';
    
    // Previous button
    if ($pagination['has_prev']) {
        $html .= '<a href="' . e(pagination_url($pagination['prev_page'])) . '" class="page-link" aria-label="Previous page"><i class="fa-solid fa-chevron-left" style="font-size: 10px;"></i> Prev</a>';
    } else {
        $html .= '<span class="page-link disabled"><i class="fa-solid fa-chevron-left" style="font-size: 10px;"></i> Prev</span>';
    }

    // Page window calculation
    $window = 2;
    $pages = [];
    for ($i = 1; $i <= $tp; $i++) {
        if ($i === 1 || $i === $tp || ($i >= $cp - $window && $i <= $cp + $window)) {
            $pages[] = $i;
        }
    }

    $prev = 0;
    foreach ($pages as $p) {
        if ($prev > 0 && $p - $prev > 1) {
            $html .= '<span class="page-link ellipsis">&hellip;</span>';
        }
        if ($p === $cp) {
            $html .= '<span class="page-link active" aria-current="page">' . $p . '</span>';
        } else {
            $html .= '<a href="' . e(pagination_url($p)) . '" class="page-link">' . $p . '</a>';
        }
        $prev = $p;
    }

    // Next button
    if ($pagination['has_next']) {
        $html .= '<a href="' . e(pagination_url($pagination['next_page'])) . '" class="page-link" aria-label="Next page">Next <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i></a>';
    } else {
        $html .= '<span class="page-link disabled">Next <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i></span>';
    }

    $html .= '</nav>';
    $html .= '</div>';

    return $html;
}

/**
 * Checks if a given date is a weekend / week-off according to company policy:
 * - All Sundays are Off (Week Off)
 * - 1st, 3rd, and 5th Saturdays are Off (Week Off)
 * - 2nd and 4th Saturdays are Working Days
 * - Monday through Friday are Working Days
 *
 * @param string|int $date Date string (Y-m-d) or Unix timestamp
 * @return bool True if week off, false if working day
 */
function is_weekend(string|int $date): bool {
    $ts = is_numeric($date) ? (int)$date : strtotime($date);
    $dayOfWeek = (int)date('w', $ts); // 0 = Sun, 6 = Sat

    if ($dayOfWeek === 0) {
        return true; // Sunday is always off
    }

    if ($dayOfWeek === 6) {
        $dayOfMonth = (int)date('j', $ts);
        $saturdayNum = (int)ceil($dayOfMonth / 7);
        return in_array($saturdayNum, [1, 3, 5], true);
    }

    return false;
}

/**
 * Alias for is_weekend()
 */
function is_week_off(string|int $date): bool {
    return is_weekend($date);
}

/**
 * Checks if a given date is a gazetted public / mandatory holiday.
 * Results are cached in memory per year for maximum performance.
 *
 * @param string|int $date Date string (Y-m-d) or Unix timestamp
 * @return bool True if gazetted holiday, false otherwise
 */
function is_holiday(string|int $date): bool {
    $ts = is_numeric($date) ? (int)$date : strtotime($date);
    $dateStr = date('Y-m-d', $ts);
    $year = (int)date('Y', $ts);

    static $holidayCache = [];
    if (!isset($holidayCache[$year])) {
        $holidayCache[$year] = [];
        try {
            if (class_exists('Database')) {
                $rows = Database::fetchAll("SELECT holiday_date FROM holidays WHERE year = ?", [$year]);
                foreach ($rows as $r) {
                    $holidayCache[$year][$r['holiday_date']] = true;
                }
            }
        } catch (Throwable $e) {
            // Silently fallback if DB is not initialized
        }
    }

    return isset($holidayCache[$year][$dateStr]);
}

/**
 * Checks if a given date is a working day (neither a week-off nor a holiday).
 *
 * @param string|int $date Date string (Y-m-d) or Unix timestamp
 * @return bool True if working day, false if week-off or holiday
 */
function is_working_day(string|int $date): bool {
    return !is_weekend($date) && !is_holiday($date);
}

