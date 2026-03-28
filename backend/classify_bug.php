<?php

/*! Simple AI-based bug classification function
 * This function takes a bug title and description as input and classifies the bug into
 * categories such as Crash, Performance, UI, Security, or General based on keyword analysis.
 * Expand this with more keywords and categories as needed.
 */
function classifyBug($title, $description) {

    // Combine title and description for keyword analysis
    $text = strtolower($title . " " . $description);

    // Check for keywords related to crashes
    // Example Title: App crash
    // Example Description: The app crashes when clicking login
    if (strpos($text, "crash") !== false || strpos($text, "error") !== false
     || strpos($text, "exception") !== false || strpos($text, "fail") !== false) {
        return "Crash";
    }

    // Check for keywords related to performance issues
    // Example Description: The page is very slow when loading dashboard
    if (strpos($text, "slow") !== false || strpos($text, "lag") !== false
     || strpos($text, "performance") !== false || strpos($text, "delay") !== false) {
        return "Performance";
    }

    // Check for keywords related to UI issues
    // Example Description: Button is not aligned properly
    if (strpos($text, "button") !== false || strpos($text, "ui") !== false
     || strpos($text, "layout") !== false || strpos($text, "design") !== false) {
        return "UI";
    }

    // Check for keywords related to security issues
    // Example Description: Found a vulnerability that allows SQL injection
    if (strpos($text, "hack") !== false || strpos($text, "security") !== false
     || strpos($text, "vulnerability") !== false || strpos($text, "sql injection") !== false) {
        return "Security";
    }
    
    // If no specific keywords are found, classify as General
    return "General";
}

/*! Simple AI-based bug priority assignment function
 * This function takes a bug description as input and assigns a priority level (High, Medium, Low)
 * based on the presence of certain keywords.
 */
function getPriority($description) {

    // Convert description to lowercase for keyword analysis
    $text = strtolower($description);

    // Check for keywords that indicate high priority
    if (strpos($text, "crash") !== false) return "High";
    if (strpos($text, "slow") !== false) return "Medium";
    return "Low";
}

?>