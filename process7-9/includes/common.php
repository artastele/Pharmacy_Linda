<?php
function sanitize_input(string $value): string
{
    return trim($value);
}

function is_future_or_today_date(string $date): bool
{
    $inputDate = strtotime($date);
    $today = strtotime(date("Y-m-d"));
    return $inputDate !== false && $inputDate >= $today;
}

function show_message(): void
{
    if (!empty($_GET["message"])) {
        echo "<div class=\"message\">" . htmlspecialchars($_GET["message"]) . "</div>";
    }
}

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function is_valid_role(string $role): bool
{
    return in_array($role, ["hr_personnel", "intern"], true);
}

function role_label(string $role): string
{
    if ($role === "hr_personnel") {
        return "HR Personnel";
    }
    if ($role === "intern") {
        return "Intern";
    }
    return "Unknown";
}

function require_login(): void
{
    ensure_session_started();
    $userId = (int) ($_SESSION["user_id"] ?? 0);
    $role = sanitize_input($_SESSION["role"] ?? "");
    if ($userId <= 0 || !is_valid_role($role)) {
        header("Location: login.php?message=Please log in first.");
        exit;
    }
}

function require_role(string $requiredRole): void
{
    require_login();
    $role = sanitize_input($_SESSION["role"] ?? "");
    if ($role !== $requiredRole) {
        header("Location: index.php?message=You are not allowed to access that page.");
        exit;
    }
}

function get_current_user_full_name(): string
{
    ensure_session_started();
    return sanitize_input($_SESSION["full_name"] ?? "");
}

function get_current_user_role(): string
{
    ensure_session_started();
    return sanitize_input($_SESSION["role"] ?? "");
}

function is_hr_user(): bool
{
    return get_current_user_role() === "hr_personnel";
}

function is_intern_user(): bool
{
    return get_current_user_role() === "intern";
}

function redirect_to_role_home(?string $message = null): void
{
    $role = get_current_user_role();
    $target = "login.php";

    if ($role === "hr_personnel") {
        $target = "tasks_list.php";
    } elseif ($role === "intern") {
        $target = "inventory_list.php";
    }

    if ($message !== null && $message !== "") {
        $separator = strpos($target, "?") === false ? "?" : "&";
        $target .= $separator . "message=" . urlencode($message);
    }

    header("Location: " . $target);
    exit;
}

function get_orientation_checklist_items(): array
{
    return [
        "company_overview" => "Company overview presented",
        "workplace_rules" => "Workplace rules and policies explained",
        "safety_briefing" => "Safety and pharmacy handling briefing completed",
        "team_introduction" => "Intern introduced to staff and work area",
        "system_walkthrough" => "System and workflow walkthrough completed",
        "qa_session" => "Questions and answers completed",
    ];
}
?>
