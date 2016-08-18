# string-justifier
PHP 5.3+ library for justifying strings.

### Usage

The constructor takes a line separator (used to split justified chunks), a word break character (used in the output when words are split) and a paragraph separator (separate paragraphs are treated individually, then joined together at the end).

```php
$string = <<<EOF
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer et tempor velit, vitae porttitor mauris. Phasellus fermentum dignissim nulla quis eleifend. Praesent vestibulum diam nisi, non egestas enim aliquam sit amet. Etiam consequat ipsum risus. Duis quis urna velit. Vivamus condimentum bibendum felis sed tempor. Suspendisse consectetur nibh vel odio bibendum euismod. Cras vehicula aliquam leo, quis laoreet nunc tincidunt quis.
EOF;

$justifier = new \Cev\Justifier\StringJustifier(
    "\n", //line separator
    "-", //indicate word break
    "\n\n" //paragraph separator
);

echo $justifier->format(
    $string,
    32,      //line width
    3        //max allowed spaces between words
);

//gives the same output:
echo $justifier($string, 32, 3);
```

Unicode characters are properly handled. If the mb_* functions are not available then composer require symfony/polyfill-mbstring.
