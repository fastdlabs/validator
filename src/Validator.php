<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 17-2-20 15:22
 */
namespace Runner\Validator;

/**
 * Class Validator
 * @package Runner\Validation
 */
class Validator
{

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $ruleGroups;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var array
     */
    protected $forceRules = ['Required'];

    /**
     * @var array
     */
    protected $messageTemplates = [];

    /**
     * Validator constructor.
     * @param array $data
     * @param array $ruleGroups
     */
    public function __construct(array $data, array $ruleGroups)
    {
        $this->data = $data;
        $this->parseRules($ruleGroups);
        $this->messageTemplates = require __DIR__ . '/message.php';
    }

    /**
     * @return bool
     */
    public function validate()
    {
        foreach ($this->ruleGroups as $field => $rules) {
            if ($this->hasField($field)) {
                foreach ($rules as $rule => $parameters) {
                    if (!$this->runValidateRule($field, $rule, $parameters)) {
                        $this->messages[$field][$rule] = $this->buildFailMessage($rule, $field, $parameters);
                    }
                }
            } elseif ($forceRules = array_intersect($this->forceRules, array_keys($rules))) {
                foreach ($forceRules as $rule) {
                    $this->messages[$field][$rule] = $this->buildFailMessage($rule, $field, []);
                }
            }
        }

        return !(bool)$this->messages;
    }

    /**
     * @return array
     */
    public function fails()
    {
        return array_keys($this->messages);
    }

    /**
     * @return array
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * @return array
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * @param array $ruleGroups
     */
    protected function parseRules(array $ruleGroups)
    {
        $map = [];
        foreach ($ruleGroups as $field => $rules) {
            foreach (explode('|', $rules) as $rule) {
                list($rule, $parameters) = explode(':', (false === strpos($rule, ':') ? ($rule . ':') : $rule), 2);
                if (isset($map[$rule])) {
                    $rule = $map[$rule];
                } else {
                    $rule = $map[$rule] = implode('', array_map(function ($value) {
                        return ucfirst($value);
                    }, explode('_', $rule)));
                }
                $this->ruleGroups[$field][$rule] = ('' === $parameters ? [] : explode(',', $parameters));
            }
        }
        unset($map);
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function hasField($field)
    {
        $field = explode('.', $field);
        $item = array_shift($field);
        if (!array_key_exists($item, $this->data)) {
            return false;
        }
        $value = $this->data[$item];

        foreach ($field as $item) {
            if (!array_key_exists($item, $value)) {
                return false;
            }
            $value = $value[$item];
        }
        return true;
    }

    /**
     * @param string $field
     * @return mixed
     */
    protected function getField($field)
    {
        $field = explode('.', $field);
        $item = array_shift($field);
        $value = $this->data[$item];
        foreach ($field as $item) {
            $value = $value[$item];
        }
        return $value;
    }

    /**
     * @param $field
     * @param $rule
     * @param array $parameters
     * @return bool
     */
    protected function runValidateRule($field, $rule, array $parameters = [])
    {
        return (bool)call_user_func([$this, "validate{$rule}"], $this->getField($field), $parameters);
    }

    /**
     * @param $rule
     * @param $field
     * @param array $parameters
     * @return string
     */
    protected function buildFailMessage($rule, $field, array $parameters = [])
    {
        if (!isset($this->messageTemplates[$rule])) {
            return "{$field} field check failed";
        }
        array_unshift($parameters, "{$field} {$this->messageTemplates[$rule]}");
        return call_user_func_array('sprintf', $parameters);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateAccept($value, array $parameters = [])
    {
        return in_array(strtolower($value), ['yes', 'on', '1', 1, true], true);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateNumeric($value, array $parameters = [])
    {
        return false !== filter_var($value, FILTER_VALIDATE_INT) || false !== filter_var($value, FILTER_VALIDATE_FLOAT);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateInteger($value, array $parameters = [])
    {
        return false !== filter_var($value, FILTER_VALIDATE_INT);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateFloat($value, array $parameters = [])
    {
        return false !== filter_var($value, FILTER_VALIDATE_FLOAT);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateSize($value, array $parameters)
    {
        return $this->getSize($value) === intval($parameters[0]);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateUrl($value, array $parameters = [])
    {
        return false !== filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateBoolean($value, array $parameters = [])
    {
        return in_array($value, [true, false, 0, 1, '0', '1'], true);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateConfirm($value, array $parameters)
    {
        return $value === $this->data[$parameters[0]];
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateDate($value, array $parameters = [])
    {
        return false !== strtotime($value);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateEmail($value, array $parameters = [])
    {
        return false !== filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateRequired($value, array $parameters = [])
    {
        return !is_null($value);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateArray($value, array $parameters = [])
    {
        return is_array($value);
    }

    /**
     * @param $value
     * @param array $parameteres
     * @return bool
     */
    protected function validateString($value, array $parameteres = [])
    {
        return is_string($value);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateNullable($value, array $parameters = [])
    {
        return true;
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateMin($value, array $parameters)
    {
        return $this->getSize($value) >= $parameters[0];
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateMax($value, array $parameters)
    {
        return $this->getSize($value) <= $parameters[0];
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateRange($value, array $parameters)
    {
        $size = $this->getSize($value);
        if (!isset($parameters[0])) {
            return false;
        }
        if (isset($parameters[1])) {
            if ('' === $parameters[0]) {
                if ('' === $parameters[1]) {
                    return false;
                }
                return $size <= $parameters[1];
            }
            if ('' === $parameters[1]) {
                return $size >= $parameters[0];
            }
            return $size >= $parameters[0] && $size <= $parameters[1];
        }
        return '' === $parameters[0] ? false : ($size >= $parameters[0]);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateRegex($value, array $parameters)
    {
        return (bool)preg_match("#{$parameters[0]}#", $value);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateIn($value, array $parameters)
    {
        return in_array($value, $parameters, true);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateIp($value, array $parameters = [])
    {
        return false !== filter_var($value, FILTER_VALIDATE_IP);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateDateFormat($value, array $parameters)
    {
        return !(bool)date_parse_from_format($parameters[0], $value)['error_count'];
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateDateBefore($value, array $parameters)
    {
        return strtotime($value) < strtotime($parameters[0]);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateDateAfter($value, array $parameters)
    {
        return strtotime($value) > strtotime($parameters[0]);
    }

    /**
     * @param $value
     * @param array $parameters
     * @return bool
     */
    protected function validateJson($value, array $parameters)
    {
        return is_object(json_decode($value));
    }

    /**
     * @param $value
     * @return int
     */
    protected function getSize($value)
    {
        switch (true) {
            case is_array($value):
                return count($value);
            case false !== $temp = filter_var($value, FILTER_VALIDATE_INT):
                return $temp;
            case false !== $temp = filter_var($value, FILTER_VALIDATE_FLOAT):
                return $temp;
            default:
                return strlen($value);
        }
    }
}
