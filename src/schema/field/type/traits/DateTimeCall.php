<?php

namespace lx\model\schema\field\type\traits;

use lx\model\schema\field\value\DateIntervalValue;
use lx\model\schema\field\value\DateTimeValue;
use lx\model\schema\field\value\DateValue;
use lx\model\schema\field\value\TimeValue;

trait DateTimeCall
{
    /**
     * @return mixed
     */
    public function __call(string $methodName, array $arguments = [])
    {
        $instructions = $this->getCallInstructions();
        $goalName = $instructions['goalName'];

        if ($arguments[0] instanceof DateTimeValue
            || $arguments[0] instanceof DateValue
            || $arguments[0] instanceof TimeValue
        ) {
            $arguments[0] = $arguments[0]->getDateTime();
        } elseif ($arguments[0] instanceof DateIntervalValue) {
            $arguments[0] = $arguments[0]->getDateInterval();
        }

        if (method_exists(DateTime::class, $methodName)) {
            if (in_array($methodName, $instructions['getters'])) {
                if ($this->isNull()) {
                    return null;
                }
                $result = empty($arguments)
                    ? call_user_func([$this->$goalName, $methodName])
                    : call_user_func_array([$this->$goalName, $methodName], $arguments);
                if ($result === false) {
                    return null;
                }
                return $result;
            }

            if (!$this->$goalName) {
                $this->$goalName = new DateTime();
            }

            if (in_array($methodName, $instructions['setters'])) {
                $result = call_user_func_array([$this->$goalName, $methodName], $arguments);
                if ($result === false) {
                    return null;
                }
                return $this;
            }
        }

        //TODO exception
        return null;
    }
}
