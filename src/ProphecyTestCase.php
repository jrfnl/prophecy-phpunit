<?php declare(strict_types=1);

namespace Prophecy\PhpUnit;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Prophecy\Exception\Doubler\DoubleException;
use Prophecy\Exception\Doubler\InterfaceNotFoundException;
use Prophecy\Exception\Prediction\PredictionException;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;

abstract class ProphecyTestCase extends TestCase
{
    /**
     * @var Prophet|null
     */
    private $prophet;

    /**
     * @var bool
     */
    private $prophecyAssertionsCounted = false;

    /**
     * @throws DoubleException
     * @throws InterfaceNotFoundException
     *
     * @psalm-param class-string|null $type
     */
    protected function prophesize(?string $classOrInterface = null): ObjectProphecy
    {
        if (\is_string($classOrInterface)) {
            $this->recordDoubledType($classOrInterface);
        }

        return $this->getProphet()->prophesize($classOrInterface);
    }

    protected function verifyMockObjects(): void
    {
        parent::verifyMockObjects();

        if ($this->prophet === null) {
            return;
        }

        try {
            $this->prophet->checkPredictions();
        } catch (PredictionException $e) {
            throw new AssertionFailedError($e->getMessage());
        } finally {
            $this->countProphecyAssertions();
        }
    }

    /**
     * @after
     */
    protected function prophecyTearDown(): void
    {
        if (null !== $this->prophet && !$this->prophecyAssertionsCounted) {
            // Some Prophecy assertions may have been done in tests themselves even when a failure happened before checking mock objects.
            $this->countProphecyAssertions();
        }

        $this->prophet = null;
    }

    private function countProphecyAssertions(): void
    {
        $this->prophecyAssertionsCounted = true;

        foreach ($this->prophet->getProphecies() as $objectProphecy) {
            foreach ($objectProphecy->getMethodProphecies() as $methodProphecies) {
                foreach ($methodProphecies as $methodProphecy) {
                    \assert($methodProphecy instanceof MethodProphecy);

                    $this->addToAssertionCount(\count($methodProphecy->getCheckedPredictions()));
                }
            }
        }
    }

    private function getProphet(): Prophet
    {
        if ($this->prophet === null) {
            $this->prophet = new Prophet;
        }

        return $this->prophet;
    }
}