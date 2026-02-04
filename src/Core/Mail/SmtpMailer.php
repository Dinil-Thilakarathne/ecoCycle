<?php

namespace Core\Mail;

class SmtpMailer
{
    private string $host;
    private string $port;
    private string $username;
    private string $password;
    private string $encryption; // 'tls' or 'ssl' or null
    private int $timeout = 30;
    private $socket;
    private $responseBuffer = '';

    public function __construct()
    {
        $smtpConfig = config('mail.smtp');

        // Fallback to environment variables if config not loaded
        if (!$smtpConfig || !isset($smtpConfig['host'])) {
            $smtpConfig = [
                'host' => $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST'),
                'port' => $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT'),
                'username' => $_ENV['SMTP_USER'] ?? getenv('SMTP_USER'),
                'password' => $_ENV['SMTP_PASS'] ?? getenv('SMTP_PASS'),
                'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? getenv('SMTP_ENCRYPTION'),
                'timeout' => (int) ($_ENV['SMTP_TIMEOUT'] ?? getenv('SMTP_TIMEOUT') ?? 30),
            ];
        }

        if (empty($smtpConfig['host'])) {
            throw new \RuntimeException('SMTP host not configured');
        }
        if (empty($smtpConfig['username'])) {
            throw new \RuntimeException('SMTP username not configured');
        }
        if (empty($smtpConfig['password'])) {
            throw new \RuntimeException('SMTP password not configured');
        }

        $this->host = $smtpConfig['host'];
        $this->port = $smtpConfig['port'] ??
            ($smtpConfig['encryption'] === 'ssl' ? 465 : 587);
        $this->username = $smtpConfig['username'];
        $this->password = $smtpConfig['password'];
        $this->encryption = $smtpConfig['encryption'] ?? 'tls';
        $this->timeout = $smtpConfig['timeout'] ?? 30;
    }

    private function connect()
    {
        if ($this->socket) {
            return true;
        }

        $transport = ($this->encryption === 'ssl') ? 'ssl://' : 'tcp://';
        $remote = $transport . $this->host . ':' . $this->port;

        $errno = 0;
        $errstr = '';
        $this->socket = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            $this->timeout
        );

        if (!$this->socket) {
            throw new \RuntimeException("Connection failed: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, $this->timeout);

        // Read server greeting
        $this->expectResponse(220);

        // EHLO
        $this->sendCommand("EHLO " . gethostname());
        $this->expectResponse(250);

        // If using STARTTLS (encryption === 'tls') do the upgrade
        if ($this->encryption === 'tls') {
            $this->sendCommand("STARTTLS");
            $this->expectResponse(220);

            // enable TLS
            $ok = stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$ok) {
                throw new \RuntimeException("Failed to enable TLS/STARTTLS");
            }

            // re-EHLO after TLS
            $this->sendCommand("EHLO " . gethostname());
            $this->expectResponse(250);
        }

        if ($this->username && $this->password) {
            $this->authLogin();
        }

        return true;
    }

    /**
     * Close socket and clear state.
     */
    private function disconnect(): void
    {
        if ($this->socket && is_resource($this->socket)) {
            try {
                $this->sendCommand("QUIT");
                $this->expectResponse(221);
            } catch (\Throwable $e) {
                // ignore
            }

            @fclose($this->socket);
        }

        $this->socket = null;
        $this->responseBuffer = '';
    }

    /**
     * Return true if socket looks usable.
     */
    private function isConnected(): bool
    {
        if (!$this->socket) {
            return false;
        }

        if (!is_resource($this->socket)) {
            return false;
        }

        $meta = stream_get_meta_data($this->socket);
        if (!empty($meta['timed_out']) || !empty($meta['eof'])) {
            return false;
        }

        return true;
    }

    /**
     * Force a reconnect
     *
     * @throws \RuntimeException
     */
    private function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    private function sendCommand($cmd)
    {
        if (!$this->socket) {
            throw new \RuntimeException("Not connected");
        }
        fwrite($this->socket, $cmd . "\r\n");
    }

    private function getResponseLine()
    {
        $line = fgets($this->socket, 515);
        if ($line === false)
            throw new \RuntimeException("Failed reading from socket");
        return rtrim($line, "\r\n");
    }

    private function expectResponse(int $expectedCode)
    {
        // Read possibly multi-line response; stop when line[3] != '-'
        $full = '';
        do {
            $line = $this->getResponseLine();
            $full .= $line . "\n";
            $code = (int) substr($line, 0, 3);
            $more = (isset($line[3]) && $line[3] === '-');
        } while ($more);

        // store last response
        $this->responseBuffer = $full;

        if ($code !== $expectedCode) {
            throw new \RuntimeException(
                "SMTP expected $expectedCode, got $code. Response: " . trim($full)
            );
        }
        return $full;
    }

    private function authLogin()
    {
        if (empty($this->username) || empty($this->password)) {
            throw new \RuntimeException("SMTP username/password not set");
        }

        $this->sendCommand("AUTH LOGIN");
        $this->expectResponse(334);

        $this->sendCommand(base64_encode($this->username));
        $this->expectResponse(334);

        $this->sendCommand(base64_encode($this->password));
        $this->expectResponse(235); // authentication successful
    }

    public function send(string $from, string $to, string $rawMessage)
    {
        $this->connect();

        // MAIL FROM
        $this->sendCommand("MAIL FROM:<$from>");
        $this->expectResponse(250);

        // RCPT TO (support single recipient; extendable)
        $this->sendCommand("RCPT TO:<$to>");
        $this->expectResponse(250);

        // DATA
        $this->sendCommand("DATA");
        $this->expectResponse(354);

        // Dot-stuff lines that begin with '.' per SMTP rules
        $rawMessage = preg_replace('/^\./m', '..', $rawMessage);

        // Ensure proper termination
        $out = $rawMessage . "\r\n.\r\n";

        $written = @fwrite($this->socket, $out);
        if ($written === false) {
            throw new \RuntimeException("Failed writing message to SMTP socket");
        }

        $this->expectResponse(250);

        // QUIT
        $this->disconnect();

        return true;
    }
}
