<?php

namespace DelayedJobs\DelayedJob;

trait EnqueueTrait
{
    /**
     * @param string|\DelayedJobs\DelayedJob\Job $worker Worker class to enqueue (In CakePHP format), or a Job instance
     * @param mixed $payload The payload for the job
     * @param array $options Array of options
     * @return \DelayedJobs\DelayedJob\Job
     * @throws \DelayedJobs\DelayedJob\Exception\JobDataException
     */
    public function enqueue($worker, $payload = null, array $options = [])
    {
        if ($worker instanceof Job) {
            $job = $worker;
        } else {
            $job = new Job();
            $job
                ->setWorker($worker)
                ->setPayload($payload)
                ->setData($options);
        }

        return Manager::instance()->enqueue($job);
    }

    /**
     * Enqueues a batch of similar jobs
     *
     * @param string $worker Worker class to enqueue (In CakePHP format)
     * @param array $jobsToEnqueue Array of jobs to enqueue. Can either be a simple array for the payload, or contain two keys (_payload, and _options)
     * @param array $options Default options for all jobs. Can be overridden with the `_options` key
     * @return array
     */
    public function enqueueBatch($worker, array $jobsToEnqueue, array $options = [])
    {
        $jobs = [];

        foreach ($jobsToEnqueue as $jobInfo) {
            $jobPayload = $jobInfo;
            $jobOptions = $options;
            if (isset($jobInfo['_payload'])) {
                $jobPayload = $jobInfo['_payload'];
            }
            if (isset($jobInfo['_options'])) {
                $jobOptions = $jobInfo['_options'] + $options;
            }
            $job = new Job();
            $job->setWorker($worker)
                ->setPayload($jobPayload)
                ->setData($jobOptions);
            $jobs[] = $job;
        }

        return Manager::instance()->enqueueBatch($jobs);
    }
}
