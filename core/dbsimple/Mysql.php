<?php

require_once dirname(__FILE__) . '/Generic.php';

class DbSimple_Mysql extends DbSimple_Generic_Database
{
    var $link;

    /** constructor(string $dsn) */
    function DbSimple_Mysql($dsn)
    {
        $p = DbSimple_Generic::parseDSN($dsn);
        if (!is_callable('mysqli_connect')) {
            return $this->_setLastError("-1", "MySQL extension is not loaded", "mysqli_connect");
        }

        $this->link = @mysqli_connect(
            $p['host'],
            $p['user'],
            $p['pass'],
            null,
            (empty($p['port']) ? null : $p['port'])
        );

        $this->_resetLastError();
        if (!$this->link) {
            return $this->_setDbError('mysqli_connect("' . $p['host'] . '", "' . $p['user'] . '")');
        }

        $ok = @mysqli_select_db($this->link, preg_replace('{^/}s', '', $p['path']));
        if (!$ok) return $this->_setDbError('mysqli_select_db()');

        if (isset($p["charset"])) {
            $this->query('SET NAMES ?', $p["charset"]);
        }
    }

    function _performEscape($s, $isIdent = false)
    {
        return $isIdent
            ? "`" . str_replace('`', '``', $s) . "`"
            : "'" . mysqli_real_escape_string($this->link, $s) . "'";
    }

    function _performTransaction($parameters = null)
    {
        return $this->query('BEGIN');
    }

    function& _performNewBlob($blobid = null)
    {
        $obj = new DbSimple_Mysql_Blob($this, $blobid);
        return $obj;
    }

    function _performGetBlobFieldNames($result)
    {
        $blobFields = array();
        for ($i = mysqli_num_fields($result) - 1; $i >= 0; $i--) {
            $field = @mysqli_fetch_field_direct($result, $i);
            if (in_array($field->type, [
                MYSQLI_TYPE_TINY_BLOB,
                MYSQLI_TYPE_MEDIUM_BLOB,
                MYSQLI_TYPE_LONG_BLOB,
                MYSQLI_TYPE_BLOB
            ])) {
                $blobFields[] = $field->name;
            }
        }
        return $blobFields;
    }

    function _performGetPlaceholderIgnoreRe()
    {
        return '
            "   (?> [^"\\\\]+|\\\\"|\\\\)*    "   |
            \'  (?> [^\'\\\\]+|\\\\\'|\\\\)* \'   |
            `   (?> [^`]+ | ``)*              `   |
            /\* .*?                          \*/      
        ';
    }

    function _performCommit()  { return $this->query('COMMIT'); }
    function _performRollback(){ return $this->query('ROLLBACK'); }

    function _performTransformQuery(&$queryMain, $how)
    {
        switch ($how) {
            case 'CALC_TOTAL':
                if (preg_match('/^(\s*SELECT)(.*)/six', $queryMain[0], $m)) {
                    if ($this->_calcFoundRowsAvailable()) {
                        $queryMain[0] = $m[1] . ' SQL_CALC_FOUND_ROWS' . $m[2];
                    }
                }
                return true;

            case 'GET_TOTAL':
                if ($this->_calcFoundRowsAvailable()) {
                    $queryMain = ['SELECT FOUND_ROWS()'];
                }
                return true;
        }
        return false;
    }

    function _performQuery($queryMain)
    {
        $this->_lastQuery = $queryMain;
        $this->_expandPlaceholders($queryMain, false);

        $result = @mysqli_query($this->link, $queryMain[0]);
        if ($result === false) return $this->_setDbError($queryMain[0]);

        if (!is_object($result)) {
            if (preg_match('/^\s*INSERT\s+/six', $queryMain[0])) {
                return @mysqli_insert_id($this->link);
            }
            return @mysqli_affected_rows($this->link);
        }
        return $result;
    }

    function _performFetch($result)
    {
        $row = @mysqli_fetch_assoc($result);
        if (mysqli_error($this->link)) {
            return $this->_setDbError($this->_lastQuery);
        }
        return ($row === false) ? null : $row;
    }

    function _setDbError($query)
    {
        // ? FIXED: always pass the link identifier
        if ($this->link) {
            return $this->_setLastError(mysqli_errno($this->link), mysqli_error($this->link), $query);
        } else {
            // fallback in rare cases before connection
            return $this->_setLastError(mysqli_connect_errno(), mysqli_connect_error(), $query);
        }
    }

    function _calcFoundRowsAvailable()
    {
        return version_compare(mysqli_get_server_info($this->link), '4.0') >= 0;
    }
}

class DbSimple_Mysql_Blob extends DbSimple_Generic_Blob
{
    var $blobdata = null;
    var $curSeek = 0;

    function DbSimple_Mysql_Blob(&$database, $blobdata = null)
    {
        $this->blobdata = $blobdata;
    }

    function read($len)
    {
        $chunk = substr($this->blobdata, $this->curSeek, $len);
        $this->curSeek += $len;
        return $chunk;
    }

    function write($data)
    {
        $this->blobdata .= $data;
    }

    function close()  { return $this->blobdata; }
    function length() { return strlen($this->blobdata); }
}
?>
