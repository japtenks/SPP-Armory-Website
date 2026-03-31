<?php
if(INCLUDED!==true)exit;

if (!function_exists('spp_admin_config_convert')) {
    function spp_admin_config_convert($xml) {
        if ($xml instanceof SimpleXMLElement) {
            $children = $xml->children();
            $return = null;
        }

        foreach ($children as $element => $value) {
            if ($value instanceof SimpleXMLElement) {
                $values = (array)$value->children();

                if (count($values) > 0) {
                    if (is_array($return[$element] ?? null)) {
                        foreach ($return[$element] as $k => $v) {
                            if (!is_int($k)) {
                                $return[$element][0][$k] = $v;
                                unset($return[$element][$k]);
                            }
                        }
                        $return[$element][] = spp_admin_config_convert($value);
                    } else {
                        $return[$element] = spp_admin_config_convert($value);
                    }
                } else {
                    if (!isset($return[$element])) {
                        $return[$element] = (string)$value;
                    } else {
                        if (!is_array($return[$element])) {
                            $return[$element] = array($return[$element], (string)$value);
                        } else {
                            $return[$element][] = (string)$value;
                        }
                    }
                }
            }
        }

        return is_array($return ?? null) ? $return : false;
    }
}

if (!function_exists('spp_admin_config_flatten')) {
    function spp_admin_config_flatten($array, $path = "")
    {
        $result = array();
        foreach ($array as $key => $value) {
            $fullkey = empty($path) ? $key : $path . "." . $key;
            if (is_array($value)) {
                $result = array_merge($result, spp_admin_config_flatten($value, $fullkey));
            } elseif (is_numeric($value)) {
                $result[$fullkey] = (string)$value;
            } else {
                $result[$fullkey] = '"' . $value . '"';
            }
        }
        return $result;
    }
}

function spp_admin_config_build_view($configObject)
{
    $configfilepath = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.xml";
    $config = spp_admin_config_flatten(spp_admin_config_convert($configObject));
    ksort($config);

    return array(
        'configfilepath' => $configfilepath,
        'config' => $config,
        'configCount' => count($config),
    );
}
