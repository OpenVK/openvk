# Names
## Namespace Names
Namespaces should be written in PascalCase.

## File Names
Code directories should have their name written in PascalCase. Code files should contain only one class and have the name of that class.
In case of multiple class definitions in one file, it's name should be the same as the "primary" class name.
Non-code directories, non-class and non-code files should be named in lisp-case.

## Variable Names
Variable names should be written in camelCase. This also applies to function arguments, class instance names and methods.

## Constant Names
Constants are written in SCREAMING_SNAKE_CASE, but should be declared case-insensetive.

## Class Names
Classes in OpenVK should belong to `openvk\` namespace and be in the corresponding directory (according to PSR-4). Names of classes should be written in PascalCase.

## Function Names
camelCase and snake_case are allowed, but first one is the recommended way. This rule does not apply to class methods, which are written in camelCase only.

---

# Coding Rules
## File header
All OpenVK files must start with `<?php` open-tag followed by `declare(strict_types=1);` on the same line. The next line must contain namespace.
The lines after must contain use-declarations, each on it's own line (usage of {} operator is OK), if there is any. Then there must be an empty line. Example:
```php
<?php declare(strict_types=1);
namespace openvk;
use Chandler\Database\DatabaseConnection;
use Nette\Utils\{Image, FileSystem};

class ...
```

## NULL
Null should be written as constant, all-uppercase: `NULL`.

## Nullable (optional) pointer arguments
Optional pointer arguments should default to `nullptr`: `function something(&int? $myPointer = nullptr)`. `nullptr` must be written in lisp-case (lowercase).

## Comparing to NULL
In OpenVK `is_null` is the preferred way to check for equality to NULL. `??` must be used in assignments and where else possible.
In case if value can either be NULL or truthy, "boolean not" should be used to check if value is not null: `if(!$var)`.

## Arrays
Arrays must be defined with modern syntax: `[1, 2, 3]` (NOT `array(1, 2, 3)`).
Same applies to `list` construction: use `[$a, , $b] = $arr;` instead of `list($a, , $b) = $arr;`

## Casts
Typecasts must be done with modern syntax where possible: `(type) $var`. Int-to-string, string-to-int, etc conversions should also be dont with modern casting
syntax where possible, but should use `ctype_` functions where needed. `gettype`, `settype` should be used in dynamic programming only.

## Goto
```goto``` should be avoided.

## `continue n; `
It is preferable to use `continue n`, `break n` instead of guarding flags:
```php
# COOL AND GOOD
foreach($a as $b)
    foreach($b as $c)
        if($b == $c)
            break 2;

# BRUH
foreach($a as $b) {
    $shouldBreak = false;
    foreach($b as $c)
        if($b == $c)
            $shouldBreak = true;
    
    if($shouldBreak)
        break;
}
```

## Comments
In OpenVK we use Perl-style `#` for single-line comments.

---

# Formatting
## Variables
It is preferable to declare only one variable per line in the code.

## Indentation
All things in OpenVK must be properly indented by a sequence of 4 spaces. Not tabs. \
When there are several variable declarations listed together, line up the variables:
```php
# OK
$photos = (new Photos)->where("meow", true);
$photo  = $photos->fetch();
$arr    = [
    "a"  => 10,
    "bb" => true,
];

# NOT OK
$photos = (new Photos)->where("meow", true);
$photo = $photos->fetch();
$arr    = [
    "a" => 10,
    "bb" => true,
];
```

## Tab/Space
+ **Do not use tabs**. Use spaces, as tabs are defined differently for different editors and printers.
+ Put one space after a comma and semicolons: `exp(1, 2)` `for($i = 1; $i < 100; $i++)`
+ Put one space around assignment operators: `$a = 1`
+ Always put a space around conditional operators: `$a = ($a > $b) ? $a : $b`
+ Do not put spaces between unary operators and their operands, primary operators and keywords:
```php
# OK
-$a;
$a++;
$b[1] = $a;
fun($b);
if($a) { ... }

# NOT OK
- $a;
$a ++;
$b [1] = $a;
fun ($b);
if ($a) { ... }
```

## Blank Lines
+ Use blank lines to create paragraphs in the code or comments to make the code more understandable
+ Use blank lines before `return` statement if it isn't the only statement in the block
+ Use blank lines after shorthand if/else/etc
```php
# OK
if($a)
    return $x;
    
doSomething();

return "yay";

# NOT OK
if($a) return $x; # return must be on separate line
doSomething(); # doSomething must be separated by an extra blank line after short if/else
return "yay"; # do use blank lines before return statement
```


## Method/Function Arguments
+ When all arguments for a function do not fit on one line, try to line up the first argument in each line:
![image](https://user-images.githubusercontent.com/34442450/167248563-21fb01be-181d-48b9-ac0c-dc953c0a12cf.png)

+ If the argument lists are still too long to fit on the line, you may line up the arguments with the method name instead.

## Maximum characters per line
Lines should be no more than 80 characters long.

## Usage of curly braces
+ Curly braces should be on separate line for class, method, and function definitions.
+ In loops, if/else, try/catch, switch constructions the opening brace should be on the same line as the operator.
+ Braces must be ommited if the block contains only one statement **AND** the related blocks are also single statemented.
+ Nested single-statement+operator blocks must not be surrounded by braces.
```php
# OK
class A
{
    function doSomethingFunny(): int
    {
        return 2;
    }
}

if(true) {
    doSomething();
    doSomethingElse();
} else {
    doSomethingFunny();
}

if($a)
    return false;
else
    doSomething();
    
foreach($b as $c => $d)
    if($c == $d)
        unset($b[$c]);

# NOT OK
class A {
    function doSomethingFunny(): int {
        return 2;
    }
}

if(true) {
    doSomething();
    doSomethingElse();
} else
    doSomethingFunny(); # why?

if($a) {
    return false;
} else {
    doSomething();
}
    
foreach($b as $c => $d) {
    if($c == $d)
        unset($b[$c]);
}

# lmao
if($a) { doSomething(); } else doSomethingElse();
```

## if/else, try/catch
+ Operators must not be indented with space from their operands but must have 1-space margin from braces:
```php
# OK
if($a) {
    doSomething();
    doSomethingElse();
} else if($b) {
    try {
        nukeSaintPetersburg('😈');
    } finally {
        return PEACE;
    }
}

# NOT OK
if ($a) { # do not add space between control flow operator IF and it's operand
    doSomething();
    doSomethingElse();
}elseif($b){ # do add margin from braces; also ELSE and IF should be separate here
    try{
        nukeSaintPetersburg('😈');
    }finally{
        return PEACE;
    }
}
```

## Switches
+ `break` must be on same indentation level as the code of le case (not the case definiton itself)
+ If there is no need to `break` a comment `# NOTICE falling through` must be places instead
```php
# OK
switch($a) {
    case 1:
        echo $a;
        break;
    
    case 2:
        echo $a++;
        # NOTICE falling through
    
    default:
        echo "c";
}

# NOT OK
switch($a) {
    case 1:
        echo $a;
    break;
    
    case 2:
        echo $a++;
    
    default:
        echo "c";
}
```
