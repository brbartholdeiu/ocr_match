<?php
// added auto push on file save with auto.sh
// more updates to test auto.sh with gitignore and self ignore itself?
// added post commit hooks test with auto.sh
// Enable PHP error reporting mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../vendor/autoload.php';
use thiagoalessio\TesseractOCR\TesseractOCR;

function extractTextFromImage($imagePath) {
    $ocr = new TesseractOCR($imagePath);
    return $ocr->run();
}

function extractHtmlFromWebsite($url) {
    return file_get_contents($url);
}

function normalizeText($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^\w\s]/', '', $text); // Remove punctuation
    $text = preg_replace('/\s+/', ' ', $text); // Normalize whitespace
    return trim($text);
}

function compareTexts($text1, $text2, $mode) {
    $text1 = normalizeText($text1);
    $text2 = normalizeText($text2);

    if ($mode === 'phrase') {
        return strpos($text2, $text1) !== false;
    } else {
        $words1 = explode(' ', $text1);
        $words2 = explode(' ', $text2);
        return array_intersect($words1, $words2);
    }
}

function findPhraseInstances($html, $phrase) {
    $normalizedPhrase = normalizeText($phrase);
    $normalizedHtml = normalizeText($html);
    $matches = [];
    $offset = 0;

    while (($pos = strpos($normalizedHtml, $normalizedPhrase, $offset)) !== false) {
        $start = max(0, $pos - 100); // Context before match
        $end = min(strlen($html), $pos + strlen($phrase) + 100); // Context after match
        $context = substr($html, $start, $end - $start);
        $highlightedContext = highlightMatch($context, $phrase);
        $matches[] = [
            'position' => $pos,
            'context' => $highlightedContext,
            'matchStart' => $start,
            'matchEnd' => $end,
        ];
        $offset = $pos + strlen($normalizedPhrase);
    }
    return $matches;
}

function highlightMatch($context, $phrase) {
    $normalizedPhrase = normalizeText($phrase);
    $escapedContext = htmlspecialchars($context, ENT_QUOTES, 'UTF-8');
    $highlightedContext = str_ireplace($normalizedPhrase, '<span class="highlight-green">' . $normalizedPhrase . '</span>', $escapedContext);
    return $highlightedContext;
}

function getLineNumber($html, $position) {
    $lines = substr_count(substr($html, 0, $position), "\n") + 1;
    return $lines;
}

// Hardcoded paths
$imagePath = './images/texocr.png';
$websiteUrl = './text.html';

// Extract text from image
$imageText = extractTextFromImage($imagePath);

// Extract HTML from website
$websiteHtml = extractHtmlFromWebsite($websiteUrl);

// Determine matching mode
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'words';

// Compare texts
$matches = compareTexts($imageText, $websiteHtml, $mode);

// Find phrase instances
$phraseInstances = $mode === 'phrase' ? findPhraseInstances($websiteHtml, $imageText) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Text Comparison</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .iframe-container {
            position: relative;
            width: 100%;
            overflow: hidden;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            display: flex;
            align-items: flex-start;
        }
        .iframe-container img, .iframe-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .code-container {
            background-color: #f9f9f9;
            border: 1px solid #e1e1e1;
            padding: 16px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .code-container code {
            font-family: monospace;
        }
        .line-number {
            color: #888;
            display: inline-block;
            width: 2em;
            text-align: right;
            margin-right: 1em;
        }
        .highlight-green {
            background-color: lightgreen;
        }
    </style>
    <script>
        function toggleSection(sectionId) {
            var section = document.getElementById(sectionId);
            if (section.style.display === "none") {
                section.style.display = "block";
            } else {
                section.style.display = "none";
            }
        }
    </script>
</head>
<body class="bg-gray-100 p-6">
<div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-6">Text Comparison Results</h1>

    <form method="GET" class="mb-6">
        <label for="mode" class="block mb-2 font-semibold">Select Matching Mode:</label>
        <select name="mode" id="mode" class="p-2 border border-gray-300 rounded">
            <option value="words" <?php echo $mode === 'words' ? 'selected' : ''; ?>>Match Words</option>
            <option value="phrase" <?php echo $mode === 'phrase' ? 'selected' : ''; ?>>Match Phrase</option>
        </select>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded ml-2">Compare</button>
    </form>

    <div class="flex flex-wrap mb-6">
        <div class="w-full lg:w-1/2 p-3">
            <button class="bg-blue-500 text-white px-4 py-2 rounded" onclick="toggleSection('imageSection')">Toggle Image</button>
            <div id="imageSection" class="iframe-container mt-4">
                <img src="<?php echo $imagePath; ?>" alt="Image" class="w-full h-auto">
            </div>
        </div>
        <div class="w-full lg:w-1/2 p-3">
            <button class="bg-blue-500 text-white px-4 py-2 rounded" onclick="toggleSection('htmlSection')">Toggle HTML Content</button>
            <div id="htmlSection" class="iframe-container mt-4">
                <iframe src="<?php echo $websiteUrl; ?>" frameborder="0"></iframe>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-semibold mb-4">Text from Image</h2>
        <pre class="bg-gray-100 p-4 rounded"><?php echo htmlspecialchars($imageText); ?></pre>
    </div>

    <div class="bg-white p-6 rounded shadow mt-6">
        <h2 class="text-2xl font-semibold mb-4">Text from Website</h2>
        <pre class="bg-gray-100 p-4 rounded"><?php echo htmlspecialchars($websiteHtml); ?></pre>
    </div>

    <div class="bg-white p-6 rounded shadow mt-6">
        <h2 class="text-2xl font-semibold mb-4">Matching Results</h2>
        <div class="flex flex-wrap">
            <?php if ($mode === 'phrase' && !empty($phraseInstances)): ?>
                <?php foreach ($phraseInstances as $instance): ?>
                    <div class="code-container mb-4">
                        <code>
                            <?php
                            // Display context lines with highlighting
                            $lines = explode("\n", $instance['context']);
                            $startLine = getLineNumber($websiteHtml, $instance['matchStart']);
                            foreach ($lines as $lineNumber => $lineContent): ?>
                                <div>
                                    <span class="line-number"><?php echo $startLine + $lineNumber; ?>:</span>
                                    <span><?php echo $lineContent; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </code>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($mode === 'words'): ?>
                <?php foreach ($matches as $word): ?>
                    <span class="bg-green-200 text-green-800 px-2 py-1 rounded m-1"><?php echo htmlspecialchars($word); ?></span>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="bg-red-200 text-red-800 px-2 py-1 rounded m-1">No matches found</span>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
