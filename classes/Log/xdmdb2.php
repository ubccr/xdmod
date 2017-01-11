<?php

require_once 'Log/mdb2.php';

class Log_xdmdb2 extends Log_mdb2
{

    function log($message, $priority = null)
    {
        if (is_array($message)) {
            return $this->log_impl(json_encode($message), $priority);
        } else {
            return $this->log_impl(json_encode(array('message' => $message)), $priority);
        }
    }

    function log_impl($message, $priority = null)
    {
        /* If a priority hasn't been specified, use the default value. */
        if ($priority === null) {
            $priority = $this->_priority;
        }

        /* Abort early if the priority is above the maximum logging level. */
        if (!$this->_isMasked($priority)) {
            return false;
        }

        /* If the connection isn't open and can't be opened, return failure. */
        if (!$this->_opened && !$this->open()) {
            return false;
        }

        /* If we don't already have a statement object, create one. */
        if (!is_object($this->_statement) && !$this->_prepareStatement()) {
            return false;
        }

        /* Extract the string representation of the message. */
        $message = $this->_extractMessage($message);

        /* Build our set of values for this log entry. */
        $values = array(
            'id'       => $this->_db->nextId($this->_sequence),
            'logtime'  => MDB2_Date::mdbNow(),
            'ident'    => $this->_ident,
            'priority' => $priority,
            'message'  => $message
        );

        if (PEAR::isError($values['id'])) {
            error_log("Failed to process log entry: " . print_r($values, true));
            return false;
        }
        /* Execute the SQL query for this log entry insertion. */
        $this->_db->expectError(MDB2_ERROR_NOSUCHTABLE);
        $result = &$this->_statement->execute($values);
        $this->_db->popExpect();

        /* Attempt to handle any errors. */
        if (PEAR::isError($result)) {
            /* We can only handle MDB2_ERROR_NOSUCHTABLE errors. */
            if ($result->getCode() != MDB2_ERROR_NOSUCHTABLE) {
                return false;
            }

            /* Attempt to create the target table. */
            if (!$this->_createTable()) {
                return false;
            }

            /* Recreate our prepared statement resource. */
            $this->_statement->free();
            if (!$this->_prepareStatement()) {
                return false;
            }

            /* Attempt to re-execute the insertion query. */
            $result = $this->_statement->execute($values);
            if (PEAR::isError($result)) {
                return false;
            }
        }

        $this->_announce(array('priority' => $priority, 'message' => $message));

        return true;
    }
}
