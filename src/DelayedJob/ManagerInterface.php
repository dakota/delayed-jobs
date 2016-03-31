<?php

namespace DelayedJobs\DelayedJob;

use Cake\Console\Shell;
use DelayedJobs\DelayedJob\Job;

/**
 * Interface DelayedJobManagerInterface
 */
interface ManagerInterface
{
    /**
     * @param \DelayedJobs\DelayedJob\Job $job
     * @return \DelayedJobs\DelayedJob\Job|bool
     */
    public function enqueue(Job $job);

    /**
     * @param \DelayedJobs\DelayedJob\Job $job Job that failed
     * @param string $message Message to store with the jbo
     * @param bool $burryJob Should the job be burried
     * @return bool|\DelayedJobs\DelayedJob\Job
     */
    public function failed(Job $job, $message, $burryJob = false);

    /**
     * @param \DelayedJobs\DelayedJob\Job $job Job that has been completed
     * @param string|null $message Message to store with job
     * @param int $duration How long execution took
     * @return \DelayedJobs\DelayedJob\Job|bool
     */
    public function completed(Job $job, $message = null, $duration = 0);

    /**
     * Gets the Job instance for a specific job
     *
     * @param int $jobId Job to fetch
     * @return \DelayedJobs\DelayedJob\Job
     * @throws \DelayedJobs\DelayedJob\Exception\JobNotFoundException
     */
    public function fetchJob($jobId);

    /**
     * Gets the current status for a requested job
     *
     * @param int $jobId Job to get status for
     * @return int
     */
    public function getStatus($jobId);

    public function lock(Job $job, $hostname = null);

    public function execute(Job $job, Shell $shell = null);

    public function enqueueNextSequence(Job $job);

    public function isSimilarJob(Job $job);
}
