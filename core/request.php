<?php

if (!function_exists('spp_allowed_field_map')) {
    function spp_allowed_field_map(array $allowedFields) {
        return array_fill_keys($allowedFields, true);
    }
}

if (!function_exists('spp_filter_allowed_fields')) {
    function spp_filter_allowed_fields(array $data, array $allowedFields) {
        $isList = array_keys($allowedFields) === range(0, count($allowedFields) - 1);
        $allowedMap = $isList ? spp_allowed_field_map($allowedFields) : $allowedFields;

        foreach (array_keys($data) as $fieldName) {
            if (!isset($allowedMap[$fieldName])) {
                unset($data[$fieldName]);
            }
        }

        return $data;
    }
}
