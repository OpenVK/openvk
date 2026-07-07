<?php

declare(strict_types=1);

namespace openvk\VKAPI\VKScript;

use openvk\VKAPI\Exceptions\APIErrorException;

/** Internal control-flow signals (not surfaced to the API caller). */
class BreakSignal extends \Exception {}
class ContinueSignal extends \Exception {}
class ReturnSignal extends \Exception
{
    public $value;
    public function __construct($value)
    {
        parent::__construct();
        $this->value = $value;
    }
}

/**
 * Tree-walking interpreter for VKScript, used by the `execute` API method.
 *
 * Evaluates the AST produced by {@see Parser}. API calls (`API.object.method({...})`) are
 * delegated to a callback supplied by the caller; a maximum of 25 are allowed per run.
 * Failed API calls are collected into {@see getExecuteErrors()} (the script keeps running,
 * the call evaluating to false), mirroring VK's `execute_errors` field. Runtime problems
 * are reported as APIErrorException with code 13.
 */
class Interpreter
{
    private const MAX_API_CALLS = 25;
    private const MAX_OPERATIONS = 5000000;

    private const MUTATING_METHODS = ["push", "pop", "shift", "unshift", "splice"];

    /** @var callable fn(string $object, string $method, array $params): mixed */
    private $apiCallback;

    /** @var array<string, mixed> */
    private array $vars = [];

    /** @var array<int, array> */
    private array $executeErrors = [];

    private int $apiCalls = 0;
    private int $operations = 0;

    public function __construct(callable $apiCallback, array $args = [])
    {
        $this->apiCallback  = $apiCallback;
        $this->vars["Args"] = $args;
    }

    /** @return array<int, array> the collected execute_errors (empty when none occurred) */
    public function getExecuteErrors(): array
    {
        return $this->executeErrors;
    }

    /**
     * @param array<int, array> $ast statement list from {@see Parser::parse()}
     * @return mixed value of the script's `return`, or null
     */
    public function run(array $ast)
    {
        try {
            $this->execBlock($ast);
        } catch (ReturnSignal $ret) {
            return $this->export($ret->value);
        } catch (BreakSignal | ContinueSignal) {
            throw new APIErrorException("Runtime error: 'break'/'continue' outside of a loop", 13);
        }

        return null;
    }

    /* ---------- statements ---------- */

    /** @param array<int, array> $statements */
    private function execBlock(array $statements): void
    {
        foreach ($statements as $stmt) {
            $this->exec($stmt);
        }
    }

    private function exec(array $node): void
    {
        $this->tick();

        switch ($node["kind"]) {
            case "var":
                foreach ($node["decls"] as $decl) {
                    $this->vars[$decl["name"]] = $decl["init"] === null ? null : $this->eval($decl["init"]);
                }
                return;

            case "expr":
                $this->eval($node["expr"]);
                return;

            case "block":
                $this->execBlock($node["body"]);
                return;

            case "empty":
                return;

            case "if":
                if ($this->truthy($this->eval($node["cond"]))) {
                    $this->exec($node["then"]);
                } elseif ($node["else"] !== null) {
                    $this->exec($node["else"]);
                }
                return;

            case "while":
                while ($this->truthy($this->eval($node["cond"]))) {
                    try {
                        $this->exec($node["body"]);
                    } catch (BreakSignal) {
                        break;
                    } catch (ContinueSignal) {
                        continue;
                    }
                }
                return;

            case "dowhile":
                do {
                    try {
                        $this->exec($node["body"]);
                    } catch (BreakSignal) {
                        break;
                    } catch (ContinueSignal) {
                        continue;
                    }
                } while ($this->truthy($this->eval($node["cond"])));
                return;

            case "for":
                if ($node["init"] !== null) {
                    $this->exec($node["init"]);
                }
                while ($node["cond"] === null || $this->truthy($this->eval($node["cond"]))) {
                    try {
                        $this->exec($node["body"]);
                    } catch (BreakSignal) {
                        break;
                    } catch (ContinueSignal) {
                        // fall through to update
                    }
                    if ($node["update"] !== null) {
                        $this->eval($node["update"]);
                    }
                }
                return;

            case "break":
                throw new BreakSignal();

            case "continue":
                throw new ContinueSignal();

            case "return":
                throw new ReturnSignal($node["value"] === null ? null : $this->eval($node["value"]));
        }

        throw new APIErrorException("Runtime error: unknown statement", 13);
    }

    /* ---------- expressions ---------- */

    private function eval(array $node)
    {
        $this->tick();

        switch ($node["kind"]) {
            case "num":
            case "str":
            case "bool":
                return $node["value"];
            case "null":
                return null;

            case "name":
                if (!array_key_exists($node["name"], $this->vars)) {
                    throw new APIErrorException("Runtime error: unknown variable '" . $node["name"] . "'", 13);
                }
                return $this->vars[$node["name"]];

            case "array":
                $out = [];
                foreach ($node["elements"] as $el) {
                    $out[] = $this->eval($el);
                }
                return $out;

            case "object":
                $out = [];
                foreach ($node["props"] as $prop) {
                    $out[$prop["key"]] = $this->eval($prop["value"]);
                }
                return $out;

            case "assign":
                $value = $this->eval($node["value"]);
                $ref   = &$this->evalRef($node["target"]);
                $ref   = $value;
                return $value;

            case "unary":
                return $this->evalUnary($node);

            case "logical":
                $left = $this->eval($node["left"]);
                if ($node["op"] === "&&") {
                    return $this->truthy($left) ? $this->eval($node["right"]) : $left;
                }
                return $this->truthy($left) ? $left : $this->eval($node["right"]);

            case "binary":
                return $this->evalBinary($node["op"], $this->eval($node["left"]), $this->eval($node["right"]));

            case "member":
                return $this->getMember($this->eval($node["object"]), $node["name"]);

            case "index":
                return $this->getIndex($this->eval($node["object"]), $this->eval($node["index"]));

            case "filter":
                return $this->evalFilter($node);

            case "call":
                return $this->evalCall($node);
        }

        throw new APIErrorException("Runtime error: unknown expression", 13);
    }

    private function evalUnary(array $node)
    {
        $value = $this->eval($node["operand"]);
        if ($node["op"] === "!") {
            return !$this->truthy($value);
        }

        // "-"
        return -$this->toNumber($value);
    }

    private function evalBinary(string $op, $left, $right)
    {
        switch ($op) {
            case "+":
                if (is_string($left) || is_string($right)) {
                    return $this->toString($left) . $this->toString($right);
                }
                if (is_array($left) || is_object($left) || is_array($right) || is_object($right)) {
                    // list + list => concatenation; object + object => shallow merge (VK semantics).
                    if (is_array($left) && array_is_list($left) && is_array($right) && array_is_list($right)) {
                        return array_merge($left, $right);
                    }
                    $out = $this->toAssoc($left);
                    foreach ($this->toAssoc($right) as $k => $v) {
                        $out[$k] = $v;
                    }
                    return $out;
                }
                return $this->toNumber($left) + $this->toNumber($right);
            case "-":
                return $this->toNumber($left) - $this->toNumber($right);
            case "*":
                return $this->toNumber($left) * $this->toNumber($right);
            case "/":
                $d = $this->toNumber($right);
                if ($d == 0) {
                    throw new APIErrorException("Runtime error: division by zero", 13);
                }
                return $this->toNumber($left) / $d;
            case "%":
                $d = (int) $this->toNumber($right);
                if ($d === 0) {
                    throw new APIErrorException("Runtime error: modulo by zero", 13);
                }
                return (int) $this->toNumber($left) % $d;
            case "==":
                return $this->looseEquals($left, $right);
            case "!=":
                return !$this->looseEquals($left, $right);
            case "<":
                return $this->compare($left, $right) < 0;
            case ">":
                return $this->compare($left, $right) > 0;
            case "<=":
                return $this->compare($left, $right) <= 0;
            case ">=":
                return $this->compare($left, $right) >= 0;
        }

        throw new APIErrorException("Runtime error: unknown operator '$op'", 13);
    }

    private function evalFilter(array $node)
    {
        $value    = $this->eval($node["object"]);
        $elements = $this->toList($value);
        $out      = [];

        foreach ($elements as $el) {
            if ($node["mode"] === "member") {
                $out[] = $this->getMember($el, $node["name"]);
            } else {
                $out[] = $this->getIndex($el, $this->eval($node["index"]));
            }
        }

        return $out;
    }

    private function evalCall(array $node)
    {
        $callee = $node["callee"];

        // API.object.method({ ... })
        $api = $this->matchApiCallee($callee);
        if ($api !== null) {
            return $this->callApi($api[0], $api[1], $node["args"]);
        }

        // receiver.method(...) — built-in string/array methods
        if ($callee["kind"] === "member") {
            return $this->callMethod($callee, $node["args"]);
        }

        // bare function — global built-ins
        if ($callee["kind"] === "name") {
            return $this->callGlobal($callee["name"], $this->evalArgs($node["args"]));
        }

        throw new APIErrorException("Runtime error: expression is not callable", 13);
    }

    /** @return array{0: string, 1: string}|null [section, method] ; section is "" for legacy unprefixed methods */
    private function matchApiCallee(array $callee): ?array
    {
        if ($callee["kind"] !== "member") {
            return null;
        }

        $object = $callee["object"];

        // API.method(...) — legacy method with no section
        if ($object["kind"] === "name" && $object["name"] === "API") {
            return ["", $callee["name"]];
        }

        // API.section.method(...)
        if (
            $object["kind"] === "member"
            && $object["object"]["kind"] === "name"
            && $object["object"]["name"] === "API"
        ) {
            return [$object["name"], $callee["name"]];
        }

        return null;
    }

    private function callApi(string $object, string $method, array $argNodes)
    {
        if (++$this->apiCalls > self::MAX_API_CALLS) {
            throw new APIErrorException("Runtime error: too many API calls in execute (max " . self::MAX_API_CALLS . ")", 13);
        }

        $params = [];
        if (count($argNodes) > 0) {
            $arg = $this->eval($argNodes[0]);
            if (is_array($arg)) {
                $params = $arg;
            } elseif (is_object($arg)) {
                $params = (array) $arg;
            }
        }

        // Flatten params to scalar request values the handlers expect.
        $request = [];
        foreach ($params as $key => $value) {
            $request[$key] = $this->toRequestValue($value);
        }

        $label = $object === "" ? $method : "$object.$method";

        try {
            return ($this->apiCallback)($object, $method, $request);
        } catch (APIErrorException $ex) {
            $this->executeErrors[] = [
                "method"     => $label,
                "error_code" => $ex->getCode(),
                "error_msg"  => $ex->getMessage(),
            ];
            return false;
        }
    }

    private function callMethod(array $callee, array $argNodes)
    {
        $method    = $callee["name"];
        $args      = $this->evalArgs($argNodes);
        $mutating  = in_array($method, self::MUTATING_METHODS, true);
        $assignable = in_array($callee["object"]["kind"], ["name", "member", "index"], true);

        if ($mutating && $assignable) {
            $receiver = &$this->evalRef($callee["object"]);
        } else {
            $receiver = $this->eval($callee["object"]);
        }

        if (is_array($receiver)) {
            return $this->arrayMethod($receiver, $method, $args);
        }
        if (is_string($receiver)) {
            return $this->stringMethod($receiver, $method, $args);
        }

        throw new APIErrorException("Runtime error: unknown method '$method'", 13);
    }

    private function callGlobal(string $name, array $args)
    {
        switch ($name) {
            case "parseInt":
                $radix = isset($args[1]) ? (int) $args[1] : 10;
                return intval($this->toString($args[0] ?? ""), $radix ?: 10);
            case "parseDouble":
            case "parseFloat":
                return (float) $this->toNumber($args[0] ?? 0);
        }

        throw new APIErrorException("Runtime error: unknown function '$name'", 13);
    }

    /** @param mixed $receiver passed by reference so mutating methods persist */
    private function arrayMethod(&$receiver, string $method, array $args)
    {
        switch ($method) {
            case "push":
                foreach ($args as $a) {
                    $receiver[] = $a;
                }
                return count($receiver);
            case "pop":
                return array_pop($receiver);
            case "shift":
                return array_shift($receiver);
            case "unshift":
                array_unshift($receiver, ...$args);
                return count($receiver);
            case "slice":
                $start  = (int) ($args[0] ?? 0);
                $length = isset($args[1]) ? (int) $args[1] - $start : null;
                if ($length !== null && $length < 0) {
                    $length = 0;
                }
                return array_values(array_slice($receiver, $start, $length));
            case "splice":
                $start   = (int) ($args[0] ?? 0);
                $delete  = isset($args[1]) ? (int) $args[1] : count($receiver) - $start;
                $items   = array_slice($args, 2);
                $removed = array_splice($receiver, $start, $delete, $items);
                return array_values($removed);
            case "indexOf":
                $idx = array_search($args[0] ?? null, $receiver, true);
                return $idx === false ? -1 : $idx;
        }

        throw new APIErrorException("Runtime error: unknown array method '$method'", 13);
    }

    private function stringMethod(string $receiver, string $method, array $args)
    {
        switch ($method) {
            case "substr":
                $start  = (int) ($args[0] ?? 0);
                $length = isset($args[1]) ? (int) $args[1] : null;
                return $length === null ? substr($receiver, $start) : substr($receiver, $start, $length);
            case "split":
                $sep = $this->toString($args[0] ?? "");
                return $sep === "" ? ($receiver === "" ? [] : str_split($receiver)) : explode($sep, $receiver);
            case "indexOf":
                $pos = strpos($receiver, $this->toString($args[0] ?? ""));
                return $pos === false ? -1 : $pos;
        }

        throw new APIErrorException("Runtime error: unknown string method '$method'", 13);
    }

    /* ---------- member / index access ---------- */

    private function getMember($obj, string $name)
    {
        if ($name === "length") {
            if (is_string($obj)) {
                return strlen($obj);
            }
            if (is_array($obj)) {
                return count($obj);
            }
        }

        if (is_array($obj)) {
            // Distribute member access over a plain list (covers `@`-style chains).
            if (array_is_list($obj) && $name !== "length") {
                $out = [];
                foreach ($obj as $el) {
                    $out[] = $this->getMember($el, $name);
                }
                return $out;
            }
            return $obj[$name] ?? null;
        }

        if (is_object($obj)) {
            return $obj->{$name} ?? null;
        }

        return null;
    }

    private function getIndex($obj, $index)
    {
        if (is_array($obj)) {
            return $obj[$this->toKey($index)] ?? null;
        }
        if (is_object($obj)) {
            return $obj->{(string) $index} ?? null;
        }
        if (is_string($obj)) {
            $i = (int) $index;
            return $obj[$i] ?? null;
        }

        return null;
    }

    /** Returns a reference to the storage slot named by an assignable node (auto-vivifies). */
    private function &evalRef(array $node)
    {
        if ($node["kind"] === "name") {
            if (!array_key_exists($node["name"], $this->vars)) {
                $this->vars[$node["name"]] = null;
            }
            return $this->vars[$node["name"]];
        }

        if ($node["kind"] === "member" || $node["kind"] === "index") {
            $key    = $node["kind"] === "member" ? $node["name"] : $this->toKey($this->eval($node["index"]));
            $parent = &$this->evalRef($node["object"]);

            if (is_object($parent)) {
                if (!isset($parent->{$key})) {
                    $parent->{$key} = null;
                }
                $ref = &$parent->{$key};
                return $ref;
            }

            if (!is_array($parent)) {
                $parent = [];
            }
            if (!array_key_exists($key, $parent)) {
                $parent[$key] = null;
            }
            $ref = &$parent[$key];
            return $ref;
        }

        throw new APIErrorException("Runtime error: invalid assignment target", 13);
    }

    /* ---------- helpers ---------- */

    private function tick(): void
    {
        if (++$this->operations > self::MAX_OPERATIONS) {
            throw new APIErrorException("Runtime error: script exceeded the operation limit", 13);
        }
    }

    /** @return array<int, mixed> */
    private function evalArgs(array $argNodes): array
    {
        $out = [];
        foreach ($argNodes as $node) {
            $out[] = $this->eval($node);
        }
        return $out;
    }

    /** @return array<string, mixed> object-like value coerced to an associative array */
    private function toAssoc($value): array
    {
        if (is_object($value)) {
            return (array) $value;
        }
        if (is_array($value)) {
            return $value;
        }
        return [];
    }

    /** @return array<int, mixed> */
    private function toList($value): array
    {
        if (is_array($value)) {
            return array_is_list($value) ? $value : [$value];
        }
        if ($value === null) {
            return [];
        }
        return [$value];
    }

    private function truthy($v): bool
    {
        if (is_array($v)) {
            return count($v) > 0;
        }
        if (is_string($v)) {
            return $v !== "" && $v !== "0";
        }
        if (is_int($v) || is_float($v)) {
            return $v != 0;
        }
        return (bool) $v;
    }

    private function toNumber($v)
    {
        if (is_int($v) || is_float($v)) {
            return $v;
        }
        if (is_bool($v)) {
            return $v ? 1 : 0;
        }
        if (is_string($v)) {
            return is_numeric($v) ? $v + 0 : 0;
        }
        return 0;
    }

    private function toString($v): string
    {
        if (is_string($v)) {
            return $v;
        }
        if (is_bool($v)) {
            return $v ? "true" : "false";
        }
        if ($v === null) {
            return "";
        }
        if (is_array($v) || is_object($v)) {
            return json_encode($v);
        }
        return (string) $v;
    }

    private function toKey($index)
    {
        if (is_int($index)) {
            return $index;
        }
        if (is_float($index) || is_bool($index)) {
            return (int) $index;
        }
        if (is_string($index) && preg_match('/^-?\d+$/', $index)) {
            return (int) $index;
        }
        return (string) $index;
    }

    /** Coerce a VKScript value into something an API handler accepts as a request param. */
    private function toRequestValue($value)
    {
        if (is_bool($value)) {
            return $value ? "1" : "0";
        }
        if (is_array($value)) {
            // VK serialises array params as comma-separated lists (e.g. user_ids).
            if (array_is_list($value)) {
                return implode(",", array_map([$this, "toRequestScalar"], $value));
            }
            return json_encode($value);
        }
        if (is_object($value)) {
            return json_encode($value);
        }
        if ($value === null) {
            return "";
        }
        return $value;
    }

    private function toRequestScalar($value): string
    {
        if (is_bool($value)) {
            return $value ? "1" : "0";
        }
        if ($value === null) {
            return "";
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        return (string) $value;
    }

    private function looseEquals($a, $b): bool
    {
        if (is_string($a) && is_string($b)) {
            return $a === $b;
        }
        if ((is_int($a) || is_float($a) || is_bool($a)) && (is_int($b) || is_float($b) || is_bool($b))) {
            return $this->toNumber($a) == $this->toNumber($b);
        }
        if ($a === null || $b === null) {
            return $a === $b;
        }
        return $a == $b;
    }

    private function compare($a, $b): int
    {
        if (is_string($a) && is_string($b)) {
            return strcmp($a, $b);
        }
        return $this->toNumber($a) <=> $this->toNumber($b);
    }

    /** Normalise associative arrays produced by object literals into stdClass for JSON output. */
    private function export($value)
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                return array_map([$this, "export"], $value);
            }
            $obj = new \stdClass();
            foreach ($value as $k => $v) {
                $obj->{$k} = $this->export($v);
            }
            return $obj;
        }

        return $value;
    }
}
