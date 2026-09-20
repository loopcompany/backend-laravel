<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$markdownPath = dirname(__DIR__) . '/docs/ARVAN_CLOUD_MIGRATION.md';
$pdfPath = dirname(__DIR__) . '/docs/ARVAN_CLOUD_MIGRATION.pdf';

$markdown = file_get_contents($markdownPath);
if ($markdown === false) {
    throw new RuntimeException('Could not read the migration guide.');
}

function addBidiMarks(string $html): string
{
    $parts = preg_split('/(<[^>]+>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

    foreach ($parts as $index => $part) {
        if ($part === '' || str_starts_with($part, '<')) {
            continue;
        }

        $parts[$index] = preg_replace_callback(
            '/[A-Za-z0-9][A-Za-z0-9_@.\/:+=?&%#~,\-]*/u',
            static fn (array $match): string => "\u{200E}" . $match[0] . "\u{200E}",
            $part
        );
    }

    return implode('', $parts);
}

function inlineMarkdown(string $line): string
{
    $line = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $line = preg_replace_callback(
        '/\[([^]]+)]\((https?:\/\/[^)]+)\)/',
        static fn (array $match): string => '<a href="' . $match[2] . '">' . $match[1] . '</a>',
        $line
    );

    $line = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $line);

    return addBidiMarks($line);
}

function markdownToHtml(string $markdown): string
{
    $lines = preg_split('/\R/u', $markdown) ?: [];
    $html = '';
    $inCode = false;
    $inList = false;

    foreach ($lines as $line) {
        if (preg_match('/^~~~/', $line)) {
            if ($inCode) {
                $html .= '</code></pre>';
                $inCode = false;
            } else {
                if ($inList) {
                    $html .= '</ul>';
                    $inList = false;
                }
                $html .= '<pre dir="ltr"><code>';
                $inCode = true;
            }
            continue;
        }

        if ($inCode) {
            $html .= htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
            continue;
        }

        if (trim($line) === '') {
            if ($inList) {
                $html .= '</ul>';
                $inList = false;
            }
            continue;
        }

        if (preg_match('/^(#{1,3})\s+(.+)$/u', $line, $match)) {
            if ($inList) {
                $html .= '</ul>';
                $inList = false;
            }
            $level = strlen($match[1]);
            $html .= '<h' . $level . '>' . inlineMarkdown($match[2]) . '</h' . $level . '>';
            continue;
        }

        if (preg_match('/^-\s+(.+)$/u', $line, $match)) {
            if (!$inList) {
                $html .= '<ul>';
                $inList = true;
            }
            $html .= '<li>' . inlineMarkdown($match[1]) . '</li>';
            continue;
        }

        if (preg_match('/^\d+\.\s+(.+)$/u', $line, $match)) {
            if (!$inList) {
                $html .= '<ol>';
                $inList = true;
            }
            $html .= '<li>' . inlineMarkdown($match[1]) . '</li>';
            continue;
        }

        if ($inList) {
            $html .= '</ul>';
            $inList = false;
        }

        if (preg_match('/^---+$/', trim($line))) {
            $html .= '<hr>';
            continue;
        }

        $html .= '<p>' . inlineMarkdown($line) . '</p>';
    }

    if ($inCode) {
        $html .= '</code></pre>';
    }

    if ($inList) {
        $html .= '</ul>';
    }

    return $html;
}

$html = '<!doctype html><html dir="rtl"><head><meta charset="UTF-8"></head><body>';
$html .= '<style>
    body {
        font-family: dejavusans;
        direction: rtl;
        text-align: right;
        line-height: 1.65;
        color: #202124;
    }
    h1 {
        color: #123b61;
        font-size: 22pt;
        border-bottom: 1px solid #123b61;
        padding-bottom: 8px;
    }
    h2 {
        color: #185a8d;
        font-size: 16pt;
        margin-top: 18px;
    }
    h3 {
        color: #2f6f9f;
        font-size: 13pt;
    }
    p, li {
        font-size: 10pt;
    }
    ul, ol {
        margin-right: 18px;
        margin-left: 0;
    }
    pre {
        direction: ltr;
        text-align: left;
        background-color: #f4f6f8;
        border: 0.5px solid #ccd5dd;
        padding: 8px;
        font-family: dejavusans;
        font-size: 7.5pt;
        line-height: 1.35;
        white-space: pre-wrap;
    }
    a {
        color: #1261a0;
    }
    hr {
        border: 0;
        border-top: 0.5px solid #ccd5dd;
    }
</style>';
$html .= markdownToHtml($markdown);
$html .= '</body></html>';

$pdf = new class extends TCPDF {
    public function Header(): void
    {
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('dejavusans', '', 8);
        $this->Cell(0, 8, 'راهنمای مهاجرت پروژه - صفحه ' . $this->getAliasNumPage(), 0, 0, 'C');
    }
};

$pdf->SetCreator('Laravel Project');
$pdf->SetAuthor('Loop Company');
$pdf->SetTitle('راهنمای مهاجرت پروژه به ابر آروان');
$pdf->SetSubject('Deployment and rollback guide');
$pdf->setRTL(true);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->SetMargins(15, 16, 15);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage('P', 'A4');
$pdf->SetFont('dejavusans', '', 10);
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output($pdfPath, 'F');

fwrite(STDOUT, "PDF created: {$pdfPath}\n");
