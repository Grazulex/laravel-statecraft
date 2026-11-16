<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Guards;

use Grazulex\LaravelStatecraft\Contracts\ContextAwareGuard;
use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class GuardExpression
{
    protected array $expression;

    protected string $from;

    protected string $to;

    /**
     * @var array<string, mixed>
     */
    protected array $context;

    /**
     * @param  array<string, mixed>  $context  Context passed to context-aware guards
     */
    public function __construct(array $expression, string $from, string $to, array $context = [])
    {
        $this->expression = $expression;
        $this->from = $from;
        $this->to = $to;
        $this->context = $context;
    }

    public function evaluate(Model $model): bool
    {
        return $this->evaluateExpression($this->expression, $model);
    }

    protected function evaluateExpression(array $expression, Model $model): bool
    {
        // Logique AND
        if (isset($expression['and'])) {
            return $this->evaluateAndExpression($expression['and'], $model);
        }

        // Logique OR
        if (isset($expression['or'])) {
            return $this->evaluateOrExpression($expression['or'], $model);
        }

        // Logique NOT
        if (isset($expression['not'])) {
            $notExpression = $expression['not'];

            // Si c'est une string, l'évaluer directement
            if (is_string($notExpression)) {
                return ! $this->evaluateGuard($notExpression, $model);
            }

            // Si c'est un array, c'est une sous-expression
            if (is_array($notExpression)) {
                return ! $this->evaluateExpression($notExpression, $model);
            }

            throw new InvalidArgumentException('Invalid NOT expression format');
        }

        throw new InvalidArgumentException('Invalid guard expression format');
    }

    protected function evaluateAndExpression(array $guards, Model $model): bool
    {
        foreach ($guards as $guard) {
            if (! $this->evaluateGuard($guard, $model)) {
                return false;
            }
        }

        return true;
    }

    protected function evaluateOrExpression(array $guards, Model $model): bool
    {
        foreach ($guards as $guard) {
            if ($this->evaluateGuard($guard, $model)) {
                return true;
            }
        }

        return false;
    }

    protected function evaluateGuard($guard, Model $model): bool
    {
        // Si c'est un array, c'est une sous-expression
        if (is_array($guard)) {
            return $this->evaluateExpression($guard, $model);
        }

        // Si c'est une string, c'est une classe Guard
        if (is_string($guard)) {
            // Essayer d'abord le nom tel quel, puis avec le namespace par défaut si ça échoue
            $guardClass = $guard;
            try {
                $guardInstance = app($guardClass);
            } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
                if (! str_contains($guardClass, '\\')) {
                    $guardClass = 'App\\StateMachines\\Guards\\'.$guardClass;
                    try {
                        $guardInstance = app($guardClass);
                    } catch (\Illuminate\Contracts\Container\BindingResolutionException $e2) {
                        throw new InvalidArgumentException("Guard class {$guard} could not be resolved: ".$e->getMessage(), $e->getCode(), $e);
                    }
                } else {
                    throw new InvalidArgumentException("Guard class {$guardClass} could not be resolved: ".$e->getMessage(), $e->getCode(), $e);
                }
            }

            if (! $guardInstance instanceof Guard) {
                throw new InvalidArgumentException("Guard {$guardClass} must implement Guard interface");
            }

            if ($guardInstance instanceof ContextAwareGuard) {
                return $guardInstance->check($model, $this->from, $this->to, $this->context);
            }

            return $guardInstance->check($model, $this->from, $this->to);
        }

        throw new InvalidArgumentException('Invalid guard type');
    }
}
