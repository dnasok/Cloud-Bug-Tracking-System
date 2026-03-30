<?php
/**
 * File: backend/classify_bug.php
 * Purpose: Heuristic keyword-based bug category and priority scoring utilities.
 */

/**
 * Normalizes bug text into lowercase alphanumeric tokens.
 *
 * @param mixed $text Source text
 * @return string Normalized text
 */
function normalizeBugText($text) {
    $text = strtolower((string)$text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

/**
 * Checks whether a normalized text contains a whole-word keyword.
 *
 * @param string $text Normalized text
 * @param string $keyword Keyword to match
 * @return bool True when keyword exists as a full token
 */
function hasKeyword($text, $keyword) {
    $pattern = '/\b' . preg_quote($keyword, '/') . '\b/';
    return preg_match($pattern, $text) === 1;
}

/**
 * Computes weighted keyword score from matched terms.
 * Higher weights indicate stronger relevance to the category or priority level.
 *
 * @param string $text Normalized text
 * @param array $weightedKeywords Map of keyword => weight
 * @return int Total score
 */
function calculateKeywordScore($text, $weightedKeywords) {
    $score = 0;
    foreach ($weightedKeywords as $keyword => $weight) {
        if (hasKeyword($text, $keyword)) {
            $score += $weight;
        }
    }
    return $score;
}

/**
 * Classifies a bug report into a category using weighted keywords.
 * Title matches are weighted more heavily than description matches.
 * Returns "General" if no category keywords are found.
 *
 * @param string $title Bug title
 * @param string $description Bug description
 * @return string Classified category label
 */
function classifyBug($title, $description) {

    $titleText = normalizeBugText($title);
    $descriptionText = normalizeBugText($description);

    $categoryKeywords = array(
        "Crash" => array(
            "crash" => 4,
            "crashes" => 4,
            "fatal" => 3,
            "exception" => 3,
            "stack trace" => 3,
            "null pointer" => 3,
            "stopped" => 2,
            "freezes" => 2,
            "hang" => 2,
            "not responding" => 3,
            "fails" => 2,
            "failure" => 2
        ),
        "Performance" => array(
            "slow" => 3,
            "sluggish" => 3,
            "lag" => 3,
            "latency" => 3,
            "delay" => 2,
            "timeout" => 3,
            "timed out" => 3,
            "performance" => 3,
            "high cpu" => 3,
            "memory leak" => 4,
            "loading" => 1,
            "takes too long" => 3
        ),
        "UI" => array(
            "ui" => 2,
            "ux" => 2,
            "layout" => 3,
            "alignment" => 3,
            "button" => 2,
            "font" => 2,
            "color" => 2,
            "responsive" => 3,
            "overlap" => 3,
            "visual" => 2,
            "css" => 2,
            "design" => 2
        ),
        "Security" => array(
            "security" => 4,
            "vulnerability" => 4,
            "xss" => 5,
            "csrf" => 5,
            "sql injection" => 5,
            "sqli" => 5,
            "token leak" => 4,
            "unauthorized" => 4,
            "permission bypass" => 5,
            "exploit" => 4,
            "breach" => 5,
            "authentication bypass" => 5
        )
    );

    $scores = array();

    foreach ($categoryKeywords as $category => $keywords) {
        $titleScore = calculateKeywordScore($titleText, $keywords);
        $descriptionScore = calculateKeywordScore($descriptionText, $keywords);
        $scores[$category] = ($titleScore * 2) + $descriptionScore;
    }

    // Security should win close ties because impact is usually highest.
    $tieBreakOrder = array("Security", "Crash", "Performance", "UI");

    $bestCategory = "General";
    $bestScore = 0;

    foreach ($tieBreakOrder as $category) {
        if ($scores[$category] > $bestScore) {
            $bestScore = $scores[$category];
            $bestCategory = $category;
        }
    }

    if ($bestScore === 0) {
        return "General";
    }

    return $bestCategory;
}

/**
 * Assigns priority based on textual severity indicators and category weight.
 * Higher scores indicate more urgent issues that should be addressed sooner.
 * Category weight boosts priority for critical areas like Security and Crash.
 * Returns "High", "Medium", or "Low" priority labels.
 *
 * @param string $description Bug description
 * @param string $title Bug title
 * @param string $category Optional pre-classified category
 * @return string High|Medium|Low
 */
function getPriority($description, $title = "", $category = "") {

    $titleText = normalizeBugText($title);
    $descriptionText = normalizeBugText($description);
    $combinedText = trim($titleText . " " . $descriptionText);

    $severityWeights = array(
        "critical" => 5,
        "urgent" => 5,
        "production down" => 6,
        "data loss" => 6,
        "security breach" => 6,
        "cannot login" => 4,
        "payment failed" => 5,
        "not responding" => 4,
        "crash" => 4,
        "exception" => 3,
        "timeout" => 3,
        "very slow" => 3,
        "intermittent" => 2,
        "minor" => -1,
        "cosmetic" => -2,
        "typo" => -2
    );

    $score = calculateKeywordScore($combinedText, $severityWeights);

    if ($category === "") {
        $category = classifyBug($title, $description);
    }

    if ($category === "Security") {
        $score += 4;
    } elseif ($category === "Crash") {
        $score += 3;
    } elseif ($category === "Performance") {
        $score += 1;
    }

    if ($score >= 7) {
        return "High";
    }
    if ($score >= 3) {
        return "Medium";
    }
    return "Low";
}

?>