<?php

declare(strict_types=1);

use App\CommonFunctions;

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Transform\Rector\FuncCall\FuncCallToNewRector;
use RectorLaravel\Rector\Class_\AnonymousMigrationsRector;
use RectorLaravel\Rector\Namespace_\FactoryDefinitionRector;
use RectorLaravel\Rector\Class_\UnifyModelDatesWithCastsRector;
use RectorLaravel\Rector\FuncCall\RemoveDumpDataDeadCodeRector;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use RectorLaravel\Rector\MethodCall\FactoryApplyingStatesRector;
use Rector\Strict\Rector\If_\BooleanInIfConditionRuleFixerRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use RectorLaravel\Rector\MethodCall\RedirectBackToBackHelperRector;
use Rector\Transform\Rector\StaticCall\StaticCallToMethodCallRector;
use RectorLaravel\Rector\FuncCall\FactoryFuncCallToStaticCallRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use RectorLaravel\Rector\FuncCall\ArgumentFuncCallToMethodCallRector;
use Rector\Strict\Rector\BooleanNot\BooleanInBooleanNotRuleFixerRector;
use Rector\Strict\Rector\Ternary\DisallowedShortTernaryRuleFixerRector;
use RectorLaravel\Rector\MethodCall\RedirectRouteToToRouteHelperRector;
use RectorLaravel\Rector\PropertyFetch\OptionalToNullsafeOperatorRector;
use Rector\Strict\Rector\Ternary\BooleanInTernaryOperatorRuleFixerRector;
use Rector\Php70\Rector\StaticCall\StaticCallOnNonStaticToInstanceCallRector;
use RectorLaravel\Rector\MethodCall\ChangeQueryWhereDateValueWithCarbonRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use RectorLaravel\Rector\FuncCall\DispatchNonShouldQueueToDispatchSyncRector;
use RectorLaravel\Rector\StaticCall\EloquentMagicMethodToQueryBuilderRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/database',
        __DIR__ . '/tests',
        __DIR__ . '/routes',
    ])
    ->withPhpSets(php82: true)
    ->withParallel(50000)
    ->withImportNames(removeUnusedImports: true)
    ->withIndent()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: true,
    )
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        LaravelSetList::ARRAY_STR_FUNCTIONS_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_100,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_STATIC_TO_INJECTION,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
        FactoryDefinitionRector::class,
        FactoryFuncCallToStaticCallRector::class,
        OptionalToNullsafeOperatorRector::class,
        RedirectBackToBackHelperRector::class,
        RedirectRouteToToRouteHelperRector::class,
        RemoveDumpDataDeadCodeRector::class,
        UnifyModelDatesWithCastsRector::class,
        AnonymousMigrationsRector::class,
        FactoryApplyingStatesRector::class,
        ChangeQueryWhereDateValueWithCarbonRector::class,
    ])
    ->withSkip([
        RemoveNonExistingVarAnnotationRector::class,
        RenamePropertyToMatchTypeRector::class,
        RenameParamToMatchTypeRector::class,
        NullToStrictStringFuncCallArgRector::class,
        RenameVariableToMatchMethodCallReturnTypeRector::class,
        ReadOnlyClassRector::class,
        FuncCallToNewRector::class,
        EloquentMagicMethodToQueryBuilderRector::class,
        RenameForeachValueVariableToMatchExprVariableRector::class,
        StaticCallToMethodCallRector::class,
        ArgumentFuncCallToMethodCallRector::class,
        FirstClassCallableRector::class,
        BooleanInTernaryOperatorRuleFixerRector::class,
        BooleanInBooleanNotRuleFixerRector::class,
        BooleanInIfConditionRuleFixerRector::class,
        StaticCallOnNonStaticToInstanceCallRector::class,
        DisallowedShortTernaryRuleFixerRector::class,
        AddMethodCallBasedStrictParamTypeRector::class => [
            __DIR__ . '/app/Domains/Sale/Services/WorstTwentyByProductReportService.php',
            __DIR__ . '/app/Domains/Sale/Services/TopTwentyByProductReportService.php',
        ],
        DispatchNonShouldQueueToDispatchSyncRector::class => [
            __DIR__ . '/app/Console/Commands/DispatchJobs.php',
        ],
    ]);
