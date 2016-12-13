<?php

require_once 'Log/mail.php';

class Log_xdmailer extends Log_mail
{

    function log($message, $priority = null)
    {
        if (is_array($message))
        {
            $parts = array();

            if (isset($message['message'])) {
                $parts[] = $message['message'];
                unset($message['message']);
            }

            if (count($message) > 0) {
                $nonMessageParts = array();

                while (list($key, $value) = each($message)) {
                    $nonMessageParts[] = "$key: $value";
                }

                $parts[] = '(' . implode(', ', $nonMessageParts) . ')';
            }

            return parent::log(implode("\n", $parts), $priority);
            
        }
        else
        {
            return parent::log($message, $priority);
        }
    }
}

