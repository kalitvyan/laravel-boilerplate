<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Presentation\Http\Problem;

use LogicException;
use Throwable;

final readonly class ProblemMap
{
    /**
     * @param  array<class-string<Throwable>, ProblemDefinition>  $definitions
     */
    public function __construct(private array $definitions = []) {}

    /**
     * @param  array<class-string<Throwable>, ProblemDefinition>  $definitions
     */
    public function with(array $definitions): self
    {
        foreach (array_keys($definitions) as $exception) {
            if (isset($this->definitions[$exception])) {
                throw new LogicException(
                    message: sprintf('Problem for %s is already registered', $exception)
                );
            }
        }

        return new self([...$this->definitions, ...$definitions]);
    }

    /**
     * Ищет по точному классу, затем по родителям: UserNotFound → NotFound.
     */
    public function find(Throwable $exception): ?ProblemDefinition
    {
        for ($class = $exception::class; $class !== false; $class = get_parent_class($class)) {
            if (isset($this->definitions[$class])) {
                return $this->definitions[$class];
            }
        }

        return null;
    }
}
