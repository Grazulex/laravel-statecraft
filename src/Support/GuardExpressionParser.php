<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Support;

use Grazulex\LaravelStatecraft\Contracts\ContextAwareGuard;
use Grazulex\LaravelStatecraft\Guards\GuardExpression;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class GuardExpressionParser
{
    public static function parse($guard, string $from, string $to): callable
    {
        return function (Model $model, array $context = []) use ($guard, $from, $to) {
            // Si c'est une string simple, c'est un guard classique
            if (is_string($guard)) {
                $guardInstance = app($guard);

                if ($guardInstance instanceof ContextAwareGuard) {
                    return $guardInstance->check($model, $from, $to, $context);
                }

                return $guardInstance->check($model, $from, $to);
            }

            // Si c'est un array, c'est une expression
            if (is_array($guard)) {
                $expression = new GuardExpression($guard, $from, $to, $context);

                return $expression->evaluate($model);
            }

            throw new InvalidArgumentException('Invalid guard format');
        };
    }

    public static function isExpression($guard): bool
    {
        return is_array($guard) && (
            isset($guard['and']) ||
            isset($guard['or']) ||
            isset($guard['not'])
        );
    }

    public static function validateExpression(array $expression): bool
    {
        // Vérifier la structure de l'expression
        if (isset($expression['and'])) {
            return is_array($expression['and']) && self::validateGuardList($expression['and']);
        }

        if (isset($expression['or'])) {
            return is_array($expression['or']) && self::validateGuardList($expression['or']);
        }

        if (isset($expression['not'])) {
            return is_array($expression['not']) ?
                self::validateExpression($expression['not']) :
                is_string($expression['not']);
        }

        return false;
    }

    protected static function validateGuardList(array $guards): bool
    {
        foreach ($guards as $guard) {
            if (is_string($guard)) {
                continue; // Guard classique
            }

            if (is_array($guard)) {
                if (! self::validateExpression($guard)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }
}
