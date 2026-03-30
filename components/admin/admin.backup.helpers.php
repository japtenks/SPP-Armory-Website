<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_backup_output_dir(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'sql_backups';
}

function spp_admin_backup_sql_literal($value): string
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_int($value) || is_float($value)) {
        return (string)$value;
    }
    return "'" . str_replace(
        array("\\", "'", "\0", "\n", "\r"),
        array("\\\\", "\\'", "\\0", "\\n", "\\r"),
        (string)$value
    ) . "'";
}

function spp_admin_backup_insert_sql(string $table, array $row): string
{
    $columns = array_map(function ($column) {
        return '`' . str_replace('`', '', (string)$column) . '`';
    }, array_keys($row));
    $values = array_map('spp_admin_backup_sql_literal', array_values($row));
    return 'INSERT INTO `' . str_replace('`', '', $table) . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
}

function spp_admin_backup_character_copy_accounts($mw): array
{
    return array(
        'horde' => (int)($mw->getConfig->character_copy_config->accounts->horde ?? 0),
        'alliance' => (int)($mw->getConfig->character_copy_config->accounts->alliance ?? 0),
    );
}

function spp_admin_backup_update_character_data_field(string $dataField, int $newGuid): string
{
    $parts = explode(' ', $dataField);
    if (isset($parts[0])) {
        $parts[0] = (string)$newGuid;
    }
    return implode(' ', $parts);
}

function spp_admin_backup_update_item_data_field(string $dataField, int $newItemGuid, int $newOwnerGuid): string
{
    $parts = explode(' ', $dataField);
    if (isset($parts[0])) {
        $parts[0] = (string)$newItemGuid;
    }
    if (isset($parts[6])) {
        $parts[6] = (string)$newOwnerGuid;
    }
    if (isset($parts[8])) {
        $parts[8] = (string)$newOwnerGuid;
    }
    return implode(' ', $parts);
}
