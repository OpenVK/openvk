<?php

declare(strict_types=1);

namespace openvk\VKAPI\VKScript;

use openvk\VKAPI\Exceptions\APIErrorException;

/**
 * Tokenizer for VKScript (the JavaScript subset executed by the `execute` API method).
 *
 * Produces a flat list of tokens consumed by {@see Parser}. Lexical errors are reported
 * as APIErrorException with code 12 ("compilation failed"), matching VK semantics.
 */
class Lexer
{
    public const KEYWORDS = [
        "var", "if", "else", "while", "for", "do",
        "break", "continue", "return", "true", "false", "null",
        "typeof", "delete", "in",
    ];

    /** Multi-character operators are tested before single-character ones. */
    private const OPERATORS = [
        ">>>=",
        "===", "!==", ">>>", "<<=", ">>=",
        "++", "--", "+=", "-=", "*=", "/=", "%=", "&=", "|=", "^=",
        "==", "!=", "<=", ">=", "&&", "||", "<<", ">>",
        "+", "-", "*", "/", "%", "<", ">", "!", "=",
        "&", "|", "^", "~", "?", ":",
    ];

    private string $code;
    private int $pos = 0;
    private int $len;

    public function __construct(string $code)
    {
        $this->code = $code;
        $this->len  = strlen($code);
    }

    /**
     * @return array<int, array{type: string, value: mixed, pos: int}>
     */
    public function tokenize(): array
    {
        $tokens = [];

        while ($this->pos < $this->len) {
            $this->skipTrivia();
            if ($this->pos >= $this->len) {
                break;
            }

            $start = $this->pos;
            $ch    = $this->code[$this->pos];

            if (ctype_digit($ch) || ($ch === "." && $this->pos + 1 < $this->len && ctype_digit($this->code[$this->pos + 1]))) {
                $tokens[] = $this->readNumber();
            } elseif ($ch === "\"" || $ch === "'") {
                $tokens[] = $this->readString($ch);
            } elseif (ctype_alpha($ch) || $ch === "_" || $ch === "$") {
                $tokens[] = $this->readName();
            } elseif (strpos(".,;()[]{}@", $ch) !== false) {
                $this->pos++;
                $tokens[] = ["type" => "punc", "value" => $ch, "pos" => $start];
            } else {
                $tokens[] = $this->readOperator();
            }
        }

        $tokens[] = ["type" => "eof", "value" => null, "pos" => $this->pos];

        return $tokens;
    }

    private function skipTrivia(): void
    {
        while ($this->pos < $this->len) {
            $ch = $this->code[$this->pos];

            if (ctype_space($ch)) {
                $this->pos++;
                continue;
            }

            if ($ch === "/" && $this->pos + 1 < $this->len) {
                $next = $this->code[$this->pos + 1];
                if ($next === "/") {
                    $this->pos += 2;
                    while ($this->pos < $this->len && $this->code[$this->pos] !== "\n") {
                        $this->pos++;
                    }
                    continue;
                }

                if ($next === "*") {
                    $this->pos += 2;
                    while ($this->pos < $this->len && !($this->code[$this->pos] === "*" && ($this->code[$this->pos + 1] ?? "") === "/")) {
                        $this->pos++;
                    }
                    if ($this->pos >= $this->len) {
                        throw new APIErrorException("Unterminated comment in script", 12);
                    }
                    $this->pos += 2;
                    continue;
                }
            }

            break;
        }
    }

    private function readNumber(): array
    {
        $start = $this->pos;

        // Check hex: 0x...
        if ($this->code[$this->pos] === '0' && $this->pos + 1 < $this->len) {
            $next = strtolower($this->code[$this->pos + 1]);
            if ($next === 'x') {
                $this->pos += 2;
                $hexStart = $this->pos;
                while ($this->pos < $this->len && ctype_xdigit($this->code[$this->pos])) {
                    $this->pos++;
                }
                if ($this->pos === $hexStart) {
                    throw new APIErrorException("Invalid hexadecimal number", 12);
                }
                $hexStr = substr($this->code, $hexStart, $this->pos - $hexStart);
                return [
                    "type"  => "num",
                    "value" => hexdec($hexStr),
                    "pos"   => $start,
                ];
            } elseif ($next === 'b') {
                $this->pos += 2;
                $binStart = $this->pos;
                while ($this->pos < $this->len && ($this->code[$this->pos] === '0' || $this->code[$this->pos] === '1')) {
                    $this->pos++;
                }
                if ($this->pos === $binStart) {
                    throw new APIErrorException("Invalid binary number", 12);
                }
                $binStr = substr($this->code, $binStart, $this->pos - $binStart);
                return [
                    "type"  => "num",
                    "value" => bindec($binStr),
                    "pos"   => $start,
                ];
            } elseif ($next === 'o') {
                $this->pos += 2;
                $octStart = $this->pos;
                while ($this->pos < $this->len && $this->code[$this->pos] >= '0' && $this->code[$this->pos] <= '7') {
                    $this->pos++;
                }
                if ($this->pos === $octStart) {
                    throw new APIErrorException("Invalid octal number", 12);
                }
                $octStr = substr($this->code, $octStart, $this->pos - $octStart);
                return [
                    "type"  => "num",
                    "value" => octdec($octStr),
                    "pos"   => $start,
                ];
            }
        }

        $hasDot = false;
        $hasExp = false;

        while ($this->pos < $this->len) {
            $ch = $this->code[$this->pos];
            if (ctype_digit($ch)) {
                $this->pos++;
            } elseif ($ch === "." && !$hasDot && !$hasExp) {
                $hasDot = true;
                $this->pos++;
            } elseif (($ch === "e" || $ch === "E") && !$hasExp) {
                $hasExp = true;
                $hasDot = true;
                $this->pos++;
                if ($this->pos < $this->len && ($this->code[$this->pos] === '+' || $this->code[$this->pos] === '-')) {
                    $this->pos++;
                }
            } else {
                break;
            }
        }

        $raw = substr($this->code, $start, $this->pos - $start);

        return [
            "type"  => "num",
            "value" => ($hasDot || $hasExp) ? (float) $raw : (int) $raw,
            "pos"   => $start,
        ];
    }

    private function readString(string $quote): array
    {
        $start = $this->pos;
        $this->pos++; // opening quote
        $buf = "";

        while ($this->pos < $this->len) {
            $ch = $this->code[$this->pos];

            if ($ch === "\\") {
                $next = $this->code[$this->pos + 1] ?? "";
                if ($next === "u" && $this->pos + 5 < $this->len && ctype_xdigit(substr($this->code, $this->pos + 2, 4))) {
                    $hex = substr($this->code, $this->pos + 2, 4);
                    $codePoint = hexdec($hex);
                    $buf .= mb_chr($codePoint, "UTF-8") ?: "";
                    $this->pos += 6;
                    continue;
                }
                if ($next === "x" && $this->pos + 3 < $this->len && ctype_xdigit(substr($this->code, $this->pos + 2, 2))) {
                    $hex = substr($this->code, $this->pos + 2, 2);
                    $buf .= chr(hexdec($hex));
                    $this->pos += 4;
                    continue;
                }

                $buf .= match ($next) {
                    "n"     => "\n",
                    "t"     => "\t",
                    "r"     => "\r",
                    "b"     => "\x08",
                    "f"     => "\x0c",
                    "v"     => "\x0b",
                    "\\"    => "\\",
                    "\""    => "\"",
                    "'"     => "'",
                    "0"     => "\0",
                    default => $next,
                };
                $this->pos += 2;
                continue;
            }

            if ($ch === $quote) {
                $this->pos++;
                return ["type" => "str", "value" => $buf, "pos" => $start];
            }

            $buf .= $ch;
            $this->pos++;
        }

        throw new APIErrorException("Unterminated string literal in script", 12);
    }

    private function readName(): array
    {
        $start = $this->pos;
        while ($this->pos < $this->len) {
            $ch = $this->code[$this->pos];
            if (ctype_alnum($ch) || $ch === "_" || $ch === "$") {
                $this->pos++;
            } else {
                break;
            }
        }

        $value = substr($this->code, $start, $this->pos - $start);
        $type  = in_array($value, self::KEYWORDS, true) ? "keyword" : "name";

        return ["type" => $type, "value" => $value, "pos" => $start];
    }

    private function readOperator(): array
    {
        $start = $this->pos;
        foreach (self::OPERATORS as $op) {
            if (substr($this->code, $this->pos, strlen($op)) === $op) {
                $this->pos += strlen($op);
                return ["type" => "op", "value" => $op, "pos" => $start];
            }
        }

        throw new APIErrorException("Unexpected character '" . $this->code[$this->pos] . "' in script", 12);
    }
}
