<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_realms_type_definitions()
{
    return array(
        0 => 'Normal',
        1 => 'PVP',
        4 => 'Normal',
        6 => 'RP',
        8 => 'RPPVP',
        16 => 'FFA_PVP',
    );
}

function spp_admin_realms_timezone_definitions()
{
    return array(
         0 => 'Unknown',
         1 => 'Development',
         2 => 'United States',
         3 => 'Oceanic',
         4 => 'Latin America',
         5 => 'Tournament',
         6 => 'Korea',
         7 => 'Tournament',
         8 => 'English',
         9 => 'German',
        10 => 'French',
        11 => 'Spanish',
        12 => 'Russian',
        13 => 'Tournament',
        14 => 'Taiwan',
        15 => 'Tournament',
        16 => 'China',
        17 => 'CN1',
        18 => 'CN2',
        19 => 'CN3',
        20 => 'CN4',
        21 => 'CN5',
        22 => 'CN6',
        23 => 'CN7',
        24 => 'CN8',
        25 => 'Tournament',
        26 => 'Test Server',
        27 => 'Tournament',
        28 => 'QA Server',
        29 => 'CN9',
    );
}

function spp_admin_realms_filter_fields(array $data)
{
    $allowed = array(
        'name',
        'address',
        'port',
        'icon',
        'timezone',
        'ra_address',
        'ra_port',
        'ra_user',
        'ra_pass',
        'soap_address',
        'soap_port',
        'soap_user',
        'soap_pass',
        'dbinfo',
    );
    return spp_filter_allowed_fields($data, $allowed);
}

function spp_admin_realms_normalize_fields(array $data)
{
    $data = spp_admin_realms_filter_fields($data);
    $data['name'] = trim((string)($data['name'] ?? ''));
    $data['address'] = trim((string)($data['address'] ?? ''));
    $data['port'] = (int)($data['port'] ?? 0);
    $data['icon'] = (int)($data['icon'] ?? 0);
    $data['timezone'] = (int)($data['timezone'] ?? 0);
    $data['ra_address'] = trim((string)($data['ra_address'] ?? ''));
    $data['ra_port'] = (int)($data['ra_port'] ?? 0);
    $data['ra_user'] = trim((string)($data['ra_user'] ?? ''));
    $data['ra_pass'] = (string)($data['ra_pass'] ?? '');
    $data['soap_address'] = trim((string)($data['soap_address'] ?? '127.0.0.1'));
    $data['soap_port'] = (int)($data['soap_port'] ?? 7878);
    $data['soap_user'] = trim((string)($data['soap_user'] ?? ''));
    $data['soap_pass'] = (string)($data['soap_pass'] ?? '');
    $data['dbinfo'] = trim((string)($data['dbinfo'] ?? ''));
    return $data;
}
