<?php
/**
 * Invia una risposta di errore in formato JSON e termina lo script.
 *
 * @param string $message   Messaggio di errore.
 * @param int    $code      Codice HTTP (default 400).
 */
function sendError($message, $code = 400)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}