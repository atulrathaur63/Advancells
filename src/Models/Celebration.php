<?php
/**
 * Celebration Model
 * Advancells HRMS - Birthday, Anniversary & New Joinee Celebrations
 */

require_once __DIR__ . '/../Database.php';

class Celebration {
    /**
     * Get ordinal suffix for numbers (1st, 2nd, 3rd, 4th...)
     */
    public static function ordinal(int $number): string {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }
        return $number . $ends[$number % 10];
    }

    /**
     * Get all celebrations categorized and sorted
     * Returns:
     * [
     *   'all'           => array,
     *   'birthdays'     => array,
     *   'anniversaries' => array,
     *   'new_joinees'   => array,
     *   'today'         => array,
     *   'summary'       => array
     * ]
     */
    public static function getAllCelebrations(?int $currentUserId = null): array {
        $today = date('Y-m-d');
        $currentYear = (int)date('Y');

        // Fetch all active employees with department and designation
        $sql = "SELECT e.id, e.user_id, e.emp_code, e.first_name, e.last_name, e.email,
                       e.gender, e.dob, e.date_of_joining, e.department_id, e.designation_id,
                       u.avatar, u.role as user_role,
                       d.name as department_name, des.title as designation_title
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                WHERE e.status = 'active'";
        $employees = Database::fetchAll($sql);

        // Fetch wishes counts mapped by receiver_id and celebration_type
        $wishesRaw = Database::fetchAll("SELECT receiver_id, celebration_type, count(*) as count 
                                         FROM celebration_wishes 
                                         GROUP BY receiver_id, celebration_type");
        $wishesMap = [];
        foreach ($wishesRaw as $w) {
            $key = $w['receiver_id'] . '_' . $w['celebration_type'];
            $wishesMap[$key] = (int)$w['count'];
        }

        // Fetch which celebrants the current user has already wished
        $userWishedMap = [];
        if ($currentUserId) {
            $userWishes = Database::fetchAll("SELECT receiver_id, celebration_type 
                                              FROM celebration_wishes 
                                              WHERE sender_id = ?", [$currentUserId]);
            foreach ($userWishes as $uw) {
                $userWishedMap[$uw['receiver_id'] . '_' . $uw['celebration_type']] = true;
            }
        }

        $birthdays = [];
        $anniversaries = [];
        $newJoinees = [];
        $todayEvents = [];

        foreach ($employees as $emp) {
            $empId = (int)$emp['id'];
            $fullName = trim($emp['first_name'] . ' ' . $emp['last_name']);
            $initials = strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1));

            // Base employee payload
            $baseInfo = [
                'employee_id'        => $empId,
                'user_id'            => (int)$emp['user_id'],
                'emp_code'           => $emp['emp_code'],
                'name'               => $fullName,
                'first_name'         => $emp['first_name'],
                'initials'           => $initials,
                'email'              => $emp['email'],
                'avatar'             => $emp['avatar'],
                'department_name'    => $emp['department_name'] ?? 'General Biotech',
                'designation_title'  => $emp['designation_title'] ?? 'Staff',
            ];

            // 1. Process Birthday
            if (!empty($emp['dob'])) {
                $dobMonthDay = substr($emp['dob'], 5, 5); // 'MM-DD'
                $bdayThisYear = $currentYear . '-' . $dobMonthDay;

                if ($bdayThisYear < $today) {
                    $nextBday = ($currentYear + 1) . '-' . $dobMonthDay;
                } else {
                    $nextBday = $bdayThisYear;
                }

                $diffDays = (int)round((strtotime($nextBday) - strtotime($today)) / 86400);

                // Show birthdays happening within the next 30 days
                if ($diffDays >= 0 && $diffDays <= 30) {
                    $birthYear = (int)date('Y', strtotime($emp['dob']));
                    $turningAge = (int)date('Y', strtotime($nextBday)) - $birthYear;
                    $isToday = ($diffDays === 0);
                    $wishesCount = $wishesMap[$empId . '_birthday'] ?? 0;
                    $hasWished = isset($userWishedMap[$empId . '_birthday']);

                    $bdayEvent = array_merge($baseInfo, [
                        'type'               => 'birthday',
                        'type_label'         => 'Birthday',
                        'type_icon'          => 'fa-cake-candles',
                        'type_color'         => '#ec4899', // rose/pink
                        'event_date'         => $nextBday,
                        'formatted_date'     => date('d M', strtotime($nextBday)),
                        'raw_date'           => $emp['dob'],
                        'days_until'         => $diffDays,
                        'is_today'           => $isToday,
                        'is_tomorrow'        => ($diffDays === 1),
                        'milestone_text'     => "Turning {$turningAge} years",
                        'urgency_label'      => $isToday ? 'Today 🎉' : ($diffDays === 1 ? 'Tomorrow' : "In {$diffDays} days"),
                        'wishes_count'       => $wishesCount,
                        'has_wished'         => $hasWished,
                    ]);

                    $birthdays[] = $bdayEvent;
                    if ($isToday) {
                        $todayEvents[] = $bdayEvent;
                    }
                }
            }

            // 2. Process Work Anniversary
            if (!empty($emp['date_of_joining'])) {
                $doj = $emp['date_of_joining'];
                $joiningYear = (int)date('Y', strtotime($doj));
                $dojMonthDay = substr($doj, 5, 5); // 'MM-DD'
                $annivThisYear = $currentYear . '-' . $dojMonthDay;

                if ($annivThisYear < $today) {
                    $nextAnniv = ($currentYear + 1) . '-' . $dojMonthDay;
                } else {
                    $nextAnniv = $annivThisYear;
                }

                $diffDays = (int)round((strtotime($nextAnniv) - strtotime($today)) / 86400);
                $milestoneYears = (int)date('Y', strtotime($nextAnniv)) - $joiningYear;

                // Milestone must be at least 1 year completed
                if ($milestoneYears >= 1 && $diffDays >= 0 && $diffDays <= 30) {
                    $isToday = ($diffDays === 0);
                    $wishesCount = $wishesMap[$empId . '_anniversary'] ?? 0;
                    $hasWished = isset($userWishedMap[$empId . '_anniversary']);
                    $ordinalMilestone = self::ordinal($milestoneYears);

                    $annivEvent = array_merge($baseInfo, [
                        'type'               => 'anniversary',
                        'type_label'         => 'Work Anniversary',
                        'type_icon'          => 'fa-award',
                        'type_color'         => '#f59e0b', // amber / gold
                        'event_date'         => $nextAnniv,
                        'formatted_date'     => date('d M', strtotime($nextAnniv)),
                        'raw_date'           => $doj,
                        'days_until'         => $diffDays,
                        'is_today'           => $isToday,
                        'is_tomorrow'        => ($diffDays === 1),
                        'milestone_years'    => $milestoneYears,
                        'milestone_text'     => "{$ordinalMilestone} Work Anniversary",
                        'urgency_label'      => $isToday ? 'Today 🎉' : ($diffDays === 1 ? 'Tomorrow' : "In {$diffDays} days"),
                        'wishes_count'       => $wishesCount,
                        'has_wished'         => $hasWished,
                    ]);

                    $anniversaries[] = $annivEvent;
                    if ($isToday) {
                        $todayEvents[] = $annivEvent;
                    }
                }
            }

            // 3. Process New Joinees (joined within the past 45 days)
            if (!empty($emp['date_of_joining'])) {
                $doj = $emp['date_of_joining'];
                $daysSinceJoined = (int)round((strtotime($today) - strtotime($doj)) / 86400);

                if ($daysSinceJoined >= 0 && $daysSinceJoined <= 45) {
                    $isToday = ($daysSinceJoined === 0);
                    $wishesCount = $wishesMap[$empId . '_welcome'] ?? 0;
                    $hasWished = isset($userWishedMap[$empId . '_welcome']);

                    $welcomeEvent = array_merge($baseInfo, [
                        'type'               => 'welcome',
                        'type_label'         => 'New Joinee',
                        'type_icon'          => 'fa-hand-sparkles',
                        'type_color'         => '#06b6d4', // cyan/teal
                        'event_date'         => $doj,
                        'formatted_date'     => date('d M Y', strtotime($doj)),
                        'raw_date'           => $doj,
                        'days_until'         => -$daysSinceJoined, // negative represents past days
                        'days_joined'        => $daysSinceJoined,
                        'is_today'           => $isToday,
                        'is_tomorrow'        => false,
                        'milestone_text'     => 'Welcome Aboard!',
                        'urgency_label'      => $isToday ? 'Joined Today 🎉' : ($daysSinceJoined === 1 ? 'Joined Yesterday' : "Joined {$daysSinceJoined}d ago"),
                        'wishes_count'       => $wishesCount,
                        'has_wished'         => $hasWished,
                    ]);

                    $newJoinees[] = $welcomeEvent;
                    if ($isToday) {
                        $todayEvents[] = $welcomeEvent;
                    }
                }
            }
        }

        // Sort Birthdays by days_until ASC
        usort($birthdays, fn($a, $b) => $a['days_until'] <=> $b['days_until']);

        // Sort Anniversaries by days_until ASC
        usort($anniversaries, fn($a, $b) => $a['days_until'] <=> $b['days_until']);

        // Sort New Joinees by days_joined ASC (most recent first)
        usort($newJoinees, fn($a, $b) => $a['days_joined'] <=> $b['days_joined']);

        // Aggregate All Events
        // Order: Today's events first, then upcoming birthdays/anniversaries (closest first), then new joinees
        $all = [];
        foreach ($todayEvents as $tev) {
            $all[] = $tev;
        }

        // Add upcoming birthdays & anniversaries not already in today
        $upcoming = array_merge(
            array_filter($birthdays, fn($b) => !$b['is_today']),
            array_filter($anniversaries, fn($a) => !$a['is_today'])
        );
        usort($upcoming, fn($a, $b) => $a['days_until'] <=> $b['days_until']);
        foreach ($upcoming as $u) {
            $all[] = $u;
        }

        // Add recent new joinees not joined today
        foreach (array_filter($newJoinees, fn($n) => !$n['is_today']) as $nj) {
            $all[] = $nj;
        }

        return [
            'all'           => $all,
            'birthdays'     => $birthdays,
            'anniversaries' => $anniversaries,
            'new_joinees'   => $newJoinees,
            'today'         => $todayEvents,
            'summary'       => [
                'total'         => count($all),
                'today_count'   => count($todayEvents),
                'birthdays'     => count($birthdays),
                'anniversaries' => count($anniversaries),
                'new_joinees'   => count($newJoinees),
            ]
        ];
    }

    /**
     * Check if a specific employee is celebrating today (Birthday, Anniversary, or First Day)
     */
    public static function getCelebrantStatus(int $employeeId): ?array {
        if ($employeeId <= 0) return null;

        $celebrations = self::getAllCelebrations();
        foreach ($celebrations['today'] as $event) {
            if ($event['employee_id'] === $employeeId) {
                return $event;
            }
        }
        return null;
    }

    /**
     * Send a celebration greeting to a colleague
     */
    public static function sendWish(int $senderUserId, int $receiverEmpId, string $type, string $message): bool {
        $validTypes = ['birthday', 'anniversary', 'welcome'];
        if (!in_array($type, $validTypes, true)) {
            return false;
        }

        $message = trim(strip_tags($message));
        if (empty($message)) {
            return false;
        }

        // Truncate to 255 chars max
        if (mb_strlen($message) > 255) {
            $message = mb_substr($message, 0, 255);
        }

        try {
            $id = Database::insert('celebration_wishes', [
                'sender_id'        => $senderUserId,
                'receiver_id'      => $receiverEmpId,
                'celebration_type' => $type,
                'message'          => $message,
                'created_at'       => date('Y-m-d H:i:s')
            ]);
            return (bool)$id;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all wishes for an employee
     */
    public static function getWishes(int $receiverEmpId, ?string $type = null): array {
        $sql = "SELECT w.*, u.name as sender_name, u.role as sender_role, u.avatar as sender_avatar,
                       des.title as sender_designation, d.name as sender_department
                FROM celebration_wishes w
                JOIN users u ON w.sender_id = u.id
                LEFT JOIN employees e ON u.id = e.user_id
                LEFT JOIN designations des ON e.designation_id = des.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE w.receiver_id = ?";
        $params = [$receiverEmpId];

        if ($type) {
            $sql .= " AND w.celebration_type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY w.created_at DESC";
        return Database::fetchAll($sql, $params);
    }
}
