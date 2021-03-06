<?php

namespace DelayedJobs\DelayedJob;

/**
 * Interface DelayedJobDatastoreInterface
 */
interface DatastoreInterface
{
    /**
     * @param \DelayedJobs\DelayedJob\Job $job Job to persist
     * @return \DelayedJobs\DelayedJob\Job|bool
     */
    public function persistJob(Job $job);

    /**
     * @param \DelayedJobs\DelayedJob\Job[] $jobs
     * @return array
     */
    public function persistJobs(array $jobs): array;

    /**
     * @param int $jobId The job to get
     * @return \DelayedJobs\DelayedJob\Job|null
     */
    public function fetchJob($jobId);

    /**
     * Returns true if a job of the same sequence is already persisted and waiting execution.
     *
     * @param \DelayedJobs\DelayedJob\Job $job The job to check for
     * @return bool
     */
    public function currentlySequenced(Job $job): bool;

    /**
     * Gets the next job in the sequence
     *
     * @param \DelayedJobs\DelayedJob\Job $job Job to get next sequence for
     * @return \DelayedJobs\DelayedJob\Job|null
     */
    public function fetchNextSequence(Job $job);

    /**
     * Checks if there already is a job with the same class waiting
     *
     * @param \DelayedJobs\DelayedJob\Job $job Job to check
     * @return bool
     */
    public function isSimilarJob(Job $job): bool;
}
