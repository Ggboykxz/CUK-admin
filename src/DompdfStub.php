<?php

declare(strict_types=1);

namespace CUK;

class DompdfStub
{
    private string $html = '';
    private string $paperSize = 'A4';
    private string $orientation = 'portrait';

    public function loadHtml(string $html): void
    {
        $this->html = $html;
    }

    public function setPaper(string $size, string $orientation = 'portrait'): void
    {
        $this->paperSize = $size;
        $this->orientation = $orientation;
    }

    public function render(): void {}

    public function stream(string $filename, array $options = []): void
    {
        $attachment = $options['Attachment'] ?? true;
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: ' . ($attachment ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
        echo '<html><head><meta charset="utf-8"><title>' . $filename . '</title>';
        echo '<style>body{font-family:sans-serif;padding:20px;color:#333;}';
        echo '.header{text-align:center;border-bottom:2px solid #1e3a5f;margin-bottom:20px;}';
        echo 'table{width:100%;border-collapse:collapse;margin-bottom:15px;}';
        echo 'th{background:#1e3a5f;color:white;padding:8px;text-align:left;}';
        echo 'td{padding:6px 8px;border-bottom:1px solid #ddd;}';
        echo '.ue-row{background:#f0f4ff;font-weight:bold;}';
        echo '</style></head><body>';
        echo 'PDF: ' . $filename . '<br><br>';
        echo '<div style="border:2px dashed #ccc;padding:20px;border-radius:8px;text-align:center;color:#999;">';
        echo 'Installez Composer puis lancez <code>composer require dompdf/dompdf</code> pour générer de vrais PDF.';
        echo '</div><hr>';
        echo $this->html;
        echo '</body></html>';
        exit;
    }

    public function output(): string
    {
        return $this->html;
    }
}

class_alias(DompdfStub::class, 'Dompdf\Options');
