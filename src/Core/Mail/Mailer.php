<?php

namespace Core\Mail;

class Mailer
{
    protected SmtpMailer $transport;
    protected array $globalFrom; // ['address'=>'...', 'name' => '...']
    protected string $templatesPath;

    public function __construct(
        SmtpMailer $transport
    ) {
        $this->transport = $transport;
        $this->globalFrom = config('mail.from');
        $this->templatesPath = rtrim(config('mail.template')['path'], '/');
    }

    /**
     * Render a PHP template file with data.
     * Template files are small PHP files that can echo/return HTML.
     * Example: resources/emails/welcome.html.php and welcome.text.php
     */
    private function renderTemplate(string $name, array $data = [], string $format = 'html'): string
    {
        $ext = $format === 'text' ? '.text.php' : '.html.php';
        $path = $this->templatesPath . '/' . $name . $ext;

        if (file_exists($path)) {
            $path = realpath($path);
        }

        if (!is_readable($path)) {
            error_log("Mailer: Template not found/readable at: " . $path . " (Original: " . $this->templatesPath . '/' . $name . $ext . ")");
            return '';
        }

        // Extract variables into local scope and capture output
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }

    /**
     * Build an RFC-2822 style headers + body message for a multipart/alternative (text + html) email.
     */
    private function buildMultipartMessage(
        string $fromAddress,
        ?string $fromName,
        string $to,
        string $subject,
        string $textBody,
        string $htmlBody
    ): string {
        $boundary = 'b_' . md5(uniqid((string) microtime(true), true));
        $headers = [];

        $fromHeader = $fromName ?
            sprintf('%s <%s>', $this->encodeHeader($fromName), $fromAddress)
            : $fromAddress;
        $headers[] = 'From: ' . $fromHeader;
        $headers[] = 'To: ' . $to;
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'Date: ' . date(DATE_RFC2822);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        // Build multipart body
        $eol = "\r\n";
        $body = '';
        // plain part
        $body .= "--{$boundary}{$eol}";
        $body .= "Content-Type: text/plain; charset=utf-8{$eol}";
        $body .= "Content-Transfer-Encoding: 8bit{$eol}{$eol}";
        $body .= rtrim($textBody, "\r\n") . $eol . $eol;

        // html part
        $body .= "--{$boundary}{$eol}";
        $body .= "Content-Type: text/html; charset=utf-8{$eol}";
        $body .= "Content-Transfer-Encoding: 8bit{$eol}{$eol}";
        $body .= rtrim($htmlBody, "\r\n") . $eol . $eol;

        // end
        $body .= "--{$boundary}--{$eol}";

        return implode($eol, $headers) . $eol . $eol . $body;
    }

    private function encodeHeader(string $value): string
    {
        // Simple encoded-word header encoding for UTF-8 subjects/names
        if (!preg_match('//u', $value)) {
            return $value;
        }
        // Use base64 encoding per RFC 2047
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    /**
     * Send using a named template that should ideally have both .text.php and .html.php variants.
     *
     * @param string|array $to single email string or ['name <email>'] for To header
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        array $data = [],
        ?string $subject = null
    ): bool {
        $html = $this->renderTemplate($templateName, $data, 'html');
        $text = $this->renderTemplate($templateName, $data, 'text');

        if ($html === '' && $text === '') {
            throw new \RuntimeException("No template found for '{$templateName}'");
        }

        // fallback if text missing
        if ($text === '') {
            // generate a simple plaintext from HTML by stripping tags
            $text = trim(strip_tags($html));
        }

        // fallback if html missing (wrap plaintext)
        if ($html === '') {
            $html = nl2br(
                htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        }

        $fromAddress = $this->globalFrom['address'] ?? null;
        $fromName = $this->globalFrom['name'] ?? null;
        if (!$fromAddress) {
            throw new \RuntimeException("No from address configured");
        }

        $subject = $subject ?? ($data['subject'] ?? '(no subject)');

        // Build RFC 2822 message
        $raw = $this->buildMultipartMessage(
            $fromAddress,
            $fromName,
            $to,
            $subject,
            $text,
            $html
        );

        // Dispatch
        return $this->transport->send($fromAddress, $to, $raw);
    }
}
