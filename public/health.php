<?php
// Lightweight health endpoint for Railway / external monitors
http_response_code(200);
header('Content-Type: text/plain');
echo 'OK';