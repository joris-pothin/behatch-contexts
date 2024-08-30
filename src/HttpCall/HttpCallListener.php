<?php
declare(strict_types=1);

namespace Behatch\HttpCall;

use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Mink\Mink;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class HttpCallListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly ContextSupportedVoter $contextSupportedVoter,
        private readonly HttpCallResultPool $httpCallResultPool,
        private readonly Mink $mink
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StepTested::AFTER => 'afterStep'
        ];
    }

    public function afterStep(AfterStepTested $event)
    {
        $testResult = $event->getTestResult();

        if (!$testResult instanceof ExecutedStepResult) {
            return null;
        }

        $httpCallResult = new HttpCallResult(
            $testResult->getCallResult()->getReturn()
        );

        if ($this->contextSupportedVoter->vote($httpCallResult)) {
            $this->httpCallResultPool->store($httpCallResult);

            return true;
        }

        // For now to avoid modification on MinkContext
        // We add fallback on Mink
        try {
            $this->httpCallResultPool->store(
                new HttpCallResult($this->mink->getSession()->getPage()->getContent())
            );
        } catch (\LogicException) {
            // Mink has no response
        } catch (\Behat\Mink\Exception\DriverException) {
            // No Mink
        }
        return null;
    }
}
