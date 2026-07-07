<?php

declare(strict_types=1);

namespace openvk\VKAPI\VKScript;

use openvk\VKAPI\Exceptions\APIErrorException;

/**
 * Recursive-descent parser for VKScript.
 *
 * Consumes the token stream from {@see Lexer} and produces an AST as nested associative
 * arrays (each carrying a "kind"). Syntax errors are reported as APIErrorException with
 * code 12 ("compilation failed").
 */
class Parser
{
    /** @var array<int, array{type: string, value: mixed, pos: int}> */
    private array $tokens;
    private int $pos = 0;

    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * @return array<int, array> list of statement nodes
     */
    public function parse(): array
    {
        $statements = [];
        while (!$this->isEof()) {
            $statements[] = $this->parseStatement();
        }

        return $statements;
    }

    /* ---------- token helpers ---------- */

    private function peek(): array
    {
        return $this->tokens[$this->pos];
    }

    private function isEof(): bool
    {
        return $this->peek()["type"] === "eof";
    }

    private function advance(): array
    {
        return $this->tokens[$this->pos++];
    }

    private function check(string $type, $value = null): bool
    {
        $tok = $this->peek();
        if ($tok["type"] !== $type) {
            return false;
        }

        return $value === null || $tok["value"] === $value;
    }

    private function accept(string $type, $value = null): bool
    {
        if ($this->check($type, $value)) {
            $this->pos++;
            return true;
        }

        return false;
    }

    private function expect(string $type, $value = null): array
    {
        if (!$this->check($type, $value)) {
            $got = $this->peek();
            $want = $value !== null ? "'$value'" : $type;
            $desc = $got["type"] === "eof" ? "end of script" : "'" . $got["value"] . "'";
            throw new APIErrorException("Syntax error: expected $want but got $desc", 12);
        }

        return $this->advance();
    }

    private function acceptSemicolons(): void
    {
        while ($this->accept("punc", ";")) {
            // VKScript treats stray semicolons as empty statements.
        }
    }

    /* ---------- statements ---------- */

    private function parseStatement(): array
    {
        $tok = $this->peek();

        if ($this->check("punc", ";")) {
            $this->advance();
            return ["kind" => "empty"];
        }

        if ($tok["type"] === "keyword") {
            switch ($tok["value"]) {
                case "var":
                    return $this->parseVar();
                case "if":
                    return $this->parseIf();
                case "while":
                    return $this->parseWhile();
                case "do":
                    return $this->parseDoWhile();
                case "for":
                    return $this->parseFor();
                case "break":
                    $this->advance();
                    $this->acceptSemicolons();
                    return ["kind" => "break"];
                case "continue":
                    $this->advance();
                    $this->acceptSemicolons();
                    return ["kind" => "continue"];
                case "return":
                    return $this->parseReturn();
            }
        }

        if ($this->check("punc", "{")) {
            return $this->parseBlock();
        }

        $expr = $this->parseExpression();
        $this->acceptSemicolons();
        return ["kind" => "expr", "expr" => $expr];
    }

    private function parseBlock(): array
    {
        $this->expect("punc", "{");
        $body = [];
        while (!$this->check("punc", "}") && !$this->isEof()) {
            $body[] = $this->parseStatement();
        }
        $this->expect("punc", "}");

        return ["kind" => "block", "body" => $body];
    }

    private function parseVar(): array
    {
        $node = $this->parseVarDeclList();
        $this->acceptSemicolons();
        return $node;
    }

    /** Parses `var a = .., b = ..` WITHOUT consuming a trailing semicolon (used by `for` init). */
    private function parseVarDeclList(): array
    {
        $this->expect("keyword", "var");
        $decls = [];

        do {
            $name = $this->expect("name")["value"];
            $init = null;
            if ($this->accept("op", "=")) {
                $init = $this->parseAssignment();
            }
            $decls[] = ["name" => $name, "init" => $init];
        } while ($this->accept("punc", ","));

        return ["kind" => "var", "decls" => $decls];
    }

    private function parseIf(): array
    {
        $this->expect("keyword", "if");
        $this->expect("punc", "(");
        $cond = $this->parseExpression();
        $this->expect("punc", ")");
        $then = $this->parseStatement();
        $else = null;
        if ($this->accept("keyword", "else")) {
            $else = $this->parseStatement();
        }

        return ["kind" => "if", "cond" => $cond, "then" => $then, "else" => $else];
    }

    private function parseWhile(): array
    {
        $this->expect("keyword", "while");
        $this->expect("punc", "(");
        $cond = $this->parseExpression();
        $this->expect("punc", ")");
        $body = $this->parseStatement();

        return ["kind" => "while", "cond" => $cond, "body" => $body];
    }

    private function parseDoWhile(): array
    {
        $this->expect("keyword", "do");
        $body = $this->parseStatement();
        $this->expect("keyword", "while");
        $this->expect("punc", "(");
        $cond = $this->parseExpression();
        $this->expect("punc", ")");
        $this->acceptSemicolons();

        return ["kind" => "dowhile", "cond" => $cond, "body" => $body];
    }

    private function parseFor(): array
    {
        $this->expect("keyword", "for");
        $this->expect("punc", "(");

        $init = null;
        if (!$this->check("punc", ";")) {
            if ($this->check("keyword", "var")) {
                $init = $this->parseVarDeclList();
            } else {
                $init = ["kind" => "expr", "expr" => $this->parseExpression()];
            }
        }
        $this->expect("punc", ";");

        $cond = $this->check("punc", ";") ? null : $this->parseExpression();
        $this->expect("punc", ";");

        $update = $this->check("punc", ")") ? null : $this->parseExpression();
        $this->expect("punc", ")");

        $body = $this->parseStatement();

        return ["kind" => "for", "init" => $init, "cond" => $cond, "update" => $update, "body" => $body];
    }

    private function parseReturn(): array
    {
        $this->expect("keyword", "return");
        $value = null;
        if (!$this->check("punc", ";") && !$this->check("punc", "}") && !$this->isEof()) {
            $value = $this->parseExpression();
        }
        $this->acceptSemicolons();

        return ["kind" => "return", "value" => $value];
    }

    /* ---------- expressions ---------- */

    private function parseExpression(): array
    {
        return $this->parseAssignment();
    }

    private function parseAssignment(): array
    {
        $left = $this->parseLogicalOr();

        if ($this->accept("op", "=")) {
            if (!in_array($left["kind"], ["name", "member", "index"], true)) {
                throw new APIErrorException("Syntax error: invalid assignment target", 12);
            }
            $value = $this->parseAssignment();
            return ["kind" => "assign", "target" => $left, "value" => $value];
        }

        return $left;
    }

    private function parseLogicalOr(): array
    {
        $left = $this->parseLogicalAnd();
        while ($this->check("op", "||")) {
            $this->advance();
            $right = $this->parseLogicalAnd();
            $left  = ["kind" => "logical", "op" => "||", "left" => $left, "right" => $right];
        }

        return $left;
    }

    private function parseLogicalAnd(): array
    {
        $left = $this->parseEquality();
        while ($this->check("op", "&&")) {
            $this->advance();
            $right = $this->parseEquality();
            $left  = ["kind" => "logical", "op" => "&&", "left" => $left, "right" => $right];
        }

        return $left;
    }

    private function parseEquality(): array
    {
        $left = $this->parseRelational();
        while ($this->check("op", "==") || $this->check("op", "!=")) {
            $op    = $this->advance()["value"];
            $right = $this->parseRelational();
            $left  = ["kind" => "binary", "op" => $op, "left" => $left, "right" => $right];
        }

        return $left;
    }

    private function parseRelational(): array
    {
        $left = $this->parseAdditive();
        while ($this->check("op", "<") || $this->check("op", ">") || $this->check("op", "<=") || $this->check("op", ">=")) {
            $op    = $this->advance()["value"];
            $right = $this->parseAdditive();
            $left  = ["kind" => "binary", "op" => $op, "left" => $left, "right" => $right];
        }

        return $left;
    }

    private function parseAdditive(): array
    {
        $left = $this->parseMultiplicative();
        while ($this->check("op", "+") || $this->check("op", "-")) {
            $op    = $this->advance()["value"];
            $right = $this->parseMultiplicative();
            $left  = ["kind" => "binary", "op" => $op, "left" => $left, "right" => $right];
        }

        return $left;
    }

    private function parseMultiplicative(): array
    {
        $left = $this->parseUnary();
        while ($this->check("op", "*") || $this->check("op", "/") || $this->check("op", "%")) {
            $op    = $this->advance()["value"];
            $right = $this->parseUnary();
            $left  = ["kind" => "binary", "op" => $op, "left" => $left, "right" => $right];
        }

        return $left;
    }

    private function parseUnary(): array
    {
        if ($this->check("op", "-") || $this->check("op", "!")) {
            $op      = $this->advance()["value"];
            $operand = $this->parseUnary();
            return ["kind" => "unary", "op" => $op, "operand" => $operand];
        }

        return $this->parsePostfix();
    }

    private function parsePostfix(): array
    {
        $expr = $this->parsePrimary();

        while (true) {
            if ($this->accept("punc", ".")) {
                $name = $this->expectName();
                $expr = ["kind" => "member", "object" => $expr, "name" => $name];
            } elseif ($this->accept("punc", "[")) {
                $index = $this->parseExpression();
                $this->expect("punc", "]");
                $expr = ["kind" => "index", "object" => $expr, "index" => $index];
            } elseif ($this->accept("punc", "(")) {
                $args = $this->parseArguments();
                $expr = ["kind" => "call", "callee" => $expr, "args" => $args];
            } elseif ($this->accept("punc", "@")) {
                if ($this->accept("punc", ".")) {
                    $name = $this->expectName();
                    $expr = ["kind" => "filter", "object" => $expr, "mode" => "member", "name" => $name];
                } elseif ($this->accept("punc", "[")) {
                    $index = $this->parseExpression();
                    $this->expect("punc", "]");
                    $expr = ["kind" => "filter", "object" => $expr, "mode" => "index", "index" => $index];
                } else {
                    throw new APIErrorException("Syntax error: '@' must be followed by '.' or '['", 12);
                }
            } else {
                break;
            }
        }

        return $expr;
    }

    private function parseArguments(): array
    {
        $args = [];
        if (!$this->check("punc", ")")) {
            do {
                $args[] = $this->parseAssignment();
            } while ($this->accept("punc", ","));
        }
        $this->expect("punc", ")");

        return $args;
    }

    private function parsePrimary(): array
    {
        $tok = $this->peek();

        switch ($tok["type"]) {
            case "num":
                $this->advance();
                return ["kind" => "num", "value" => $tok["value"]];
            case "str":
                $this->advance();
                return ["kind" => "str", "value" => $tok["value"]];
            case "name":
                $this->advance();
                return ["kind" => "name", "name" => $tok["value"]];
            case "keyword":
                if ($tok["value"] === "true") {
                    $this->advance();
                    return ["kind" => "bool", "value" => true];
                }
                if ($tok["value"] === "false") {
                    $this->advance();
                    return ["kind" => "bool", "value" => false];
                }
                if ($tok["value"] === "null") {
                    $this->advance();
                    return ["kind" => "null"];
                }
                break;
            case "punc":
                if ($tok["value"] === "(") {
                    $this->advance();
                    $expr = $this->parseExpression();
                    $this->expect("punc", ")");
                    return $expr;
                }
                if ($tok["value"] === "[") {
                    return $this->parseArrayLiteral();
                }
                if ($tok["value"] === "{") {
                    return $this->parseObjectLiteral();
                }
                break;
        }

        $desc = $tok["type"] === "eof" ? "end of script" : "'" . $tok["value"] . "'";
        throw new APIErrorException("Syntax error: unexpected $desc", 12);
    }

    private function parseArrayLiteral(): array
    {
        $this->expect("punc", "[");
        $elements = [];
        if (!$this->check("punc", "]")) {
            do {
                if ($this->check("punc", "]")) {
                    break; // trailing comma
                }
                $elements[] = $this->parseAssignment();
            } while ($this->accept("punc", ","));
        }
        $this->expect("punc", "]");

        return ["kind" => "array", "elements" => $elements];
    }

    private function parseObjectLiteral(): array
    {
        $this->expect("punc", "{");
        $props = [];
        if (!$this->check("punc", "}")) {
            do {
                if ($this->check("punc", "}")) {
                    break; // trailing comma
                }

                $keyTok = $this->peek();
                if (in_array($keyTok["type"], ["str", "name", "keyword"], true)) {
                    $key = (string) $keyTok["value"];
                    $this->advance();
                } elseif ($keyTok["type"] === "num") {
                    $key = (string) $keyTok["value"];
                    $this->advance();
                } else {
                    throw new APIErrorException("Syntax error: invalid object key", 12);
                }

                $this->expect("punc", ":");
                $value  = $this->parseAssignment();
                $props[] = ["key" => $key, "value" => $value];
            } while ($this->accept("punc", ","));
        }
        $this->expect("punc", "}");

        return ["kind" => "object", "props" => $props];
    }

    /** Allows keywords (e.g. `do`, `for`) to be used as member/property names, like VK. */
    private function expectName(): string
    {
        $tok = $this->peek();
        if ($tok["type"] === "name" || $tok["type"] === "keyword") {
            $this->advance();
            return (string) $tok["value"];
        }

        throw new APIErrorException("Syntax error: expected a name", 12);
    }
}
