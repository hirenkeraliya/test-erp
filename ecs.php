<?php

declare(strict_types=1);

use App\CommonFunctions;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\AssignmentInConditionSniff;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Operator\IncrementStyleFixer;
use PhpCsFixer\Fixer\Operator\StandardizeIncrementFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMethodCasingFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Operator\OperatorLinebreakFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSeparationFixer;
use PhpCsFixer\Fixer\LanguageConstruct\CombineConsecutiveUnsetsFixer;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/database',
        __DIR__ . '/tests',
        __DIR__ . '/routes',
    ])
    ->withPreparedSets(
        psr12: true,
        common: true,
        symplify: true,
    )
    ->withPhpCsFixerSets(
        perCS20: true,
        psr12: true,
        php82Migration: true,
        phpCsFixer: true,
        symfony: true,
        doctrineAnnotation: true
    )
    ->withSkip([
        PhpUnitMethodCasingFixer::class,
        MethodChainingNewlineFixer::class,
        MethodChainingIndentationFixer::class,
        IncrementStyleFixer::class,
        StandardizeIncrementFixer::class,
        AssignmentInConditionSniff::class,
        NativeFunctionInvocationFixer::class,
        ConcatSpaceFixer::class,
        GlobalNamespaceImportFixer::class,
        PhpdocAlignFixer::class,
        OperatorLinebreakFixer::class,
        PhpdocSeparationFixer::class,
        CombineConsecutiveUnsetsFixer::class,
    ])
    ->withRules([
        NoUnusedImportsFixer::class,
    ])
    ->withConfiguredRule(MultilineWhitespaceBeforeSemicolonsFixer::class, [
        'strategy' => 'no_multi_line',
    ])
    ->withConfiguredRule(YodaStyleFixer::class, [
        'always_move_variable' => true,
    ]);
