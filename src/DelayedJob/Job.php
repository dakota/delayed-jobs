<?php

namespace DelayedJobs\DelayedJob;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Event\EventDispatcherInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\I18n\Time;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use DelayedJobs\DelayedJob\Exception\JobDataException;
use DelayedJobs\DelayedJob\Exception\JobExecuteException;
use DelayedJobs\Worker\JobWorkerInterface;

/**
 * Class Job
 */
class Job
{
    const STATUS_NEW = 1;
    const STATUS_BUSY = 2;
    const STATUS_BURRIED = 3;
    const STATUS_SUCCESS = 4;
    const STATUS_KICK = 5;
    const STATUS_FAILED = 6;
    const STATUS_UNKNOWN = 7;
    const STATUS_TEST_JOB = 8;

    /**
     * @var string
     */
    protected $_worker;
    /**
     * @var string
     */
    protected $_group;
    /**
     * @var int
     */
    protected $_priority = 100;
    /**
     * @var array
     */
    protected $_payload = [];
    /**
     * @var array
     */
    protected $_options = [];
    /**
     * @var string
     */
    protected $_sequence;
    /**
     * @var \Cake\I18n\Time
     */
    protected $_runAt;
    /**
     * @var int
     */
    protected $_id;
    /**
     * @var int
     */
    protected $_status = self::STATUS_NEW;
    /**
     * @var int
     */
    protected $_maxRetries = 5;
    /**
     * @var int
     */
    protected $_retries = 0;
    /**
     * @var \Cake\I18n\Time
     */
    protected $_timeFailed;
    /**
     * @var string
     */
    protected $_lastMessage;
    /**
     * @var \Cake\I18n\Time
     */
    protected $_startTime;
    /**
     * @var \Cake\I18n\Time
     */
    protected $_endTime;
    /**
     * @var int
     */
    protected $_duration;
    /**
     * @var string
     */
    protected $_hostName;

    /**
     * Job constructor.
     *
     * @param array $data Data to populate with
     */
    public function __construct(array $data = null)
    {
        if ($data) {
            $this->setData($data);
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'id' => $this->getId(),
            'worker' => $this->getWorker(),
            'group' => $this->getGroup(),
            'priority' => $this->getPriority(),
            'payload' => $this->getPayload(),
            'options' => $this->getOptions(),
            'sequence' => $this->getSequence(),
            'run_at' => $this->getRunAt(),
            'status' => $this->getStatus(),
            'failed_at' => $this->getTimeFailed(),
            'last_message' => $this->getLastMessage(),
            'start_time' => $this->getStartTime(),
            'end_time' => $this->getEndTime(),
            'duration' => $this->getDuration(),
            'max_retries' => $this->getMaxRetries(),
            'retries' => $this->getRetries(),
        ];
    }

    /**
     * @param array $data
     * @return $this
     * @throws \DelayedJobs\DelayedJob\Exception\JobDataException
     */
    public function setData(array $data)
    {
        foreach ($data as $key => $value) {
            $method = 'set' . Inflector::camelize($key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->_id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getRetries()
    {
        return $this->_retries;
    }

    /**
     * @param int $retries
     *
     * @return $this
     */
    public function setRetries($retries)
    {
        $this->_retries = $retries;

        return $this;
    }

    /**
     * @return $this
     */
    public function incrementRetries()
    {
        $this->_retries++;
        
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxRetries()
    {
        return $this->_maxRetries !== null ? $this->_maxRetries : Configure::read('dj.max.retries');
    }

    /**
     * @param int $maxRetries Max retries
     * @return $this
     */
    public function setMaxRetries($maxRetries)
    {
        $this->_maxRetries = $maxRetries;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWorker()
    {
        return $this->_worker;
    }

    /**
     * @param string $worker Class name
     * @return $this
     * @throws JobDataException
     */
    public function setWorker($worker)
    {
        $className = App::className($worker, 'Worker', 'Worker');

        if (!$className) {
            throw new JobDataException(sprintf('Worker name %s is not a valid Worker class', $worker));
        }

        $this->_worker = $worker;

        return $this;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        if (!empty($this->_group)) {
            return $this->_group;
        } else {
            return $this->_worker;
        }
    }

    /**
     * @param string $group
     * @return $this
     */
    public function setGroup($group)
    {
        $this->_group = $group;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->_priority;
    }

    /**
     * @param int $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->_priority = $priority;

        return $this;
    }

    /**
     * @param string $key Hash get compatible key (or null for entire payload)
     * @return mixed
     */
    public function getPayload($key = null)
    {
        if ($key === null) {
            return $this->_payload;
        } else {
            return Hash::get($this->_payload, $key);
        }
    }

    /**
     * @param array $payload Payload array
     * @param bool $defaults Use as defaults
     * @return $this
     */
    public function setPayload(array $payload, $defaults = false)
    {
        if ($defaults === false) {
            $this->_payload = $payload;
        } else {
            $this->_payload += $payload;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * @return string
     */
    public function getSequence()
    {
        return $this->_sequence;
    }

    /**
     * @param string $sequence
     * @return $this
     */
    public function setSequence($sequence = null)
    {
        $this->_sequence = $sequence;

        return $this;
    }

    /**
     * @return \Cake\I18n\Time
     */
    public function getRunAt()
    {
        if ($this->_runAt === null) {
            $this->_runAt = new Time();
        }

        return $this->_runAt;
    }

    /**
     * @param \Cake\I18n\Time $run_at
     * @return $this
     */
    public function setRunAt(Time $run_at = null)
    {
        $this->_runAt = $run_at;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->_status = $status;

        return $this;
    }

    /**
     * @return \Cake\I18n\Time
     */
    public function getTimeFailed()
    {
        return $this->_timeFailed;
    }

    /**
     * @param \Cake\I18n\Time $timeFailed
     * @return $this
     */
    public function setTimeFailed(Time $timeFailed = null)
    {
        $this->_timeFailed = $timeFailed;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastMessage()
    {
        return $this->_lastMessage;
    }

    /**
     * @param string $lastMessage
     * @return $this
     */
    public function setLastMessage($lastMessage)
    {
        $this->_lastMessage = $lastMessage;

        return $this;
    }

    /**
     * @return \Cake\I18n\Time
     */
    public function getStartTime()
    {
        return $this->_startTime;
    }

    /**
     * @param \Cake\I18n\Time $startTime
     * @return $this
     */
    public function setStartTime(Time $startTime = null)
    {
        $this->_startTime = $startTime;

        return $this;
    }

    /**
     * @return \Cake\I18n\Time
     */
    public function getEndTime()
    {
        return $this->_endTime;
    }

    /**
     * @param \Cake\I18n\Time $endTime
     * @return $this
     */
    public function setEndTime(Time $endTime = null)
    {
        $this->_endTime = $endTime;

        return $this;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->_duration;
    }

    /**
     * @param int $duration
     * @return $this
     */
    public function setDuration($duration)
    {
        $this->_duration = $duration;

        return $this;
    }

    /**
     * @return string
     */
    public function getHostName()
    {
        return $this->_hostName;
    }

    /**
     * @param string $hostName
     * @return $this
     */
    public function setHostName($hostName)
    {
        $this->_hostName = $hostName;

        return $this;
    }
}
