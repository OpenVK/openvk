<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->name('openvkctl')
    ->exclude(['tmp'])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP82Migration' => true,
    ])
    ->setFinder($finder)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
;
