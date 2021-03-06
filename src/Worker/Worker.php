<?php
namespace DelayedJobs\Worker;

use Cake\Datasource\ModelAwareTrait;
use Cake\Event\Event;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use DelayedJobs\DelayedJob\DelayedJobInterface;
use DelayedJobs\DelayedJob\EnqueueTrait;
use DelayedJobs\DelayedJob\Job;
use DelayedJobs\Result\ResultInterface;

/**
 * Class BaseWorker
 */
abstract class Worker implements JobWorkerInterface, EventDispatcherInterface, EventListenerInterface
{
    use EnqueueTrait;
    use EventDispatcherTrait;
    use ModelAwareTrait;

    /**
     * @var \Cake\Console\Shell
     */
    protected $_shell;

    /**
     * Construct the listener
     *
     * @param array $options Allow child listeners to have options
     */
    public function __construct(array $options = [])
    {
        $this->modelFactory('Table', [TableRegistry::class, 'get']);

        if (isset($options['shell'])) {
            $this->_shell = $options['shell'];
            unset($options['shell']);
        }

        $this->getEventManager()->on($this);
    }

    /**
     * Returns a list of events this object is implementing. When the class is registered
     * in an event manager, each individual method will be associated with the respective event.
     *
     * ### Example:
     *
     * ```
     *  public function implementedEvents()
     *  {
     *      return [
     *          'Order.complete' => 'sendEmail',
     *          'Article.afterBuy' => 'decrementInventory',
     *          'User.onRegister' => ['callable' => 'logRegistration', 'priority' => 20, 'passParams' => true]
     *      ];
     *  }
     * ```
     *
     * @return array associative array or event key names pointing to the function
     * that should be called in the object when the respective event is fired
     */
    public function implementedEvents(): array
    {
        return [
            'DelayedJob.beforeJobExecute' => 'beforeExecute',
            'DelayedJob.afterJobExecute' => 'afterExecute'
        ];
    }

    /**
     * @param \Cake\Event\Event $event The event
     * @param \DelayedJobs\DelayedJob\Job $job The job
     * @return void
     */
    public function beforeExecute(Event $event, Job $job)
    {
    }

    /**
     * @param \Cake\Event\Event $event The event
     * @param \DelayedJobs\Result\ResultInterface $result The job result
     * @param int $duration The duration of the execution in milliseconds
     * @return void
     */
    public function afterExecute(Event $event, ResultInterface $result, int $duration)
    {
    }
}
